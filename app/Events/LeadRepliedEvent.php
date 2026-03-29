<?php

namespace App\Events;

use App\Models\EmailMessage;
use App\Models\EmailThread;
use App\Models\Lead;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class LeadRepliedEvent
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(
        public readonly Lead         $lead,
        public readonly EmailThread  $thread,
        public readonly EmailMessage $message,
    ) {}
}
