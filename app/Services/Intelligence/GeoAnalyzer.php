<?php

namespace App\Services\Intelligence;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class GeoAnalyzer
{
    /** @var array<string, string> */
    private const AI_CRAWLERS = [
        'GPTBot' => 'OpenAI GPTBot',
        'OAI-SearchBot' => 'OpenAI Search',
        'ChatGPT-User' => 'ChatGPT User',
        'ClaudeBot' => 'Anthropic Claude',
        'anthropic-ai' => 'Anthropic AI',
        'PerplexityBot' => 'Perplexity AI',
        'CCBot' => 'Common Crawl',
        'Bytespider' => 'ByteDance Index',
        'cohere-ai' => 'Cohere AI',
        'Google-Extended' => 'Google AI (Gemini)',
        'GoogleOther' => 'Google Other Crawlers',
        'Applebot-Extended' => 'Apple AI',
        'FacebookBot' => 'Meta AI',
        'Amazonbot' => 'Amazon Alexa',
    ];

    private const TIMEOUT = 10;

    /** @var array<string, float> */
    private const CITABILITY_WEIGHTS = [
        'answer_block' => 0.30,
        'self_containment' => 0.25,
        'structural_readability' => 0.20,
        'statistical_density' => 0.15,
        'uniqueness_signals' => 0.10,
    ];

    /**
     * Analyze a URL for GEO (Generative Engine Optimization) readiness.
     *
     * @return array<string, mixed>
     */
    public function analyze(string $url, ?string $brandName = null): array
    {
        $pageData = $this->fetchPage($url);
        $robotsTxt = $this->fetchRobotsTxt($url);

        $this->rateLimitDelay();

        $llmsTxt = $this->fetchLlmsTxt($url);

        $this->rateLimitDelay();

        $citability = $pageData ? $this->scoreCitability($pageData) : $this->emptyCitability();
        $crawlerAccess = $this->parseCrawlerAccess($robotsTxt);
        $brandMentions = $brandName ? $this->scanBrandMentions($brandName) : $this->emptyBrandMentions();
        $schemaMarkup = $pageData ? $this->extractSchemaMarkup($pageData) : [];
        $technicalSeo = $pageData ? $this->assessTechnicalSeo($pageData, $url) : $this->emptyTechnicalSeo();

        return [
            'page_data' => [
                'url' => $url,
                'fetched' => $pageData !== null,
                'word_count' => $pageData ? str_word_count(strip_tags($pageData)) : 0,
            ],
            'robots_txt' => [
                'found' => $robotsTxt !== null,
                'content_length' => $robotsTxt ? strlen($robotsTxt) : 0,
                'ai_crawlers' => $crawlerAccess,
            ],
            'llms_txt' => [
                'found' => $llmsTxt !== null,
                'content_length' => $llmsTxt ? strlen($llmsTxt) : 0,
                'preview' => $llmsTxt ? substr($llmsTxt, 0, 500) : null,
            ],
            'citability' => $citability,
            'brand_mentions' => $brandMentions,
            'schema_markup' => $schemaMarkup,
            'technical_seo' => $technicalSeo,
        ];
    }

    /**
     * Analyze a brand without a website (for no-website leads).
     *
     * @return array<string, mixed>
     */
    public function analyzeWithoutWebsite(string $brandName): array
    {
        $this->rateLimitDelay();

        $brandMentions = $this->scanBrandMentions($brandName);

        return [
            'page_data' => ['url' => null, 'fetched' => false, 'word_count' => 0],
            'robots_txt' => ['found' => false, 'content_length' => 0, 'ai_crawlers' => $this->allCrawlersUnknown()],
            'llms_txt' => ['found' => false, 'content_length' => 0, 'preview' => null],
            'citability' => $this->emptyCitability(),
            'brand_mentions' => $brandMentions,
            'schema_markup' => [],
            'technical_seo' => $this->emptyTechnicalSeo(),
        ];
    }

