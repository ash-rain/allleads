<?php

namespace App\Livewire;

use App\Models\LeadActivity as LeadActivityModel;
use Illuminate\View\View;
use Livewire\Component;

class LeadActivity extends Component
{
    public int $leadId;

    public function render(): View
    {
        $activities = LeadActivityModel::query()
            ->where('lead_id', $this->leadId)
            ->orderByDesc('created_at')
            ->get();

        return view('livewire.lead-activity', ['activities' => $activities]);
    }
}
