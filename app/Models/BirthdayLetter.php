<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class BirthdayLetter extends Model
{
    use HasFactory;

    protected $fillable = [
        'member_id',
        'user_id',
        'birthday_year',
        'is_anonymous',
        'sender_name',
        'message',
    ];

    protected $casts = [
        'birthday_year' => 'integer',
        'is_anonymous' => 'boolean',
    ];

    /**
     * Get the birthday recipient member who received this letter.
     */
    public function member(): BelongsTo
    {
        return $this->belongsTo(Member::class);
    }

    /**
     * Get the authenticated sender user account.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Alias for user relationship (the sender).
     */
    public function sender(): BelongsTo
    {
        return $this->user();
    }

    /**
     * Get the display name for the letter sender based on anonymity preference.
     * Anonymous is a display preference only; true sender is always tracked via user_id.
     */
    public function getDisplayNameAttribute(): string
    {
        if ($this->is_anonymous) {
            return 'Anonymous';
        }

        if ($this->user && $this->user->member) {
            return $this->user->member->full_name;
        }

        if ($this->user) {
            return $this->user->name;
        }

        return $this->sender_name ?: 'A BYC Friend';
    }

    /**
     * Check if this letter can be edited by the given user.
     * Admin: can edit at any time.
     * Normal user: only own letter and only while recipient's birthday is still today in Asia/Jakarta.
     */
    public function canBeEditedBy(?User $user): bool
    {
        if (!$user) {
            return false;
        }

        if ($user->isAdmin()) {
            return true;
        }

        if ((int) $this->user_id !== (int) $user->id) {
            return false;
        }

        return $this->member ? $this->member->isBirthdayToday() : false;
    }
}
