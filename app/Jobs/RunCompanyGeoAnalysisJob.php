<?php

namespace App\Jobs;

use App\Ai\Agents\GeoAnalysisAgent;
use App\Models\AiSetting;
use App\Models\Business;
use App\Models\GeoAnalysis;
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
        public readonly ?Business $business = null,
    ) {}

    public function handle(): void
    {
        $analysis = GeoAnalysis::create([
            'user_id' => $this->userId,
            'url' => $this->url,
            'status' => GeoAnalysis::STATUS_PENDING,
            'started_at' => now(),
        ]);

        $brandName = $this->business?->name ?? parse_url($this->url, PHP_URL_HOST) ?? $this->url;

        $setting = AiSetting::singleton();

        $result = (new GeoAnalysisAgent($this->url, $this->business))->prompt(
            "Analyze {$this->url} for GEO readiness. Brand: {$brandName}",
            provider: $setting->provider,
            model: $setting->model,
            timeout: $setting->timeout ?? 180,
        );

        $analysis->update([
            'status' => GeoAnalysis::STATUS_COMPLETED,
            'result' => $result->toArray(),
            'provider' => $result->meta->provider,
            'model' => $result->meta->model,
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
            ->where('status', GeoAnalysis::STATUS_PENDING)
            ->update([
                'status' => GeoAnalysis::STATUS_FAILED,
                'error_message' => $e->getMessage(),
            ]);
    }
}
