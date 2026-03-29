<?php

namespace App\Providers;

use App\Events\LeadRepliedEvent;
use App\Listeners\HandleLeadReplied;
use App\Models\Lead;
use App\Observers\LeadObserver;
use App\Policies\EmailDraftPolicy;
use App\Policies\LeadPolicy;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void {}

    public function boot(): void
    {
        Lead::observe(LeadObserver::class);

        Gate::policy(Lead::class, LeadPolicy::class);
        Gate::policy(\App\Models\EmailDraft::class, EmailDraftPolicy::class);

        Event::listen(LeadRepliedEvent::class, HandleLeadReplied::class);
    }
}
