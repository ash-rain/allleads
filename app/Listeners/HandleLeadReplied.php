<?php

namespace App\Listeners;

use App\Events\LeadRepliedEvent;
use App\Models\Lead;
use App\Models\LeadActivity;
use App\Notifications\LeadRepliedNotification;
use Illuminate\Contracts\Queue\ShouldQueue;

class HandleLeadReplied implements ShouldQueue
{
    public function handle(LeadRepliedEvent $event): void
    {
        $lead   = $event->lead;
        $thread = $event->thread;

        // Advance lead status if still at 'contacted'.
        if ($lead->status === 'contacted') {
            $lead->update(['status' => 'replied']);

            LeadActivity::record(
                leadId: $lead->id,
                userId: null,
                event: 'status_changed',
                payload: ['from' => 'contacted', 'to' => 'replied', 'trigger' => 'inbound_reply'],
            );
        }

        // Record inbound activity.
        LeadActivity::record(
            leadId: $lead->id,
            userId: null,
            event: 'email_received',
            payload: ['thread_id' => $thread->id, 'subject' => $event->message->subject],
        );

        // Notify all admins and the assigned agent.
        $notifiables = \App\Models\User::role('admin')->get();

        if ($lead->assignee_id && ! $notifiables->contains('id', $lead->assignee_id)) {
            $assignee = \App\Models\User::find($lead->assignee_id);
            if ($assignee) {
                $notifiables->push($assignee);
            }
        }

        foreach ($notifiables as $user) {
            $user->notify(new LeadRepliedNotification($lead, $thread));
        }
    }
}
