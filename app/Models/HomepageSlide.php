<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;

class HomepageSlide extends Model
{
    use HasFactory;

    protected $fillable = [
        'media_file_id',
        'title',
        'caption',
        'sort_order',
        'is_active',
    ];

    protected $casts = [
        'sort_order' => 'integer',
        'is_active' => 'boolean',
    ];

    /**
     * Get the dedicated database-stored photo for this slide.
     */
    public function slidePhoto(): HasOne
    {
        return $this->hasOne(HomepageSlidePhoto::class, 'homepage_slide_id');
    }

    /**
     * Get the associated media file for this slide.
     */
    public function media(): BelongsTo
    {
        return $this->belongsTo(MediaFile::class, 'media_file_id');
    }

    /**
     * Accessor for the publicly accessible image URL.
     */
    public function getImageUrlAttribute(): string
    {
        if ($this->slidePhoto && !empty($this->slidePhoto->image_data)) {
            return $this->slidePhoto->image_data;
        }

        if ($this->media) {
            return $this->media->getUrl();
        }

        return asset('assets/images/group-photo-dummy.svg');
    }

    /**
     * Scope query to only active slides.
     */
    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope query to order slides sequentially.
     */
    public function scopeOrdered(Builder $query): Builder
    {
        return $query->orderBy('sort_order', 'asc')->orderBy('id', 'asc');
    }
}
