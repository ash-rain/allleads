<?php

namespace App\Livewire;

use App\Jobs\RunGeoAnalysisJob;
use App\Models\Lead;
use App\Models\LeadGeoAnalysis;
use Illuminate\View\View;
use Livewire\Component;

class GeoAnalysis extends Component
{
    public int $leadId;

    public ?string $analysisStatus = null;

    public function mount(): void
    {
        $analysis = LeadGeoAnalysis::where('lead_id', $this->leadId)->first();
        $this->analysisStatus = $analysis?->status;
    }

    public function reload(): void
    {
        $this->analysisStatus = LeadGeoAnalysis::where('lead_id', $this->leadId)->value('status');
    }

    public function retry(): void
    {
        LeadGeoAnalysis::where('lead_id', $this->leadId)->update([
            'status' => LeadGeoAnalysis::STATUS_PENDING,
            'raw_data' => null,
            'result' => null,
            'error_message' => null,
            'started_at' => now(),
            'completed_at' => null,
        ]);

        RunGeoAnalysisJob::dispatch(
            Lead::findOrFail($this->leadId),
            auth()->id()
        );

        $this->analysisStatus = LeadGeoAnalysis::STATUS_PENDING;
    }

    public function runAnalysis(): void
    {
        LeadGeoAnalysis::updateOrCreate(
            ['lead_id' => $this->leadId],
            [
                'status' => LeadGeoAnalysis::STATUS_PENDING,
                'raw_data' => null,
                'result' => null,
                'error_message' => null,
                'started_at' => now(),
                'completed_at' => null,
            ]
        );

        RunGeoAnalysisJob::dispatch(
            Lead::findOrFail($this->leadId),
            auth()->id()
        );

        $this->analysisStatus = LeadGeoAnalysis::STATUS_PENDING;
    }

    public function render(): View
    {
        $analysis = $this->analysisStatus
            ? LeadGeoAnalysis::where('lead_id', $this->leadId)->first()
            : null;

        $lead = Lead::find($this->leadId);

        return view('livewire.geo-analysis', [
            'analysis' => $analysis,
            'lead' => $lead,
            'isPending' => $this->analysisStatus === LeadGeoAnalysis::STATUS_PENDING,
            'isCompleted' => $this->analysisStatus === LeadGeoAnalysis::STATUS_COMPLETED,
        ]);
    }
}
