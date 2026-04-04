<?php

namespace App\Jobs;

use App\Models\AiSetting;
use App\Models\BusinessSetting;
use App\Models\Lead;
use App\Models\LeadActivity;
use App\Models\LeadWebsiteAnalysis;
use App\Models\User;
use App\Notifications\WebsiteAnalysisFailedNotification;
use App\Services\Ai\AiProviderFactory;
use App\Services\Intelligence\WebsiteScraper;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class RunWebsiteAnalysisJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 2;

    public int $timeout = 120;

    public function __construct(
        public readonly Lead $lead,
        public readonly int $userId,
    ) {}

    public function handle(WebsiteScraper $scraper): void
    {
        $analysis = LeadWebsiteAnalysis::updateOrCreate(
            ['lead_id' => $this->lead->id],
            [
                'status' => LeadWebsiteAnalysis::STATUS_PENDING,
                'scraped_data' => null,
                'result' => null,
                'error_message' => null,
                'started_at' => now(),
                'completed_at' => null,
            ]
        );

        $scrapedData = [];
        if ($this->lead->website) {
            $scrapedData = $scraper->scrape($this->lead->website);
        }

        $analysis->update(['scraped_data' => $scrapedData]);

        $setting = AiSetting::singleton();
        $provider = AiProviderFactory::makeWithFallback($setting);

        $system = $this->buildSystemPrompt($setting->language ?? 'English');
        $user = $this->buildUserPrompt($scrapedData);

        $raw = $provider->complete($system, $user, [
            'model' => $setting->model,
            'temperature' => (float) $setting->temperature,
            'max_tokens' => (int) $setting->max_tokens,
            'timeout' => (int) $setting->timeout,
        ]);

        $result = $this->parseJsonResponse($raw);

        $analysis->update([
            'status' => LeadWebsiteAnalysis::STATUS_COMPLETED,
            'result' => $result,
            'provider' => $setting->provider,
            'model' => $setting->model,
            'completed_at' => now(),
        ]);

        LeadActivity::record($this->lead, 'website_analysis_completed', [
            'score' => $result['overall_score'] ?? null,
            'provider' => $setting->provider,
            'model' => $setting->model,
        ], $this->userId);
    }

    public function failed(\Throwable $e): void
    {
        Log::error('RunWebsiteAnalysisJob failed', [
            'lead_id' => $this->lead->id,
            'error' => $e->getMessage(),
        ]);

        LeadWebsiteAnalysis::where('lead_id', $this->lead->id)
            ->whereIn('status', [LeadWebsiteAnalysis::STATUS_PENDING])
            ->update([
                'status' => LeadWebsiteAnalysis::STATUS_FAILED,
                'error_message' => $e->getMessage(),
            ]);

        User::find($this->userId)?->notify(
            new WebsiteAnalysisFailedNotification($this->lead, $e->getMessage())
        );

        LeadActivity::record($this->lead, 'website_analysis_failed', [
            'error' => $e->getMessage(),
        ], $this->userId);
    }

    // ─── Helpers ────────────────────────────────────────────────────────────

    private function buildSystemPrompt(string $language): string
    {
        $businessSetting = BusinessSetting::singleton();
        $businessContext = $businessSetting->toPromptContext();
        $services = $businessSetting->key_services ?? 'web development services';

        return <<<PROMPT
You are an expert B2B sales intelligence analyst. You represent a specific business and must tailor your analysis to it.

{$businessContext}

Using the above as context for who WE are, analyse the PROSPECT's website data and return a structured JSON object with exactly these keys:

- business_overview (string): 2-3 sentence company summary of the prospect
- value_proposition (string): what the prospect sells and to whom
- target_market (string): customer segments the prospect serves
- revenue_model (string): how the prospect makes money
- competitive_position (string): prospect's market position vs competitors
- growth_signals (string): prospect's expansion indicators (hiring, new products, etc.)
- tech_maturity (string): prospect's digital sophistication assessment
- sales_angles (array of 3 strings): specific outreach angles WE can use based on OUR services and what WE can offer this prospect
- pain_points (array of strings): likely challenges this prospect has that OUR specific services can solve
- overall_score (integer 1-100): fit score for {$services}

IMPORTANT: Write ALL analysis text values in {$language}.
The sales_angles and pain_points must be grounded in our specific services and value proposition above — not generic.
Return ONLY valid JSON with those 10 keys, no extra text or markdown.
PROMPT;
    }

    /**
     * @param  array<string, mixed>  $scrapedData
     */
    private function buildUserPrompt(array $scrapedData): string
    {
        $lead = $this->lead;

        $lines = [
            "Business Name: {$lead->title}",
        ];

        if ($lead->category) {
            $lines[] = "Category: {$lead->category}";
        }

        if ($lead->website) {
            $lines[] = "Website: {$lead->website}";
        }

        if (! empty($scrapedData['company_name'])) {
            $lines[] = "Detected Company Name: {$scrapedData['company_name']}";
        }

        if (! empty($scrapedData['tech_stack'])) {
            $lines[] = 'Tech Stack: '.implode(', ', $scrapedData['tech_stack']);
        }

        if (! empty($scrapedData['social_links'])) {
            foreach ($scrapedData['social_links'] as $platform => $url) {
                $lines[] = ucfirst($platform).': '.$url;
            }
        }

        if (! empty($scrapedData['pricing_tiers'])) {
            $lines[] = 'Pricing Tiers: '.implode(', ', array_column($scrapedData['pricing_tiers'], 'name'));
        }

        if (! empty($scrapedData['job_postings'])) {
            $lines[] = 'Open Positions: '.implode(', ', array_slice($scrapedData['job_postings'], 0, 5));
        }

        if (! empty($scrapedData['contact_info'])) {
            foreach ($scrapedData['contact_info'] as $type => $value) {
                $lines[] = ucfirst($type).': '.$value;
            }
        }

        if (! empty($scrapedData['company_size_signals'])) {
            $lines[] = "Size Signal: {$scrapedData['company_size_signals']}";
        }

        if (! empty($scrapedData['team_members'])) {
            $names = array_slice(array_column($scrapedData['team_members'], 'name'), 0, 5);
            $lines[] = 'Team Members: '.implode(', ', $names);
        }

        if (! empty($scrapedData['page_text'])) {
            $lines[] = 'Homepage Text: '.mb_substr($scrapedData['page_text'], 0, 500);
        }

        return 'Analyse this business for B2B outreach:'."\n".implode("\n", $lines);
    }

    /**
     * @return array<string, mixed>
     */
    private function parseJsonResponse(string $raw): array
    {
        // Strip <think>...</think> blocks from reasoning models (e.g. DeepSeek)
        $cleaned = preg_replace('/<think>[\s\S]*?<\/think>/i', '', $raw);
        // Strip markdown code fences if present
        $cleaned = preg_replace('/^```(?:json)?\s*/m', '', $cleaned ?? $raw);
        $cleaned = preg_replace('/\s*```$/m', '', $cleaned ?? $raw);

        $decoded = json_decode(trim($cleaned ?? ''), true);

        if (! is_array($decoded)) {
            throw new \RuntimeException('AI returned invalid JSON: '.mb_substr($raw, 0, 200));
        }

        return $decoded;
    }
}
