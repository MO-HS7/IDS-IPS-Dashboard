<?php

namespace App\Policies;

use App\Models\NetworkLog;
use App\Models\User;
use Illuminate\Auth\Access\Response;

class NetworkLogPolicy
{
    /**
     * Determine whether the user can view any models.
     */
    public function viewAny(User $user): bool
    {
        return in_array($user->role, ['Admin', 'Analyst', 'Viewer']);
    }

    /**
     * Determine whether the user can view the model.
     */
    public function view(User $user, NetworkLog $networkLog): bool
    {
        return $user->role === 'Admin' || $user->id === $networkLog->user_id;
    }

    /**
     * Determine whether the user can create models.
     */
    public function create(User $user): bool
    {
        return in_array($user->role, ['Admin', 'Analyst']);
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(User $user, NetworkLog $networkLog): bool
    {
        return $user->role === 'Admin' || ($user->role === 'Analyst' && $user->id === $networkLog->user_id);
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, NetworkLog $networkLog): bool
    {
        return $user->role === 'Admin' || ($user->role === 'Analyst' && $user->id === $networkLog->user_id);
    }

    /**
     * Determine whether the user can restore the model.
     */
    public function restore(User $user, NetworkLog $networkLog): bool
    {
        return $user->role === 'Admin';
    }

    /**
     * Determine whether the user can permanently delete the model.
     */
    public function forceDelete(User $user, NetworkLog $networkLog): bool
    {
        return $user->role === 'Admin';
    }
}
