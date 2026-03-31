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

        return view('livewire.intelligence-dashboard', [
            'lead' => $lead,
            'prospectAnalysis' => $lead->prospectAnalysis,
            'websiteAnalysis' => $lead->websiteAnalysis,
        ]);
    }
}
