<?php

namespace App\Jobs;

use App\Models\AiSetting;
use App\Models\BusinessSetting;
use App\Models\GeoAnalysis;
use App\Services\Ai\AiProviderFactory;
use App\Services\Intelligence\GeoAnalyzer;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class RunCompanyGeoAnalysisJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 2;

    public int $timeout = 180;

    public function __construct(
        public readonly string $url,
        public readonly int $userId,
    ) {}

    public function handle(GeoAnalyzer $analyzer): void
    {
        $analysis = GeoAnalysis::create([
            'user_id' => $this->userId,
            'url' => $this->url,
            'status' => GeoAnalysis::STATUS_PENDING,
            'started_at' => now(),
        ]);

        $businessSetting = BusinessSetting::singleton();
        $brandName = $businessSetting->company_name ?? parse_url($this->url, PHP_URL_HOST) ?? $this->url;

        $rawData = $analyzer->analyze($this->url, $brandName);
        $analysis->update(['raw_data' => $rawData]);

        $setting = AiSetting::singleton();
        $provider = AiProviderFactory::makeWithFallback($setting);

        $system = $this->buildSystemPrompt($setting->language ?? 'English');
        $user = $this->buildUserPrompt($rawData, $this->url);

        $raw = $provider->complete($system, $user, [
            'model' => $setting->model,
            'temperature' => (float) $setting->temperature,
            'max_tokens' => (int) $setting->max_tokens,
            'timeout' => (int) $setting->timeout,
        ]);

        $result = $this->parseJsonResponse($raw);

        $analysis->update([
            'status' => GeoAnalysis::STATUS_COMPLETED,
            'result' => $result,
            'provider' => $setting->provider,
            'model' => $setting->model,
            'completed_at' => now(),
        ]);
    }

    public function failed(\Throwable $e): void
    {
        Log::error('RunCompanyGeoAnalysisJob failed', [
            'url' => $this->url,
            'user_id' => $this->userId,
            'error' => $e->getMessage(),
        ]);

        GeoAnalysis::where('user_id', $this->userId)
            ->where('url', $this->url)
            ->whereIn('status', [GeoAnalysis::STATUS_PENDING])
            ->update([
                'status' => GeoAnalysis::STATUS_FAILED,
                'error_message' => $e->getMessage(),
            ]);
    }

    // ─── Helpers ────────────────────────────────────────────────────────────

    private function buildSystemPrompt(string $language): string
    {
        $businessSetting = BusinessSetting::singleton();
        $businessContext = $businessSetting->toPromptContext();

        return <<<PROMPT
You are a GEO (Generative Engine Optimization) specialist conducting a self-audit.

{$businessContext}

Analyse the provided website crawl data for OUR OWN website and return a structured JSON object with exactly these keys:

- geo_score (integer 1-100): overall GEO readiness score. Weight: citability 35%, crawler access 25%, brand authority 20%, schema markup 10%, technical SEO 10%
- ai_visibility_summary (string): 2-3 sentence executive summary of our current AI visibility
- citability_assessment (string): analysis of our content quality for AI citation — what's working and what needs improvement
- crawler_access_summary (string): which AI crawlers can access our site and any access issues to fix
- brand_authority_assessment (string): our online brand authority across Wikipedia and Wikidata
- schema_assessment (string): our structured data implementation and what we should add
- technical_assessment (string): key technical GEO signals and recommendations
- sales_angles (array of 2-3 strings): how our GEO expertise can be positioned as a sales advantage
- quick_wins (array of 3-5 strings): immediate improvements we can make to boost our own GEO score
- platform_recommendations (array of 2-4 strings): AI platforms or directories where we should establish our presence

IMPORTANT: Write ALL analysis text values in {$language}.
Return ONLY valid JSON with those 10 keys, no extra text or markdown.
PROMPT;
    }

    /**
     * @param  array<string, mixed>  $rawData
     */
    private function buildUserPrompt(array $rawData, string $url): string
    {
        $lines = [
            "Analysing GEO readiness for: {$url}",
            '',
        ];

        $pageData = $rawData['page_data'] ?? [];
        if (! empty($pageData)) {
            $lines[] = '=== Page Data ===';
            $lines[] = 'Fetched: '.($pageData['fetched'] ? 'yes' : 'no');
            $lines[] = 'Word count: '.($pageData['word_count'] ?? 0);
            $lines[] = '';
        }

        $citability = $rawData['citability'] ?? [];
        if (! empty($citability)) {
            $lines[] = '=== Citability Score ===';
            $lines[] = 'Total: '.($citability['total'] ?? 0).'/100 (Grade: '.($citability['grade'] ?? 'F').')';
            $lines[] = 'Answer Block: '.($citability['answer_block'] ?? 0);
            $lines[] = 'Self-Containment: '.($citability['self_containment'] ?? 0);
            $lines[] = 'Structural Readability: '.($citability['structural_readability'] ?? 0);
            $lines[] = 'Statistical Density: '.($citability['statistical_density'] ?? 0);
            $lines[] = 'Uniqueness Signals: '.($citability['uniqueness_signals'] ?? 0);
            $lines[] = '';
        }

        $robotsData = $rawData['robots_txt'] ?? [];
        if (isset($robotsData['ai_crawlers'])) {
            $lines[] = '=== AI Crawler Access ===';
            $lines[] = 'robots.txt found: '.($robotsData['found'] ? 'yes' : 'no');
            foreach ($robotsData['ai_crawlers'] as $bot => $info) {
                $lines[] = "{$info['label']} ({$bot}): ".($info['status'] ?? 'unknown');
            }
            $lines[] = '';
        }

        $llmsData = $rawData['llms_txt'] ?? [];
        $lines[] = '=== llms.txt ===';
        $lines[] = 'llms.txt found: '.($llmsData['found'] ? 'yes' : 'no');
        $lines[] = '';

        $brandData = $rawData['brand_mentions'] ?? [];
        if (! empty($brandData['brand_name'])) {
            $lines[] = '=== Brand Authority ===';
            $lines[] = 'Brand: '.$brandData['brand_name'];
            $wiki = $brandData['wikipedia'] ?? [];
            $lines[] = 'Wikipedia: '.($wiki['found'] ? count($wiki['pages']).' pages found' : 'not found');
            $wikidata = $brandData['wikidata'] ?? [];
            $lines[] = 'Wikidata: '.($wikidata['found'] ? count($wikidata['entities']).' entities found' : 'not found');
            $lines[] = '';
        }

        $schema = $rawData['schema_markup'] ?? [];
        $lines[] = '=== Schema Markup ===';
        if (! empty($schema)) {
            $types = array_column($schema, 'type');
            $lines[] = 'Types found: '.implode(', ', $types);
        } else {
            $lines[] = 'No structured data found';
        }
        $lines[] = '';

        $tech = $rawData['technical_seo'] ?? [];
        if (! empty($tech)) {
            $lines[] = '=== Technical SEO ===';
            foreach ($tech as $key => $value) {
                $label = str_replace('_', ' ', $key);
                $lines[] = "{$label}: ".($value ? 'yes' : 'no');
            }
        }

        return implode("\n", $lines);
    }

    /**
     * @return array<string, mixed>
     */
    private function parseJsonResponse(string $raw): array
    {
        // Strip <think>...</think> blocks from reasoning models (e.g. DeepSeek, Qwen3)
        $cleaned = preg_replace('/<think>[\s\S]*?<\/think>/i', '', $raw);
        $cleaned = preg_replace('/^```(?:json)?\s*/m', '', $cleaned ?? $raw);
        $cleaned = preg_replace('/\s*```$/m', '', $cleaned ?? $raw);

        $data = json_decode(trim($cleaned ?? ''), true);

        if (! is_array($data)) {
            return [
                'geo_score' => 0,
                'ai_visibility_summary' => $raw,
                'citability_assessment' => null,
                'crawler_access_summary' => null,
                'brand_authority_assessment' => null,
                'schema_assessment' => null,
                'technical_assessment' => null,
                'sales_angles' => [],
                'quick_wins' => [],
                'platform_recommendations' => [],
            ];
        }

        return $data;
    }
}