    // ─── Fetching ────────────────────────────────────────────────────────────

    private function fetchPage(string $url): ?string
    {
        try {
            $response = Http::timeout(self::TIMEOUT)
                ->withHeaders(['User-Agent' => 'Mozilla/5.0 (compatible; AllLeads/1.0)'])
                ->get($url);

            return $response->successful() ? $response->body() : null;
        } catch (\Throwable $e) {
            Log::debug('GeoAnalyzer: failed to fetch page', ['url' => $url, 'error' => $e->getMessage()]);

            return null;
        }
    }

    private function fetchRobotsTxt(string $url): ?string
    {
        $baseUrl = rtrim($this->extractBaseUrl($url), '/');

        try {
            $response = Http::timeout(self::TIMEOUT)
                ->withHeaders(['User-Agent' => 'Mozilla/5.0 (compatible; AllLeads/1.0)'])
                ->get("{$baseUrl}/robots.txt");

            return $response->successful() ? $response->body() : null;
        } catch (\Throwable $e) {
            Log::debug('GeoAnalyzer: failed to fetch robots.txt', ['url' => $baseUrl, 'error' => $e->getMessage()]);

            return null;
        }
    }

    private function fetchLlmsTxt(string $url): ?string
    {
        $baseUrl = rtrim($this->extractBaseUrl($url), '/');

        try {
            $response = Http::timeout(self::TIMEOUT)
                ->withHeaders(['User-Agent' => 'Mozilla/5.0 (compatible; AllLeads/1.0)'])
                ->get("{$baseUrl}/llms.txt");

            return ($response->successful() && str_contains($response->header('Content-Type') ?? '', 'text'))
                ? $response->body()
                : null;
        } catch (\Throwable $e) {
            Log::debug('GeoAnalyzer: failed to fetch llms.txt', ['url' => $baseUrl, 'error' => $e->getMessage()]);

            return null;
        }
    }

    // ─── robots.txt parsing ──────────────────────────────────────────────────

    /**
     * Parse robots.txt and return access status for each AI crawler.
     *
     * @return array<string, array<string, string>>
     */
    private function parseCrawlerAccess(?string $robotsTxt): array
    {
        if ($robotsTxt === null) {
            return $this->allCrawlersUnknown();
        }

        $result = [];
        $lines = array_map('trim', explode("\n", $robotsTxt));
        $currentAgents = [];
        $rules = [];

        foreach ($lines as $line) {
            if (str_starts_with($line, '#') || $line === '') {
                $currentAgents = [];

                continue;
            }

            if (str_starts_with(strtolower($line), 'user-agent:')) {
                $agent = trim(substr($line, 11));
                $currentAgents[] = $agent;

                continue;
            }

            if (str_starts_with(strtolower($line), 'disallow:')) {
                $path = trim(substr($line, 9));

                foreach ($currentAgents as $agent) {
                    $rules[$agent][] = ['type' => 'disallow', 'path' => $path];
                }

                continue;
            }

            if (str_starts_with(strtolower($line), 'allow:')) {
                $path = trim(substr($line, 6));

                foreach ($currentAgents as $agent) {
                    $rules[$agent][] = ['type' => 'allow', 'path' => $path];
                }
            }
        }

        foreach (self::AI_CRAWLERS as $botName => $label) {
            $access = $this->determineCrawlerAccess($botName, $rules);
            $result[$botName] = ['label' => $label, 'status' => $access];
        }

        return $result;
    }

    /**
     * @param  array<string, array<array<string, string>>>  $rules
     */
    private function determineCrawlerAccess(string $botName, array $rules): string
    {
        $matchedRules = $rules[$botName] ?? $rules['*'] ?? [];

        if (empty($matchedRules)) {
            return 'allowed';
        }

        foreach ($matchedRules as $rule) {
            if ($rule['type'] === 'disallow' && $rule['path'] === '/') {
                return 'blocked';
            }
        }

        $hasDisallow = collect($matchedRules)->where('type', 'disallow')->where('path', '!=', '')->count() > 0;

        return $hasDisallow ? 'partial' : 'allowed';
    }

