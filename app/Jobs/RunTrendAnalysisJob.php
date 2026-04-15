<?php

namespace App\Jobs;

use App\Ai\Agents\TrendAnalysisAgent;
use App\Models\AiSetting;
use App\Models\Lead;
use App\Models\LeadActivity;
use App\Models\LeadTrendAnalysis;
use App\Models\User;
use App\Notifications\TrendAnalysisFailedNotification;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class RunTrendAnalysisJob implements ShouldQueue
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
        $analysis = LeadTrendAnalysis::updateOrCreate(
            ['lead_id' => $this->lead->id],
            [
                'status' => LeadTrendAnalysis::STATUS_PENDING,
                'raw_data' => null,
                'result' => null,
                'error_message' => null,
                'started_at' => now(),
                'completed_at' => null,
            ]
        );

        $topic = $analysis->topic ?? trim(implode(' ', array_filter([
            $this->lead->title,
            $this->lead->category,
        ])));

        $setting = AiSetting::singleton();

        $result = (new TrendAnalysisAgent($this->lead, $this->lead->business))->prompt(
            "Analyze market trends for: {$topic}",
            provider: $setting->provider,
            model: $setting->model,
            timeout: $setting->timeout ?? 120,
        );

        $analysis->update([
            'topic' => $topic,
            'status' => LeadTrendAnalysis::STATUS_COMPLETED,
            'result' => $result->toArray(),
            'provider' => $result->meta->provider,
            'model' => $result->meta->model,
            'completed_at' => now(),
        ]);

        LeadActivity::record($this->lead, 'trend_analysis_completed', [
            'topic' => $topic,
            'provider' => $result->meta->provider,
            'model' => $result->meta->model,
        ], $this->userId);
    }

    public function failed(\Throwable $e): void
    {
        Log::error('RunTrendAnalysisJob failed', [
            'lead_id' => $this->lead->id,
            'error' => $e->getMessage(),
        ]);

        LeadTrendAnalysis::where('lead_id', $this->lead->id)
            ->where('status', LeadTrendAnalysis::STATUS_PENDING)
            ->update([
                'status' => LeadTrendAnalysis::STATUS_FAILED,
                'error_message' => $e->getMessage(),
            ]);

        User::find($this->userId)?->notify(
            new TrendAnalysisFailedNotification($this->lead, $e->getMessage())
        );

        LeadActivity::record($this->lead, 'trend_analysis_failed', [
            'error' => $e->getMessage(),
        ], $this->userId);
    }
}
