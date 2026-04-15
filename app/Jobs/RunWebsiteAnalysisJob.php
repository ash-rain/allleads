<?php

namespace App\Jobs;

use App\Ai\Agents\WebsiteAnalysisAgent;
use App\Models\AiSetting;
use App\Models\Lead;
use App\Models\LeadActivity;
use App\Models\LeadWebsiteAnalysis;
use App\Models\User;
use App\Notifications\WebsiteAnalysisFailedNotification;
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

    public function handle(): void
    {
        $analysis = LeadWebsiteAnalysis::updateOrCreate(
            ['lead_id' => $this->lead->id],
            [
                'status' => LeadWebsiteAnalysis::STATUS_PENDING,
                'scraped_data' => [],
                'result' => null,
                'error_message' => null,
                'started_at' => now(),
                'completed_at' => null,
            ]
        );

        $setting = $this->lead->business?->aiSettingOrCreate() ?? AiSetting::singleton();

        $result = (new WebsiteAnalysisAgent($this->lead, $this->lead->business))->prompt(
            $this->lead->website
                ? "Analyze the website {$this->lead->website} for B2B sales intelligence."
                : "Analyze business '{$this->lead->title}' (no website) for B2B sales intelligence.",
            provider: $setting->provider,
            model: $setting->model,
            timeout: $setting->timeout ?? 120,
        );

        $analysis->update([
            'status' => LeadWebsiteAnalysis::STATUS_COMPLETED,
            'result' => $result->toArray(),
            'provider' => $result->meta->provider,
            'model' => $result->meta->model,
            'completed_at' => now(),
        ]);

        LeadActivity::record($this->lead, 'website_analysis_completed', [
            'score' => $result['overall_score'] ?? null,
            'provider' => $result->meta->provider,
            'model' => $result->meta->model,
        ], $this->userId);
    }

    public function failed(\Throwable $e): void
    {
        Log::error('RunWebsiteAnalysisJob failed', [
            'lead_id' => $this->lead->id,
            'error' => $e->getMessage(),
        ]);

        LeadWebsiteAnalysis::where('lead_id', $this->lead->id)
            ->where('status', LeadWebsiteAnalysis::STATUS_PENDING)
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
}
