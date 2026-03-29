<?php

namespace App\Livewire;

use App\Models\LeadNote;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;
use Livewire\Component;

class LeadNotes extends Component
{
    public int $leadId;

    public string $type = 'note';

    public string $body = '';

    public int $duration = 0;

    public string $outcome = '';

    protected function rules(): array
    {
        return [
            'body' => 'required|string|min:1',
            'type' => 'required|in:note,call',
            'duration' => 'nullable|integer|min:0',
            'outcome' => 'nullable|string|in:interested,not_interested,no_answer,callback',
        ];
    }

    public function addNote(): void
    {
        $this->validate();

        LeadNote::create([
            'lead_id' => $this->leadId,
            'type' => $this->type,
            'body' => $this->body,
            'duration_minutes' => $this->type === 'call' ? $this->duration : null,
            'outcome' => $this->type === 'call' ? $this->outcome : null,
            'created_by' => Auth::id(),
        ]);

        $this->reset(['body', 'duration', 'outcome']);
        $this->type = 'note';
    }

    public function render(): View
    {
        $notes = LeadNote::query()
            ->where('lead_id', $this->leadId)
            ->with('creator:id,name')
            ->latest()
            ->get();

        return view('livewire.lead-notes', ['notes' => $notes]);
    }
}
