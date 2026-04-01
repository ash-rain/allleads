<?php

namespace App\Livewire;

use App\Jobs\GenerateColdEmailJob;
use App\Models\EmailDraft;
use App\Models\EmailMessage;
use App\Models\EmailThread;
use App\Models\Lead;
use Filament\Notifications\Notification;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;
use Livewire\Component;

class ConversationView extends Component
{
    public int $leadId;

    public bool $showDraftEditor = false;

    public ?int $selectedDraftId = null;

    public bool $showManualReply = false;

    public string $manualReplyBody = '';

    public string $manualReplySubject = '';

    public int $awaitingDraftSince = 0;

    public function mount(int $leadId): void
    {
        $this->leadId = $leadId;
    }

    public function openDraftEditor(int $draftId): void
    {
        $this->selectedDraftId = $draftId;
        $this->showDraftEditor = true;
        $this->showManualReply = false;
    }

    public function closeDraftEditor(): void
    {
        $this->showDraftEditor = false;
        $this->selectedDraftId = null;
    }

    public function openManualReply(): void
    {
        $this->showManualReply = true;
        $this->showDraftEditor = false;
    }

    public function saveManualReply(): void
    {
        $this->validate([
            'manualReplyBody' => 'required|string|min:1',
            'manualReplySubject' => 'nullable|string|max:255',
        ]);

        $thread = EmailThread::firstOrCreate(
            ['lead_id' => $this->leadId, 'status' => 'open'],
            ['thread_key' => 'manual-'.$this->leadId]
        );

        EmailMessage::create([
            'thread_id' => $thread->id,
            'role' => 'outbound',
            'subject' => $this->manualReplySubject,
            'body' => $this->manualReplyBody,
            'sender' => Auth::user()->email ?? '',
            'source' => 'manual',
            'sent_at' => now(),
        ]);

        $this->reset(['manualReplyBody', 'manualReplySubject', 'showManualReply']);
    }

    public function deleteDraft(int $draftId): void
    {
        $draft = EmailDraft::findOrFail($draftId);
        Gate::authorize('delete', $draft);

        $draft->delete();

        if ($this->selectedDraftId === $draftId) {
            $this->showDraftEditor = false;
            $this->selectedDraftId = null;
        }

        Notification::make()
            ->title(__('emails.draft_deleted'))
            ->success()
            ->send();
    }

    public function generateAiDraft(): void
    {
        $lead = Lead::findOrFail($this->leadId);
        $thread = EmailThread::firstOrCreate(
            ['lead_id' => $this->leadId, 'status' => 'open'],
            ['thread_key' => 'ai-'.$this->leadId.'-'.now()->timestamp]
        );

        GenerateColdEmailJob::dispatch($lead, $thread, null, Auth::id());

        $this->awaitingDraftSince = now()->timestamp;

        Notification::make()
            ->title(__('emails.action_generate'))
            ->body(__('emails.generate_queued'))
            ->info()
            ->send();
    }

    public function render(): View
    {
        $threads = EmailThread::query()
            ->where('lead_id', $this->leadId)
            ->with(['messages' => fn ($q) => $q->orderBy('created_at')])
            ->orderByDesc('created_at')
            ->get();

        $drafts = EmailDraft::query()
            ->where('lead_id', $this->leadId)
            ->whereIn('status', ['draft', 'queued_for_send'])
            ->with('versions')
            ->latest()
            ->get();

        // Reset the generating spinner once a new draft appears or the wait has exceeded 5 minutes.
        if ($this->awaitingDraftSince > 0) {
            $since = Carbon::createFromTimestamp($this->awaitingDraftSince);
            $newDraftArrived = $drafts->where('created_at', '>=', $since)->isNotEmpty();
            $timedOut = now()->diffInSeconds($since) > 300;

            if ($newDraftArrived || $timedOut) {
                $this->awaitingDraftSince = 0;
            }
        }

        return view('livewire.conversation-view', [
            'threads' => $threads,
            'drafts' => $drafts,
            'generating' => $this->awaitingDraftSince > 0,
        ]);
    }
}
