<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Member extends Model
{
    use HasFactory;

    protected $fillable = [
        'full_name',
        'position',
        'photo_file_id',
        'is_active',
    ];

    protected $casts = [
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
}