    /**
     * @return array<string, array<string, string>>
     */
    private function allCrawlersUnknown(): array
    {
        $result = [];

        foreach (self::AI_CRAWLERS as $bot => $label) {
            $result[$bot] = ['label' => $label, 'status' => 'unknown'];
        }

        return $result;
    }

    // ─── Citability scoring ──────────────────────────────────────────────────

    /**
     * Score citability using 5-factor algorithm.
     *
     * @return array<string, mixed>
     */
    private function scoreCitability(string $html): array
    {
        $text = strip_tags($html);

        $scores = [
            'answer_block' => $this->scoreAnswerBlock($text),
            'self_containment' => $this->scoreSelfContainment($text),
            'structural_readability' => $this->scoreStructuralReadability($html),
            'statistical_density' => $this->scoreStatisticalDensity($text),
            'uniqueness_signals' => $this->scoreUniquenessSignals($text),
        ];

        $weighted = 0.0;
        foreach (self::CITABILITY_WEIGHTS as $factor => $weight) {
            $weighted += $scores[$factor] * $weight;
        }

        $total = (int) round($weighted);
        $grade = match (true) {
            $total >= 80 => 'A',
            $total >= 65 => 'B',
            $total >= 50 => 'C',
            $total >= 35 => 'D',
            default => 'F',
        };

        return array_merge($scores, ['total' => $total, 'grade' => $grade]);
    }

    private function scoreAnswerBlock(string $text): int
    {
        $wordCount = str_word_count($text);

        if ($wordCount < 50) {
            return 10;
        }

        $sentences = preg_split('/[.!?]+/', $text, -1, PREG_SPLIT_NO_EMPTY) ?: [];
        $avgSentenceLength = $wordCount / max(count($sentences), 1);

        $score = 50;

        // Reward concise, direct sentences (ideal 15-25 words)
        if ($avgSentenceLength >= 15 && $avgSentenceLength <= 25) {
            $score += 20;
        } elseif ($avgSentenceLength < 30) {
            $score += 10;
        }

        // Reward question-answer patterns
        if (substr_count(strtolower($text), '?') > 2) {
            $score += 15;
        }

        // Reward numbered lists (often direct answers)
        if (preg_match_all('/^\d+\./m', $text) > 2) {
            $score += 15;
        }

        return min(100, $score);
    }

    private function scoreSelfContainment(string $text): int
    {
        $wordCount = str_word_count($text);

        if ($wordCount < 100) {
            return 20;
        }

        $score = 50;

        // Reward definitions
        if (preg_match_all('/\b(is defined as|refers to|means that|is a type of)\b/i', $text) > 1) {
            $score += 20;
        }

        // Penalise "click here" type content (relies on external context)
        if (preg_match_all('/\b(click here|read more|see also|learn more)\b/i', $text) > 3) {
            $score -= 15;
        }

        // Reward longer, substantive content
        if ($wordCount > 500) {
            $score += 20;
        } elseif ($wordCount > 200) {
            $score += 10;
        }

        return max(0, min(100, $score));
    }

    private function scoreStructuralReadability(string $html): int
    {
        $score = 30;

        // H1 presence
        if (preg_match('/<h1[\s>]/i', $html)) {
            $score += 15;
        }

        // H2/H3 headings
        $headingCount = preg_match_all('/<h[23][\s>]/i', $html);
        $score += min(20, $headingCount * 5);

        // Lists (structured content)
        $listCount = preg_match_all('/<ul|<ol/i', $html);
        $score += min(15, $listCount * 5);

        // Tables (structured data)
        if (preg_match('/<table/i', $html)) {
            $score += 10;
        }

        // Paragraphs
        $paraCount = preg_match_all('/<p[\s>]/i', $html);
        $score += min(10, $paraCount);

        return min(100, $score);
    }

