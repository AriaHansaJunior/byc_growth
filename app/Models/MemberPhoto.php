<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MemberPhoto extends Model
{
    use HasFactory;

    protected $table = 'member_photos';

    protected $fillable = [
        'member_id',
        'photo_data',
        'original_name',
        'mime_type',
        'file_size',
    ];

    /**
     * Get the associated member.
     */
    public function member(): BelongsTo
    {
        return $this->belongsTo(Member::class, 'member_id');
    }
}
