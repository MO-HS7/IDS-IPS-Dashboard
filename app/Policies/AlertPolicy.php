<?php

namespace App\Policies;

use App\Models\Alert;
use App\Models\User;
use Illuminate\Auth\Access\Response;

class AlertPolicy
{
    /**
     * Determine whether the user can view any models.
     */
    public function viewAny(User $user): bool
    {
        // Only Admin and Analyst can view alerts
        return in_array($user->role, ['Admin', 'Analyst']);
    }

    /**
     * Determine whether the user can view the model.
     */
    public function view(User $user, Alert $alert): bool
    {
        // Admin and Analyst can view all alerts
        if (in_array($user->role, ['Admin', 'Analyst'])) {
            return true;
        }
        
        // Viewer can view alerts from their own network logs
        if ($alert->networkLog && $alert->networkLog->user_id === $user->id) {
            return true;
        }
        
        return false;
    }

    /**
     * Determine whether the user can create models.
     */
    public function create(User $user): bool
    {
        // Only Admin and Analyst can create alerts
        return in_array($user->role, ['Admin', 'Analyst']);
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(User $user, Alert $alert): bool
    {
        // Only Admin and Analyst can update alerts
        return in_array($user->role, ['Admin', 'Analyst']);
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, Alert $alert): bool
    {
        // Only Admin can delete alerts
        return $user->role === 'Admin';
    }

    /**
     * Determine whether the user can restore the model.
     */
    public function restore(User $user, Alert $alert): bool
    {
        // Only Admin can restore alerts
        return $user->role === 'Admin';
    }

    /**
     * Determine whether the user can permanently delete the model.
     */
    public function forceDelete(User $user, Alert $alert): bool
    {
        // Only Admin can permanently delete alerts
        return $user->role === 'Admin';
    }
}