    private function scoreStatisticalDensity(string $text): int
    {
        $wordCount = str_word_count($text);

        if ($wordCount < 50) {
            return 10;
        }

        $score = 20;

        // Count numbers/statistics
        $numberCount = preg_match_all('/\b\d+(?:\.\d+)?(?:%|k|m|b|million|billion|thousand)?\b/i', $text);
        $density = $numberCount / ($wordCount / 100);

        if ($density >= 5) {
            $score += 60;
        } elseif ($density >= 3) {
            $score += 40;
        } elseif ($density >= 1) {
            $score += 20;
        }

        // Year references suggest currency
        if (preg_match_all('/\b(202[3-9]|203\d)\b/', $text) > 0) {
            $score += 20;
        }

        return min(100, $score);
    }

    private function scoreUniquenessSignals(string $text): int
    {
        $score = 30;

        // Proprietary terms
        if (preg_match_all('/\b(according to|our research|we found|study shows|data shows|survey of)\b/i', $text) > 0) {
            $score += 25;
        }

        // Case studies / named examples
        if (preg_match_all('/\b(case study|for example|for instance|such as)\b/i', $text) > 1) {
            $score += 25;
        }

        // Quotes
        if (preg_match_all('/"[^"]{10,}"/', $text) > 0) {
            $score += 20;
        }

        return min(100, $score);
    }

    /**
     * @return array<string, mixed>
     */
    private function emptyCitability(): array
    {
        return [
            'answer_block' => 0,
            'self_containment' => 0,
            'structural_readability' => 0,
            'statistical_density' => 0,
            'uniqueness_signals' => 0,
            'total' => 0,
            'grade' => 'F',
        ];
    }

    // ─── Brand scanning ──────────────────────────────────────────────────────

    /**
     * Scan for brand mentions across Wikipedia and Wikidata.
     *
     * @return array<string, mixed>
     */
    private function scanBrandMentions(string $brandName): array
    {
        $result = [
            'brand_name' => $brandName,
            'wikipedia' => $this->checkWikipedia($brandName),
            'wikidata' => $this->checkWikidata($brandName),
        ];

        return $result;
    }

    /**
     * @return array<string, mixed>
     */
    private function checkWikipedia(string $brandName): array
    {
        try {
            $response = Http::timeout(self::TIMEOUT)
                ->withHeaders(['User-Agent' => 'AllLeads/1.0 (contact@allleads.io)'])
                ->get('https://en.wikipedia.org/w/api.php', [
                    'action' => 'query',
                    'list' => 'search',
                    'srsearch' => $brandName,
                    'srlimit' => 3,
                    'format' => 'json',
                ]);

            if (! $response->successful()) {
                return ['found' => false, 'pages' => []];
            }

            $data = $response->json();
            $results = $data['query']['search'] ?? [];

            $pages = array_map(fn ($r) => [
                'title' => $r['title'],
                'snippet' => strip_tags($r['snippet'] ?? ''),
                'url' => 'https://en.wikipedia.org/wiki/'.str_replace(' ', '_', $r['title']),
            ], array_slice($results, 0, 3));

            return [
                'found' => count($pages) > 0,
                'pages' => $pages,
            ];
        } catch (\Throwable $e) {
            Log::debug('GeoAnalyzer: Wikipedia check failed', ['brand' => $brandName, 'error' => $e->getMessage()]);

            return ['found' => false, 'pages' => []];
        }
    }

