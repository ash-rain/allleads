<?php

namespace App\Jobs;

use App\Ai\Agents\ProspectScoringAgent;
use App\Models\AiSetting;
use App\Models\Lead;
use App\Models\LeadActivity;
use App\Models\LeadProspectAnalysis;
use App\Models\User;
use App\Notifications\ProspectAnalysisFailedNotification;
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

        $setting = $this->lead->business?->aiSettingOrCreate() ?? AiSetting::singleton();

        $result = (new ProspectScoringAgent($this->lead, $this->lead->business))->prompt(
            $this->buildUserPrompt(),
            provider: $setting->provider,
            model: $setting->model,
            timeout: $setting->timeout ?? 120,
        );

        $analysis->update([
            'status' => LeadProspectAnalysis::STATUS_COMPLETED,
            'result' => $result->toArray(),
            'provider' => $result->meta->provider,
            'model' => $result->meta->model,
            'completed_at' => now(),
        ]);

        LeadActivity::record($this->lead, 'prospect_analysis_completed', [
            'score' => $result['prospect_score'] ?? null,
            'provider' => $result->meta->provider,
            'model' => $result->meta->model,
        ], $this->userId);
    }

    public function failed(\Throwable $e): void
    {
        Log::error('RunProspectAnalysisJob failed', [
            'lead_id' => $this->lead->id,
            'error' => $e->getMessage(),
        ]);

        LeadProspectAnalysis::where('lead_id', $this->lead->id)
            ->where('status', LeadProspectAnalysis::STATUS_PENDING)
            ->update([
                'status' => LeadProspectAnalysis::STATUS_FAILED,
                'error_message' => $e->getMessage(),
            ]);

        User::find($this->userId)?->notify(
            new ProspectAnalysisFailedNotification($this->lead, $e->getMessage())
        );

        LeadActivity::record($this->lead, 'prospect_analysis_failed', [
            'error' => $e->getMessage(),
        ], $this->userId);
    }

    // ─── Helpers ────────────────────────────────────────────────────────────

    private function buildUserPrompt(): string
    {
        $lead = $this->lead;
        $websiteContent = $this->fetchWebsiteContent();

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
}
