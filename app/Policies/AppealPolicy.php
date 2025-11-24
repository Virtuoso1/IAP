<?php

namespace App\Policies;

use App\Models\Appeal;
use App\Models\User;
use Illuminate\Auth\Access\Response;

class AppealPolicy
{
    /**
     * Determine if the user can view any appeals.
     */
    public function viewAny(User $user): bool
    {
        return $user->canModerate();
    }

    /**
     * Determine if the user can view the appeal.
     */
    public function view(User $user, Appeal $appeal): bool
    {
        return $user->canModerate() || $user->id === $appeal->user_id;
    }

    /**
     * Determine if the user can create appeals.
     */
    public function create(User $user): bool
    {
        return true; // All authenticated users can create appeals
    }

    /**
     * Determine if the user can update the appeal.
     */
    public function update(User $user, Appeal $appeal): bool
    {
        return $user->canModerate() || $user->id === $appeal->user_id;
    }

    /**
     * Determine if the user can delete the appeal.
     */
    public function delete(User $user, Appeal $appeal): bool
    {
        return $user->canModerate() || $user->id === $appeal->user_id;
    }
}