    /**
     * @return array<string, mixed>
     */
    private function checkWikidata(string $brandName): array
    {
        try {
            $response = Http::timeout(self::TIMEOUT)
                ->withHeaders(['User-Agent' => 'AllLeads/1.0 (contact@allleads.io)'])
                ->get('https://www.wikidata.org/w/api.php', [
                    'action' => 'wbsearchentities',
                    'search' => $brandName,
                    'language' => 'en',
                    'limit' => 3,
                    'format' => 'json',
                ]);

            if (! $response->successful()) {
                return ['found' => false, 'entities' => []];
            }

            $data = $response->json();
            $entities = $data['search'] ?? [];

            $items = array_map(fn ($e) => [
                'id' => $e['id'],
                'label' => $e['label'] ?? '',
                'description' => $e['description'] ?? '',
                'url' => $e['url'] ?? "https://www.wikidata.org/wiki/{$e['id']}",
            ], array_slice($entities, 0, 3));

            return [
                'found' => count($items) > 0,
                'entities' => $items,
            ];
        } catch (\Throwable $e) {
            Log::debug('GeoAnalyzer: Wikidata check failed', ['brand' => $brandName, 'error' => $e->getMessage()]);

            return ['found' => false, 'entities' => []];
        }
    }

    /**
     * @return array<string, mixed>
     */
    private function emptyBrandMentions(): array
    {
        return [
            'brand_name' => null,
            'wikipedia' => ['found' => false, 'pages' => []],
            'wikidata' => ['found' => false, 'entities' => []],
        ];
    }

    // ─── Schema markup ───────────────────────────────────────────────────────

    /**
     * Extract JSON-LD schema markup types from HTML.
     *
     * @return array<int, array<string, string>>
     */
    private function extractSchemaMarkup(string $html): array
    {
        $schemas = [];

        preg_match_all('/<script[^>]+type=["\']application\/ld\+json["\'][^>]*>(.*?)<\/script>/si', $html, $matches);

        foreach ($matches[1] as $jsonString) {
            $data = json_decode(trim($jsonString), true);

            if (! is_array($data)) {
                continue;
            }

            // Handle @graph arrays
            if (isset($data['@graph']) && is_array($data['@graph'])) {
                foreach ($data['@graph'] as $item) {
                    if (isset($item['@type'])) {
                        $schemas[] = ['type' => $item['@type'], 'context' => $data['@context'] ?? ''];
                    }
                }
            } elseif (isset($data['@type'])) {
                $schemas[] = ['type' => $data['@type'], 'context' => $data['@context'] ?? ''];
            }
        }

        return $schemas;
    }

    // ─── Technical SEO ───────────────────────────────────────────────────────

    /**
     * @return array<string, mixed>
     */
    private function assessTechnicalSeo(string $html, string $url): array
    {
        return [
            'has_meta_description' => (bool) preg_match('/<meta[^>]+name=["\']description["\'][^>]*>/i', $html),
            'has_og_tags' => (bool) preg_match('/<meta[^>]+property=["\']og:/i', $html),
            'has_canonical' => (bool) preg_match('/<link[^>]+rel=["\']canonical["\'][^>]*>/i', $html),
            'has_viewport' => (bool) preg_match('/<meta[^>]+name=["\']viewport["\'][^>]*>/i', $html),
            'is_https' => str_starts_with($url, 'https://'),
            'has_h1' => (bool) preg_match('/<h1[\s>]/i', $html),
        ];
    }

    /**
     * @return array<string, bool>
     */
    private function emptyTechnicalSeo(): array
    {
        return [
            'has_meta_description' => false,
            'has_og_tags' => false,
            'has_canonical' => false,
            'has_viewport' => false,
            'is_https' => false,
            'has_h1' => false,
        ];
    }

    // ─── Helpers ─────────────────────────────────────────────────────────────

    private function extractBaseUrl(string $url): string
    {
        $parsed = parse_url($url);
        $scheme = $parsed['scheme'] ?? 'https';
        $host = $parsed['host'] ?? $url;

        return "{$scheme}://{$host}";
    }

    private function rateLimitDelay(): void
    {
        usleep(random_int(200_000, 500_000));
    }
}
