<?php

namespace App\Policies;

use App\Models\User;

/**
 * Authorises user-management actions against the `users.*` permission set.
 * Users are never allowed to delete themselves.
 */
class UserPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('users.view');
    }

    public function view(User $user, User $target): bool
    {
        return $user->can('users.view');
    }

    public function create(User $user): bool
    {
        return $user->can('users.create');
    }

    public function update(User $user, User $target): bool
    {
        return $user->can('users.update');
    }

    public function delete(User $user, User $target): bool
    {
        return $user->can('users.delete') && $user->id !== $target->id;
    }
}
