<?php

namespace App\Observers;

use App\Models\Lead;
use App\Models\LeadActivity;
use Illuminate\Support\Facades\Auth;

class LeadObserver
{
    public function created(Lead $lead): void
    {
        LeadActivity::record($lead, 'created', [
            'source' => $lead->source,
        ], Auth::id());
    }

    public function updated(Lead $lead): void
    {
        $userId = Auth::id();

        if ($lead->isDirty('status')) {
            LeadActivity::record($lead, 'status_changed', [
                'from' => $lead->getOriginal('status'),
                'to' => $lead->status,
            ], $userId);
        }

        if ($lead->isDirty('assignee_id')) {
            LeadActivity::record($lead, 'assignee_changed', [
                'from' => $lead->getOriginal('assignee_id'),
                'to' => $lead->assignee_id,
            ], $userId);
        }
    }
}
