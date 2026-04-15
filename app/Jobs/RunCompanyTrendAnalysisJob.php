<?php

namespace App\Jobs;

use App\Ai\Agents\TrendAnalysisAgent;
use App\Models\AiSetting;
use App\Models\Business;
use App\Models\TrendAnalysis;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class RunCompanyTrendAnalysisJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 2;

    public int $timeout = 120;

    public function __construct(
        public readonly string $topic,
        public readonly int $userId,
        public readonly ?Business $business = null,
    ) {}

    public function handle(): void
    {
        $analysis = TrendAnalysis::create([
            'user_id' => $this->userId,
            'topic' => $this->topic,
            'status' => TrendAnalysis::STATUS_PENDING,
            'started_at' => now(),
        ]);

        $setting = $this->business?->aiSettingOrCreate() ?? AiSetting::singleton();

        $result = (new TrendAnalysisAgent($this->topic, $this->business))->prompt(
            "Analyze market trends for: {$this->topic}",
            provider: $setting->provider,
            model: $setting->model,
            timeout: $setting->timeout ?? 120,
        );

        $analysis->update([
            'status' => TrendAnalysis::STATUS_COMPLETED,
            'result' => $result->toArray(),
            'provider' => $result->meta->provider,
            'model' => $result->meta->model,
            'completed_at' => now(),
        ]);
    }

    public function failed(\Throwable $e): void
    {
        Log::error('RunCompanyTrendAnalysisJob failed', [
            'topic' => $this->topic,
            'error' => $e->getMessage(),
        ]);

        TrendAnalysis::where('user_id', $this->userId)
            ->where('topic', $this->topic)
            ->where('status', TrendAnalysis::STATUS_PENDING)
            ->update([
                'status' => TrendAnalysis::STATUS_FAILED,
                'error_message' => $e->getMessage(),
            ]);
    }
}
