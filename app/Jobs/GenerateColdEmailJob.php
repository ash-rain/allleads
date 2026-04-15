<?php

namespace App\Jobs;

use App\Ai\Agents\ColdEmailAgent;
use App\Models\AiSetting;
use App\Models\EmailCampaign;
use App\Models\EmailDraft;
use App\Models\EmailThread;
use App\Models\Lead;
use App\Models\LeadActivity;
use App\Models\User;
use App\Notifications\DraftFailedNotification;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class GenerateColdEmailJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;

    public int $timeout = 300;

    public function __construct(
        public readonly Lead $lead,
        public readonly EmailThread $thread,
        public readonly ?EmailCampaign $campaign,
        public readonly int $userId,
    ) {}

    public function handle(): void
    {
        $setting = AiSetting::singleton();

        $result = (new ColdEmailAgent($this->lead, $setting))->prompt(
            $this->buildUserPrompt(),
            provider: $setting->provider,
            model: $setting->model,
            timeout: $setting->timeout ?? 300,
        );

        EmailDraft::create([
            'lead_id' => $this->lead->id,
            'campaign_id' => $this->campaign?->id,
            'thread_id' => $this->thread->id,
            'subject' => $result['subject'],
            'body' => $result['body'],
            'status' => 'draft',
            'version' => 1,
        ]);

        LeadActivity::record($this->lead, 'draft_generated', [
            'thread_id' => $this->thread->id,
            'subject' => $result['subject'],
        ], $this->userId);
    }

    public function failed(\Throwable $e): void
    {
        Log::error('GenerateColdEmailJob failed', [
            'lead_id' => $this->lead->id,
            'error' => $e->getMessage(),
        ]);

        User::find($this->userId)?->notify(
            new DraftFailedNotification($this->lead, $e->getMessage())
        );

        LeadActivity::record($this->lead, 'draft_generation_failed', [
            'error' => $e->getMessage(),
        ], $this->userId);
    }

    // ─── Prompt Builder ─────────────────────────────────────────────────────

    private function buildUserPrompt(): string
    {
        $lead = $this->lead;
        $parts = ["Business name: {$lead->title}"];

        if ($lead->category) {
            $parts[] = "Category: {$lead->category}";
        }
        if ($lead->review_rating) {
            $parts[] = "Google review rating: {$lead->review_rating}/5";
        }
        if ($lead->address) {
            $parts[] = "Location: {$lead->address}";
        }
        if ($lead->website) {
            $parts[] = "Website: {$lead->website}";
        } else {
            $parts[] = 'No website found.';
        }

        return 'Write a cold email for this lead:'."\n".implode("\n", $parts);
    }
}
