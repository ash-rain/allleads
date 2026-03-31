<?php

namespace App\Services\Intelligence;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class WebsiteScraper
{
    private const TIMEOUT = 10;

    private const SUBPAGES = ['/about', '/team', '/pricing', '/careers', '/contact'];

    private const TECH_SIGNATURES = [
        'WordPress' => ['wp-content', 'wp-includes', 'generator" content="WordPress'],
        'Shopify' => ['cdn.shopify.com', 'Shopify.theme', 'myshopify.com'],
        'Wix' => ['wix.com', 'wixstatic.com', '_wix_'],
        'Squarespace' => ['squarespace.com', 'squarespace-cdn.com'],
        'Webflow' => ['webflow.io', 'webflow.com/'],
        'React' => ['react.development.js', 'react.production.min.js', '__REACT'],
        'Vue' => ['vue.min.js', 'vue.global.js', '__VUE'],
        'Angular' => ['angular.min.js', 'ng-version='],
        'Next.js' => ['_next/static', '__NEXT_DATA__'],
        'Laravel' => ['laravel_session', 'csrf-token" content='],
        'Drupal' => ['Drupal.settings', '/sites/default/files'],
        'Magento' => ['Magento_', '/mage/'],
    ];

    /**
     * Scrape a website and return structured extracted data.
     *
     * @return array<string, mixed>
     */
    public function scrape(string $url): array
    {
        $homepage = $this->fetchPage($url);

        $data = [
            'company_name' => $this->extractCompanyName($homepage),
            'tech_stack' => $homepage ? $this->detectTechStack($homepage) : [],
            'social_links' => $homepage ? $this->extractSocialLinks($homepage) : [],
            'team_members' => [],
            'pricing_tiers' => [],
            'job_postings' => [],
            'contact_info' => $homepage ? $this->extractContactInfo($homepage) : [],
            'company_size_signals' => $homepage ? $this->extractCompanySizeSignals($homepage) : null,
        ];

        foreach (self::SUBPAGES as $subpage) {
            $this->rateLimitDelay();

            $subpageUrl = rtrim($url, '/').$subpage;
            $content = $this->fetchPage($subpageUrl);

            if (! $content) {
                continue;
            }

            match ($subpage) {
                '/team', '/about' => $data['team_members'] = array_merge(
                    $data['team_members'],
                    $this->extractTeamMembers($content)
                ),
                '/pricing' => $data['pricing_tiers'] = $this->extractPricingTiers($content),
                '/careers', '/jobs' => $data['job_postings'] = array_merge(
                    $data['job_postings'],
                    $this->extractJobPostings($content)
                ),
                '/contact' => $data['contact_info'] = array_merge(
                    $data['contact_info'],
                    $this->extractContactInfo($content)
                ),
                default => null,
            };
        }

        return $data;
    }

    private function fetchPage(string $url): ?string
    {
        // Only allow HTTP/HTTPS schemes for security
        $scheme = parse_url($url, PHP_URL_SCHEME);
        if (! in_array($scheme, ['http', 'https'], true)) {
            return null;
        }

        try {
            $response = Http::timeout(self::TIMEOUT)
                ->withHeaders(['User-Agent' => 'AllLeads-Analyzer/1.0'])
                ->get($url);

            if ($response->successful()) {
                return $response->body();
            }
        } catch (\Throwable $e) {
            Log::debug('WebsiteScraper failed to fetch page', [
                'url' => $url,
                'error' => $e->getMessage(),
            ]);
        }

        return null;
    }

    private function extractCompanyName(?string $html): ?string
    {
        if (! $html) {
            return null;
        }

        // Try OG site_name first
        if (preg_match('/<meta[^>]+property=["\']og:site_name["\'][^>]+content=["\']([^"\']+)["\']/', $html, $m)) {
            return trim($m[1]);
        }

        // Try title tag
        if (preg_match('/<title[^>]*>([^<|]+)/', $html, $m)) {
            return trim(explode('|', $m[1])[0]);
        }

        // Try h1
        if (preg_match('/<h1[^>]*>([^<]+)<\/h1>/', $html, $m)) {
            return trim(strip_tags($m[1]));
        }

        return null;
    }

    /**
     * @return list<string>
     */
    private function detectTechStack(string $html): array
    {
        $detected = [];

        foreach (self::TECH_SIGNATURES as $tech => $signatures) {
            foreach ($signatures as $signature) {
                if (str_contains($html, $signature)) {
                    $detected[] = $tech;
                    break;
                }
            }
        }

        return $detected;
    }

