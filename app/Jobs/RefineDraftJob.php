<?php

namespace App\Jobs;

use App\Models\AiSetting;
use App\Models\EmailDraft;
use App\Models\User;
use App\Notifications\DraftFailedNotification;
use App\Services\Ai\AiProviderFactory;
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
        $setting = AiSetting::singleton();
        $provider = AiProviderFactory::makeWithFallback($setting);

        $system = $this->buildSystemPrompt();
        $user = $this->buildUserPrompt();

        $refined = $provider->complete($system, $user, [
            'model' => $setting->model,
            'temperature' => (float) $setting->temperature,
            'max_tokens' => (int) $setting->max_tokens,
        ]);

        // Save a version snapshot of the current body before overwriting.
        $this->draft->saveVersion($this->userId);

        $this->draft->update([
            'body' => $refined,
            'status' => 'draft',
        ]);
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
            User::find($this->userId)?->notify(
                new DraftFailedNotification($lead, $e->getMessage())
            );
        }
    }

    // ─── Prompt Builders ────────────────────────────────────────────────────

    private function buildSystemPrompt(): string
    {
        $setting = AiSetting::singleton();
        $language = $setting->language ?? 'English';
        $tone = $setting->tone ?? 'professional';

        return <<<PROMPT
You are an expert cold email copywriter. You are editing an existing cold email draft.
Language: {$language}. Tone: {$tone}.
Apply ONLY the requested changes. Keep everything else as-is. Return only the updated email body — no subject line, no commentary.
PROMPT;
    }

    private function buildUserPrompt(): string
    {
        return <<<PROMPT
Current draft:
{$this->draft->body}

Requested change:
{$this->instruction}
PROMPT;
    }
}
