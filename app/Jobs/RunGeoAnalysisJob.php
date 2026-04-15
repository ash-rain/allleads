<?php

namespace App\Jobs;

use App\Ai\Agents\GeoAnalysisAgent;
use App\Models\AiSetting;
use App\Models\Lead;
use App\Models\LeadActivity;
use App\Models\LeadGeoAnalysis;
use App\Models\User;
use App\Notifications\GeoAnalysisFailedNotification;
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

    public function handle(): void
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

        $setting = $this->lead->business?->aiSettingOrCreate() ?? AiSetting::singleton();

        $result = (new GeoAnalysisAgent($this->lead, $this->lead->business))->prompt(
            $this->lead->website
                ? "Analyze {$this->lead->website} for GEO readiness. Brand: {$this->lead->title}"
                : "Analyze brand '{$this->lead->title}' for GEO readiness (no website).",
            provider: $setting->provider,
            model: $setting->model,
            timeout: $setting->timeout ?? 180,
        );

        $analysis->update([
            'status' => LeadGeoAnalysis::STATUS_COMPLETED,
            'result' => $result->toArray(),
            'provider' => $result->meta->provider,
            'model' => $result->meta->model,
            'completed_at' => now(),
        ]);

        LeadActivity::record($this->lead, 'geo_analysis_completed', [
            'provider' => $result->meta->provider,
            'model' => $result->meta->model,
            'has_website' => $this->lead->website !== null,
        ], $this->userId);
    }

    public function failed(\Throwable $e): void
    {
        Log::error('RunGeoAnalysisJob failed', [
            'lead_id' => $this->lead->id,
            'error' => $e->getMessage(),
        ]);

        LeadGeoAnalysis::where('lead_id', $this->lead->id)
            ->where('status', LeadGeoAnalysis::STATUS_PENDING)
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
}
