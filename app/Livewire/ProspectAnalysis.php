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

    public ?string $analysisStatus = null;

    public function mount(): void
    {
        $this->reload();
    }

    public function reload(): void
    {
        $this->analysisStatus = LeadProspectAnalysis::where('lead_id', $this->leadId)->value('status');
    }

    public function retry(): void
    {
        LeadProspectAnalysis::where('lead_id', $this->leadId)->update([
            'status' => LeadProspectAnalysis::STATUS_PENDING,
            'error_message' => null,
            'result' => null,
            'started_at' => now(),
            'completed_at' => null,
        ]);

        RunProspectAnalysisJob::dispatch(
            Lead::findOrFail($this->leadId),
            auth()->id()
        );

        $this->analysisStatus = LeadProspectAnalysis::STATUS_PENDING;
    }

    public function render(): View
    {
        $analysis = $this->analysisStatus
            ? LeadProspectAnalysis::where('lead_id', $this->leadId)->first()
            : null;

        return view('livewire.prospect-analysis', [
            'analysis' => $analysis,
            'isPending' => $this->analysisStatus === LeadProspectAnalysis::STATUS_PENDING,
            'isCompleted' => $this->analysisStatus === LeadProspectAnalysis::STATUS_COMPLETED,
        ]);
    }
}
