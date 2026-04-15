<?php

namespace App\Jobs;

use App\Ai\Agents\DraftRefinementAgent;
use App\Models\AiSetting;
use App\Models\EmailDraft;
use App\Models\LeadActivity;
use App\Models\User;
use App\Notifications\DraftFailedNotification;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class RefineDraftJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;

    public int $timeout = 60;

    public function __construct(
        public readonly EmailDraft $draft,
        public readonly string $instruction,
        public readonly int $userId,
    ) {}

    public function handle(): void
    {
        $business = $this->draft->lead?->business;
        $setting = $business ? $business->aiSettingOrCreate() : AiSetting::singleton();

        $result = (new DraftRefinementAgent($this->draft, $setting))->prompt(
            "Current draft:\n{$this->draft->body}\n\nRequested change:\n{$this->instruction}",
            provider: $setting->provider,
            model: $setting->model,
            timeout: $setting->timeout ?? 60,
        );

        // Save a version snapshot of the current body before overwriting.
        $this->draft->saveVersion($this->userId);

        $this->draft->update([
            'body' => $result['body'],
            'status' => 'draft',
        ]);

        $lead = $this->draft->lead;
        if ($lead) {
            LeadActivity::record($lead, 'draft_refined', [
                'draft_id' => $this->draft->id,
                'instruction' => $this->instruction,
            ], $this->userId);
        }
    }

    public function failed(\Throwable $e): void
    {
        Log::error('RefineDraftJob failed', [
            'draft_id' => $this->draft->id,
            'error' => $e->getMessage(),
        ]);

        $this->draft->update(['status' => 'failed', 'error' => $e->getMessage()]);

        $lead = $this->draft->lead;
        if ($lead) {
            LeadActivity::record($lead, 'draft_refinement_failed', [
                'draft_id' => $this->draft->id,
                'error' => $e->getMessage(),
            ], $this->userId);

            User::find($this->userId)?->notify(
                new DraftFailedNotification($lead, $e->getMessage())
            );
        }
    }
}
