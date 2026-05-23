<?php

namespace App\Policies;

use App\Models\User;
use App\Models\Incident;

class IncidentPolicy
{
    public function view(User $user, Incident $incident): bool
    {
        if ($user->role === 'admin') {
            return true;
        }

        return $user->id === $incident->reported_by || in_array($user->role, ['engineer', 'supervisor'], true);
    }

    public function create(User $user): bool
    {
        return (bool) $user->is_active;
    }

    public function update(User $user, Incident $incident): bool
    {
        return $user->role === 'admin' || $user->id === $incident->reported_by;
    }

    public function delete(User $user, Incident $incident): bool
    {
        return $user->role === 'admin';
    }
}
