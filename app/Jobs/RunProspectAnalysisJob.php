<?php

namespace App\Jobs;

use App\Models\AiSetting;
use App\Models\Lead;
use App\Models\LeadProspectAnalysis;
use App\Models\User;
use App\Notifications\ProspectAnalysisFailedNotification;
use App\Services\Ai\AiProviderFactory;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class RunProspectAnalysisJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 2;

    public int $timeout = 120;

    public function __construct(
        public readonly Lead $lead,
        public readonly int $userId,
    ) {}

    public function handle(): void
    {
        $analysis = LeadProspectAnalysis::updateOrCreate(
            ['lead_id' => $this->lead->id],
            [
                'status' => LeadProspectAnalysis::STATUS_PENDING,
                'result' => null,
                'error_message' => null,
                'started_at' => now(),
                'completed_at' => null,
            ]
        );

        $websiteContent = $this->fetchWebsiteContent();

        $setting = AiSetting::singleton();
        $provider = AiProviderFactory::makeWithFallback($setting);

        $system = $this->buildSystemPrompt();
        $user = $this->buildUserPrompt($websiteContent);

        $raw = $provider->complete($system, $user, [
            'model' => $setting->model,
            'temperature' => (float) $setting->temperature,
            'max_tokens' => (int) $setting->max_tokens,
        ]);

        $result = $this->parseJsonResponse($raw);

        $analysis->update([
            'status' => LeadProspectAnalysis::STATUS_COMPLETED,
            'result' => $result,
            'provider' => $setting->provider,
            'model' => $setting->model,
            'completed_at' => now(),
        ]);
    }

    public function failed(\Throwable $e): void
    {
        Log::error('RunProspectAnalysisJob failed', [
            'lead_id' => $this->lead->id,
            'error' => $e->getMessage(),
        ]);

        LeadProspectAnalysis::where('lead_id', $this->lead->id)
            ->whereIn('status', [LeadProspectAnalysis::STATUS_PENDING])
            ->update([
                'status' => LeadProspectAnalysis::STATUS_FAILED,
                'error_message' => $e->getMessage(),
            ]);

        User::find($this->userId)?->notify(
            new ProspectAnalysisFailedNotification($this->lead, $e->getMessage())
        );
    }

    // ─── Helpers ────────────────────────────────────────────────────────────

    private function fetchWebsiteContent(): ?string
    {
        if (! $this->lead->website) {
            return null;
        }

        try {
            $response = Http::timeout(10)->get($this->lead->website);

            if ($response->successful()) {
                // Strip HTML tags and collapse whitespace; keep first 3000 chars
                $text = strip_tags($response->body());
                $text = preg_replace('/\s+/', ' ', $text);

                return mb_substr(trim($text), 0, 3000);
            }
        } catch (\Throwable) {
            // Silent fail — website scraping is best-effort
        }

        return null;
    }

    private function buildSystemPrompt(): string
    {
        return <<<'PROMPT'
You are an expert B2B sales intelligence analyst. Analyse the prospect and return a structured JSON object with exactly these keys:

- prospect_score (integer 1-100): overall fit score
- company_fit (string): 2-3 sentence assessment of why this company is a good prospect
- contact_intel (string): key insights about the contact/decision-maker based on available data
- opportunity (string): the main business opportunity — what pain point or gap can be solved
- competitive_intel (string): likely existing solutions or competitors they might be using
- outreach_strategy (string): recommended first-contact approach and suggested opening line

Return ONLY valid JSON with those 6 keys, no extra text or markdown.
PROMPT;
    }

    private function buildUserPrompt(?string $websiteContent): string
    {
        $lead = $this->lead;

        $lines = [
            "Business Name: {$lead->title}",
        ];

        if ($lead->category) {
            $lines[] = "Category: {$lead->category}";
        }
        if ($lead->address) {
            $lines[] = "Address: {$lead->address}";
        }
        if ($lead->phone) {
            $lines[] = "Phone: {$lead->phone}";
        }
        if ($lead->email) {
            $lines[] = "Email: {$lead->email}";
        }
        if ($lead->website) {
            $lines[] = "Website: {$lead->website}";
        }
        if ($lead->review_rating) {
            $lines[] = "Review Rating: {$lead->review_rating}";
        }

        if ($websiteContent) {
            $lines[] = '';
            $lines[] = 'Website Content (first 3000 chars):';
            $lines[] = $websiteContent;
        }

        return implode("\n", $lines);
    }

    /** @return array<string, mixed> */
    private function parseJsonResponse(string $raw): array
    {
        // Strip markdown code fences if present
        $clean = preg_replace('/^```(?:json)?\s*/i', '', trim($raw));
        $clean = preg_replace('/\s*```$/', '', $clean);

        $decoded = json_decode(trim($clean), true);

        if (! is_array($decoded)) {
            throw new \RuntimeException("AI returned invalid JSON: {$raw}");
        }

        return $decoded;
    }
}
