<?php

namespace App\Livewire;

use App\Jobs\RefineDraftJob;
use App\Jobs\SendEmailJob;
use App\Models\EmailDraft;
use App\Models\EmailDraftVersion;
use Illuminate\Support\Facades\Gate;
use Livewire\Component;

class DraftEditor extends Component
{
    // ─── Props ──────────────────────────────────────────────────────────────

    public int $draftId;

    // ─── State ──────────────────────────────────────────────────────────────

    public ?EmailDraft $draft = null;

    public string $subject = '';

    public string $body = '';

    public string $refineInput = '';

    public bool $refining = false;

    public bool $sending = false;

    public bool $showVersions = false;

    public array $versions = [];

    public ?string $statusMessage = null;

    public string $statusType = 'success'; // 'success' | 'error'

    // ─── Lifecycle ──────────────────────────────────────────────────────────

    public function mount(int $draftId): void
    {
        $this->draftId = $draftId;
        $this->loadDraft();
    }

    public function loadDraft(): void
    {
        $this->draft = EmailDraft::with('versions')->findOrFail($this->draftId);
        $this->subject = $this->draft->subject ?? '';
        $this->body = $this->draft->body ?? '';
        $this->versions = $this->draft->versions
            ->map(fn (EmailDraftVersion $v) => [
                'version' => $v->version,
                'created_at' => $v->created_at?->diffForHumans(),
            ])
            ->toArray();
    }

    // ─── Editing ────────────────────────────────────────────────────────────

    public function save(): void
    {
        Gate::authorize('update', $this->draft);

        $validated = $this->validate([
            'subject' => ['required', 'string', 'max:255'],
            'body' => ['required', 'string'],
        ]);

        $this->draft->update([
            'subject' => $validated['subject'],
            'body' => $validated['body'],
        ]);

        $this->flash('Saved.', 'success');
    }

    // ─── AI Refinement ──────────────────────────────────────────────────────

    public function refine(): void
    {
        Gate::authorize('update', $this->draft);

        $validated = $this->validate([
            'refineInput' => ['required', 'string', 'max:500'],
        ]);

        $this->refining = true;

        RefineDraftJob::dispatch($this->draft, $validated['refineInput'], auth()->id());

        $this->refineInput = '';
        $this->flash(__('ai.refine_dispatched'), 'success');

        // Reset flag; draft will be reloaded via polling or manual refresh.
        $this->refining = false;
    }

    // ─── Version History ────────────────────────────────────────────────────

    public function toggleVersions(): void
    {
        $this->showVersions = ! $this->showVersions;
    }

    public function restoreVersion(int $versionNumber): void
    {
        Gate::authorize('update', $this->draft);

        $this->draft->restoreVersion($versionNumber);
        $this->loadDraft();
        $this->flash("Restored to version {$versionNumber}.", 'success');
    }

    // ─── Send ────────────────────────────────────────────────────────────────

    public function send(): void
    {
        Gate::authorize('update', $this->draft);

        if ($this->draft->status === 'sent') {
            $this->flash('This draft has already been sent.', 'error');

            return;
        }

        // Persist latest edits before dispatching.
        $this->draft->update([
            'subject' => $this->subject,
            'body' => $this->body,
        ]);

        $this->sending = true;

        SendEmailJob::dispatch($this->draft, auth()->id());

        $this->flash('Email queued for sending.', 'success');
        $this->sending = false;
    }

    // ─── Render ─────────────────────────────────────────────────────────────

    public function render()
    {
        return view('livewire.draft-editor');
    }

    // ─── Helpers ─────────────────────────────────────────────────────────────

    private function flash(string $message, string $type = 'success'): void
    {
        $this->statusMessage = $message;
        $this->statusType = $type;
    }
}
