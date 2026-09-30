<?php

namespace App\Policies;

use App\Models\BirthdayLetter;
use App\Models\Member;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class BirthdayLetterPolicy
{
    use HandlesAuthorization;

    /**
     * Determine whether the user can view any birthday letters.
     * Admin can view all letters; normal users cannot browse all letters.
     */
    public function viewAny(User $user): bool
    {
        return $user->isAdmin();
    }

    /**
     * Determine whether the user can view a specific birthday letter.
     * Allowed: Admin, the original sender, or the recipient member.
     */
    public function view(User $user, BirthdayLetter $letter): bool
    {
        if ($user->isAdmin()) {
            return true;
        }

        // Sender can always view their own letter
        if ((int) $letter->user_id === (int) $user->id) {
            return true;
        }

        // Recipient member can view letters addressed to them
        if ($user->member_id && (int) $user->member_id === (int) $letter->member_id) {
            return true;
        }

        return false;
    }

    /**
     * Determine whether the user can create a birthday letter for a recipient.
     * Allowed: Authenticated user cannot write to themselves and cannot duplicate letter in same year.
     */
    public function create(User $user, Member $recipient): bool
    {
        // Self-wish prevention
        if ($user->member_id && (int) $user->member_id === (int) $recipient->id) {
            return false;
        }

        return true;
    }

    /**
     * Determine whether the user can update the birthday letter.
     * Admin: can edit at any time.
     * Normal user: only own letter and only while recipient's birthday date is still today.
     */
    public function update(User $user, BirthdayLetter $letter): bool
    {
        return $letter->canBeEditedBy($user);
    }

    /**
     * Determine whether the user can delete the birthday letter.
     * Admin only.
     */
    public function delete(User $user, BirthdayLetter $letter): bool
    {
        return $user->isAdmin();
    }

    /**
     * Determine whether the user can inspect the true sender identity of an anonymous letter.
     * Admin only.
     */
    public function viewSenderIdentity(User $user, BirthdayLetter $letter): bool
    {
        if ($user->isAdmin()) {
            return true;
        }

        if ((int) $letter->user_id === (int) $user->id) {
            return true;
        }

        return !$letter->is_anonymous;
    }
}
