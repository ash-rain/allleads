<?php

namespace App\Livewire;

use App\Jobs\RunProspectAnalysisJob;
use App\Models\Lead;
use App\Models\LeadProspectAnalysis;
use Illuminate\View\View;
use Livewire\Component;

class ProspectAnalysis extends Component
{
    public int $leadId;

    public ?LeadProspectAnalysis $analysis = null;

    public function mount(): void
    {
        $this->reload();
    }

    public function reload(): void
    {
        $this->analysis = LeadProspectAnalysis::where('lead_id', $this->leadId)->first();
    }

    public function retry(): void
    {
        RunProspectAnalysisJob::dispatch(
            Lead::findOrFail($this->leadId),
            auth()->id()
        );

        $this->reload();
    }

    public function render(): View
    {
        return view('livewire.prospect-analysis', [
            'analysis' => $this->analysis,
            'isPending' => $this->analysis?->status === LeadProspectAnalysis::STATUS_PENDING,
        ]);
    }
}
