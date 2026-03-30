<?php

namespace App\Policies;

use App\Models\EmailDraft;
use App\Models\User;

class EmailDraftPolicy
{
    public function viewAny(User $user): bool
    {
        return true;
    }

    public function view(User $user, EmailDraft $draft): bool
    {
        return $user->hasRole('admin') || $draft->lead?->assignee_id === $user->id;
    }

    public function create(User $user): bool
    {
        return true;
    }

    public function update(User $user, EmailDraft $draft): bool
    {
        return $user->hasRole('admin') || $draft->lead?->assignee_id === $user->id;
    }

    public function delete(User $user, EmailDraft $draft): bool
    {
        return $user->hasRole('admin') || $draft->lead?->assignee_id === $user->id;
    }
}
