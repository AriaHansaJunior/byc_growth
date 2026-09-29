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
        'sender_name',
        'message',
    ];

    /**
     * Get the birthday member who received this letter.
     */
    public function member(): BelongsTo
    {
        return $this->belongsTo(Member::class);
    }
}
