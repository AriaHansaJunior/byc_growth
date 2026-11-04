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
        'compressed_photo_data',
        'raw_photo_data',
        'crop_zoom',
        'crop_offset_x',
        'crop_offset_y',
        'original_name',
        'mime_type',
        'file_size',
    ];

    protected $casts = [
        'crop_zoom' => 'float',
        'crop_offset_x' => 'float',
        'crop_offset_y' => 'float',
    ];

    /**
     * Get the associated member.
     */
    public function member(): BelongsTo
    {
        return $this->belongsTo(Member::class, 'member_id');
    }
}
