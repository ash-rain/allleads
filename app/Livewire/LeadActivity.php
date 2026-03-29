<?php

namespace App\Livewire;

use App\Models\LeadActivity;
use Livewire\Component;

class LeadActivity extends Component
{
    public int $leadId;

    public function render(): \Illuminate\View\View
    {
        $activities = LeadActivity::query()
            ->where('lead_id', $this->leadId)
            ->orderByDesc('created_at')
            ->get();

        return view('livewire.lead-activity', ['activities' => $activities]);
    }
}
