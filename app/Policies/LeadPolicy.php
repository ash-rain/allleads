<?php

namespace App\Policies;

use App\Models\Lead;
use App\Models\User;

class LeadPolicy
{
    public function viewAny(User $user): bool
    {
        return true;
    }

    public function view(User $user, Lead $lead): bool
    {
        return true;
    }

    public function create(User $user): bool
    {
        return true;
    }

    public function update(User $user, Lead $lead): bool
    {
        if ($user->hasRole('admin')) {
            return true;
        }

        // Agents can only update leads assigned to them
        return $lead->assignee_id === $user->id;
    }

    public function delete(User $user, Lead $lead): bool
    {
        return $user->hasRole('admin');
    }

    public function restore(User $user, Lead $lead): bool
    {
        return $user->hasRole('admin');
    }

    public function forceDelete(User $user, Lead $lead): bool
    {
        return $user->hasRole('admin');
    }
}
