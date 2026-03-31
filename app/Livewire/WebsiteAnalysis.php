<?php

namespace App\Livewire;

use App\Jobs\RunWebsiteAnalysisJob;
use App\Models\Lead;
use App\Models\LeadWebsiteAnalysis;
use Illuminate\View\View;
use Livewire\Component;

class WebsiteAnalysis extends Component
{
    public int $leadId;

    public ?string $analysisStatus = null;

    public function mount(): void
    {
        $this->reload();
    }

    public function reload(): void
    {
        $this->analysisStatus = LeadWebsiteAnalysis::where('lead_id', $this->leadId)->value('status');
    }

    public function retry(): void
    {
        LeadWebsiteAnalysis::where('lead_id', $this->leadId)->update([
            'status' => LeadWebsiteAnalysis::STATUS_PENDING,
            'scraped_data' => null,
            'error_message' => null,
            'result' => null,
            'started_at' => now(),
            'completed_at' => null,
        ]);

        RunWebsiteAnalysisJob::dispatch(
            Lead::findOrFail($this->leadId),
            auth()->id()
        );

        $this->analysisStatus = LeadWebsiteAnalysis::STATUS_PENDING;
    }

    public function runAnalysis(): void
    {
        LeadWebsiteAnalysis::updateOrCreate(
            ['lead_id' => $this->leadId],
            [
                'status' => LeadWebsiteAnalysis::STATUS_PENDING,
                'scraped_data' => null,
                'result' => null,
                'error_message' => null,
                'started_at' => now(),
                'completed_at' => null,
            ]
        );

        RunWebsiteAnalysisJob::dispatch(
            Lead::findOrFail($this->leadId),
            auth()->id()
        );

        $this->analysisStatus = LeadWebsiteAnalysis::STATUS_PENDING;
    }

    public function render(): View
    {
        $analysis = $this->analysisStatus
            ? LeadWebsiteAnalysis::where('lead_id', $this->leadId)->first()
            : null;

        return view('livewire.website-analysis', [
            'analysis' => $analysis,
            'isPending' => $this->analysisStatus === LeadWebsiteAnalysis::STATUS_PENDING,
            'isCompleted' => $this->analysisStatus === LeadWebsiteAnalysis::STATUS_COMPLETED,
        ]);
    }
}
