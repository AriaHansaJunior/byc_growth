<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class HomepageSlidePhoto extends Model
{
    use HasFactory;

    protected $table = 'homepage_slide_photos';

    protected $fillable = [
        'homepage_slide_id',
        'image_data',
        'original_name',
        'mime_type',
        'file_size',
    ];

    /**
     * Get the associated homepage slide.
     */
    public function slide(): BelongsTo
    {
        return $this->belongsTo(HomepageSlide::class, 'homepage_slide_id');
    }
}
