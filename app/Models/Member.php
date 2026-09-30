<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Member extends Model
{
    use HasFactory;

    protected $fillable = [
        'full_name',
        'position',
        'date_of_birth',
        'photo_file_id',
        'is_active',
    ];

    protected $casts = [
        'date_of_birth' => 'date',
        'is_active' => 'boolean',
    ];

    /**
     * Get the uploaded photo metadata for this member.
     */
    public function photo(): BelongsTo
    {
        return $this->belongsTo(MediaFile::class, 'photo_file_id');
    }

    /**
     * Get member photo URL.
     */
    public function getPhotoUrlAttribute(): ?string
    {
        return $this->photo ? $this->photo->getUrl() : null;
    }

    /**
     * Get birthday letters received by this member.
     */
    public function birthdayLetters(): HasMany
    {
        return $this->hasMany(BirthdayLetter::class);
    }

    /**
     * Get cash transactions for this member.
     */
    public function cashTransactions(): HasMany
    {
        return $this->hasMany(CashTransaction::class);
    }

    /**
     * Get the associated user account for this member.
     */
    public function user(): \Illuminate\Database\Eloquent\Relations\HasOne
    {
        return $this->hasOne(User::class);
    }

    /**
     * Check if today in Surabaya (Asia/Jakarta) is this member's birthday.
     * Compares month and day only.
     */
    public function isBirthdayToday(?Carbon $date = null): bool
    {
        if (!$this->date_of_birth) {
            return false;
        }

        $targetDate = $date ?? Carbon::now('Asia/Jakarta');

        return $this->date_of_birth->format('m-d') === $targetDate->format('m-d');
    }

    /**
     * Scope query to only members celebrating a birthday on the given or current date.
     */
    public function scopeBirthdayToday($query, ?Carbon $date = null)
    {
        $targetDate = $date ?? Carbon::now('Asia/Jakarta');

        return $query->whereNotNull('date_of_birth')
            ->whereRaw("DATE_FORMAT(date_of_birth, '%m-%d') = ?", [$targetDate->format('m-d')]);
    }
}
