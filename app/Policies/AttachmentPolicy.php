<?php

namespace App\Policies;

use App\Models\Attachment;
use App\Models\User;

class AttachmentPolicy
{
    /**
     * Determine whether the user can view any models.
     */
    public function viewAny(User $user): bool
    {
        return true;
    }

    /**
     * Determine whether the user can view the model.
     */
    public function view(User $user, Attachment $attachment): bool
    {
        // Supervisor bisa lihat semua
        if ($user->role === 'supervisor') {
            return true;
        }

        $incident = $attachment->incident;

        // Engineer: incident yang di-handle
        if ($user->role === 'engineer' && $incident->handled_by === $user->id) {
            return true;
        }

        // Operator: incident yang dilaporkan
        if ($user->role === 'operator' && $incident->reported_by === $user->id) {
            return true;
        }

        return false;
    }

    /**
     * Determine whether the user can create models.
     */
    public function create(User $user): bool
    {
        return in_array($user->role, ['operator', 'engineer', 'supervisor'], true);
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(User $user, Attachment $attachment): bool
    {
        // Hanya uploader atau supervisor yang bisa update deskripsi
        return $attachment->uploaded_by === $user->id || $user->role === 'supervisor';
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, Attachment $attachment): bool
    {
        // Uploader, supervisor, atau engineer yang handle bisa delete
        if ($attachment->uploaded_by === $user->id || $user->role === 'supervisor') {
            return true;
        }

        $incident = $attachment->incident;
        if ($user->role === 'engineer' && $incident->handled_by === $user->id) {
            return true;
        }

        return false;
    }

    /**
     * Determine whether the user can restore the model.
     */
    public function restore(User $user, Attachment $attachment): bool
    {
        return $user->role === 'supervisor';
    }

    /**
     * Determine whether the user can permanently delete the model.
     */
    public function forceDelete(User $user, Attachment $attachment): bool
    {
        return $user->role === 'supervisor';
    }
}
