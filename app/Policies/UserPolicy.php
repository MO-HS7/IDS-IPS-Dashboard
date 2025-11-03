<?php

namespace App\Policies;

use App\Models\User;

class UserPolicy
{
    /**
     * Determine if the user can view any users.
     */
    public function viewAny(User $user): bool
    {
        return $user->role === 'Admin';
    }

    /**
     * Determine if the user can view the user.
     */
    public function view(User $user, User $model): bool
    {
        return $user->role === 'Admin';
    }

    /**
     * Determine if the user can create users.
     */
    public function create(User $user): bool
    {
        return $user->role === 'Admin';
    }

    /**
     * Determine if the user can update the user.
     */
    public function update(User $user, User $model): bool
    {
        return $user->role === 'Admin';
    }

    /**
     * Determine if the user can delete the user.
     */
    public function delete(User $user, User $model): bool
    {
        // Admin can delete, but not themselves
        return $user->role === 'Admin' && $user->id !== $model->id;
    }
}