    /**
     * @return array<string, string>
     */
    private function extractSocialLinks(string $html): array
    {
        $links = [];
        $socialPatterns = [
            'linkedin' => '/https?:\/\/(www\.)?linkedin\.com\/(?:company|in)\/[a-zA-Z0-9_-]+/i',
            'twitter' => '/https?:\/\/(www\.)?(?:twitter|x)\.com\/[a-zA-Z0-9_]+/i',
            'facebook' => '/https?:\/\/(www\.)?facebook\.com\/[a-zA-Z0-9_.]+/i',
            'instagram' => '/https?:\/\/(www\.)?instagram\.com\/[a-zA-Z0-9_.]+/i',
        ];

        foreach ($socialPatterns as $platform => $pattern) {
            if (preg_match($pattern, $html, $m)) {
                $links[$platform] = $m[0];
            }
        }

        return $links;
    }

    /**
     * @return list<array<string, string>>
     */
    private function extractTeamMembers(string $html): array
    {
        $members = [];

        // Simple heuristic: look for name + title patterns near common team section markup
        preg_match_all(
            '/class="[^"]*(?:team|member|staff|person)[^"]*"[^>]*>.*?<h[2-4][^>]*>([^<]{3,60})<\/h[2-4]>.*?(?:<p[^>]*>([^<]{3,80})<\/p>)?/si',
            $html,
            $matches,
            PREG_SET_ORDER
        );

        foreach (array_slice($matches, 0, 10) as $match) {
            $name = trim(strip_tags($match[1] ?? ''));
            $title = trim(strip_tags($match[2] ?? ''));

            if ($name) {
                $members[] = array_filter(['name' => $name, 'title' => $title]);
            }
        }

        return $members;
    }

    /**
     * @return list<array<string, string>>
     */
    private function extractPricingTiers(string $html): array
    {
        $tiers = [];

        // Look for pricing plan names with price indicators
        preg_match_all(
            '/(?:<h[2-4][^>]*>([^<]{2,50})<\/h[2-4]>).*?(?:\$|€|£|USD|EUR)\s*(\d+(?:\.\d{2})?)/si',
            $html,
            $matches,
            PREG_SET_ORDER
        );

        foreach (array_slice($matches, 0, 5) as $match) {
            $name = trim(strip_tags($match[1] ?? ''));
            $price = trim($match[2] ?? '');

            if ($name && $price) {
                $tiers[] = ['name' => $name, 'price' => $price];
            }
        }

        return $tiers;
    }

    /**
     * @return list<string>
     */
    private function extractJobPostings(string $html): array
    {
        $jobs = [];

        // Look for job listing patterns
        preg_match_all(
            '/<(?:h[2-5]|li|p)[^>]*class="[^"]*(?:job|position|role|opening)[^"]*"[^>]*>([^<]{5,100})<\/(?:h[2-5]|li|p)>/i',
            $html,
            $matches
        );

        foreach (array_slice($matches[1] ?? [], 0, 10) as $job) {
            $clean = trim(strip_tags($job));
            if ($clean) {
                $jobs[] = $clean;
            }
        }

        return array_values(array_unique($jobs));
    }

    /**
     * @return array<string, string>
     */
    private function extractContactInfo(string $html): array
    {
        $info = [];

        // Email
        if (preg_match('/\b[a-zA-Z0-9._%+\-]+@[a-zA-Z0-9.\-]+\.[a-zA-Z]{2,}\b/', $html, $m)) {
            $info['email'] = $m[0];
        }

        // Phone (international or local formats)
        if (preg_match('/(?:\+?\d[\d\s\-().]{7,20}\d)/', $html, $m)) {
            $info['phone'] = trim($m[0]);
        }

        return $info;
    }

    private function extractCompanySizeSignals(?string $html): ?string
    {
        if (! $html) {
            return null;
        }

        // Look for "team of X", "X employees", "X+ people" patterns
        $patterns = [
            '/team\s+of\s+(\d+(?:\+|k)?(?:\s+people)?)/i',
            '/(\d+(?:\+|k)?)\s+employees/i',
            '/(\d+(?:\+|k)?)\s+people/i',
            '/(\d+(?:\+|k)?)\s+staff/i',
        ];

        foreach ($patterns as $pattern) {
            if (preg_match($pattern, strip_tags($html), $m)) {
                return $m[0];
            }
        }

        return null;
    }

    private function rateLimitDelay(): void
    {
        usleep(random_int(200000, 500000)); // 200-500ms
    }
}
