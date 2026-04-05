<?php

namespace App\Jobs;

use App\Models\AiSetting;
use App\Models\BusinessSetting;
use App\Models\Lead;
use App\Models\LeadActivity;
use App\Models\LeadGeoAnalysis;
use App\Models\User;
use App\Notifications\GeoAnalysisFailedNotification;
use App\Services\Ai\AiProviderFactory;
use App\Services\Intelligence\GeoAnalyzer;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class RunGeoAnalysisJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 2;

    public int $timeout = 180;

    public function __construct(
        public readonly Lead $lead,
        public readonly int $userId,
    ) {}

    public function handle(GeoAnalyzer $analyzer): void
    {
        $analysis = LeadGeoAnalysis::updateOrCreate(
            ['lead_id' => $this->lead->id],
            [
                'status' => LeadGeoAnalysis::STATUS_PENDING,
                'raw_data' => null,
                'result' => null,
                'error_message' => null,
                'started_at' => now(),
                'completed_at' => null,
            ]
        );

        $url = $this->lead->website;
        $brandName = $this->lead->title;

        $rawData = $url
            ? $analyzer->analyze($url, $brandName)
            : $analyzer->analyzeWithoutWebsite($brandName);

        $analysis->update(['raw_data' => $rawData]);

        $setting = AiSetting::singleton();
        $provider = AiProviderFactory::makeWithFallback($setting);

        $system = $this->buildSystemPrompt($setting->language ?? 'English');
        $user = $this->buildUserPrompt($rawData, $url);

        $raw = $provider->complete($system, $user, [
            'model' => $setting->model,
            'temperature' => (float) $setting->temperature,
            'max_tokens' => (int) $setting->max_tokens,
            'timeout' => (int) $setting->timeout,
        ]);

        $result = $this->parseJsonResponse($raw);

        $analysis->update([
            'status' => LeadGeoAnalysis::STATUS_COMPLETED,
            'result' => $result,
            'provider' => $setting->provider,
            'model' => $setting->model,
            'completed_at' => now(),
        ]);

        LeadActivity::record($this->lead, 'geo_analysis_completed', [
            'provider' => $setting->provider,
            'model' => $setting->model,
            'has_website' => $url !== null,
        ], $this->userId);
    }

    public function failed(\Throwable $e): void
    {
        Log::error('RunGeoAnalysisJob failed', [
            'lead_id' => $this->lead->id,
            'error' => $e->getMessage(),
        ]);

        LeadGeoAnalysis::where('lead_id', $this->lead->id)
            ->whereIn('status', [LeadGeoAnalysis::STATUS_PENDING])
            ->update([
                'status' => LeadGeoAnalysis::STATUS_FAILED,
                'error_message' => $e->getMessage(),
            ]);

        User::find($this->userId)?->notify(
            new GeoAnalysisFailedNotification($this->lead, $e->getMessage())
        );

        LeadActivity::record($this->lead, 'geo_analysis_failed', [
            'error' => $e->getMessage(),
        ], $this->userId);
    }

    // ─── Helpers ────────────────────────────────────────────────────────────

    private function buildSystemPrompt(string $language): string
    {
        $businessSetting = BusinessSetting::singleton();
        $businessContext = $businessSetting->toPromptContext();
        $services = $businessSetting->key_services ?? 'our services';

        return <<<PROMPT
You are a GEO (Generative Engine Optimization) specialist for a B2B sales team. You represent a specific business and must tailor your analysis to find commercial opportunities.

{$businessContext}

Using the above as context for who WE are, analyse the provided website crawl data and return a structured JSON object with exactly these keys:

- geo_score (integer 1-100): overall GEO readiness score. Weight: citability 35%, crawler access 25%, brand authority 20%, schema markup 10%, technical SEO 10%
- ai_visibility_summary (string): 2-3 sentence summary of how visible this business is to AI systems right now
- citability_assessment (string): analysis of content quality for AI citation — strengths and weaknesses
- crawler_access_summary (string): summary of which AI crawlers can access the site and implications
- brand_authority_assessment (string): assessment of online brand authority based on Wikipedia/Wikidata presence
- schema_assessment (string): evaluation of structured data implementation and recommendations
- technical_assessment (string): key technical SEO signals for AI discoverability
- sales_angles (array of 2-3 strings): specific pitch angles WE can use with this lead based on their GEO gaps and OUR services ({$services})
- quick_wins (array of 3-5 strings): specific, actionable GEO improvements this business could implement quickly
- platform_recommendations (array of 2-4 strings): specific AI platforms or directories where this business should establish presence

IMPORTANT: Write ALL analysis text values in {$language}.
Return ONLY valid JSON with those 10 keys, no extra text or markdown.
PROMPT;
    }

    /**
     * @param  array<string, mixed>  $rawData
     */
    private function buildUserPrompt(array $rawData, ?string $url): string
    {
        $lines = [
            $url ? "Analysing GEO readiness for: {$url}" : 'Analysing brand-only GEO readiness (no website)',
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
