<?php

namespace App\Livewire;

use App\Jobs\GenerateColdEmailJob;
use App\Models\EmailDraft;
use App\Models\EmailMessage;
use App\Models\EmailThread;
use App\Models\Lead;
use Filament\Notifications\Notification;
use Illuminate\Support\Facades\Auth;
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

    public function generateAiDraft(): void
    {
        $lead = Lead::findOrFail($this->leadId);
        $thread = EmailThread::firstOrCreate(
            ['lead_id' => $this->leadId, 'status' => 'open'],
            ['thread_key' => 'ai-'.$this->leadId.'-'.now()->timestamp]
        );

        GenerateColdEmailJob::dispatch($lead, $thread, null, Auth::id());

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

        return view('livewire.conversation-view', [
            'threads' => $threads,
            'drafts' => $drafts,
        ]);
    }
}
