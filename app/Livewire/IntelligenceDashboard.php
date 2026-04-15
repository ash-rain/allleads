<?php

namespace App\Livewire;

use App\Models\Lead;
use Illuminate\View\View;
use Livewire\Component;

class IntelligenceDashboard extends Component
{
    public int $leadId;

    public function mount(int $leadId): void
    {
        $this->leadId = $leadId;
    }

    public function render(): View
    {
        $lead = Lead::findOrFail($this->leadId);
        $business = $lead->business;

        return view('livewire.intelligence-dashboard', [
            'lead' => $lead,
            'business' => $business,
            'prospectAnalysis' => $lead->prospectAnalysis,
            'websiteAnalysis' => $lead->websiteAnalysis,
            'trendAnalysis' => $lead->trendAnalysis,
            'geoAnalysis' => $lead->geoAnalysis,
        ]);
    }
}
