<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Support\Facades\Storage;

class MediaFile extends Model
{
    use HasFactory;

    protected $fillable = [
        'disk',
        'file_path',
        'original_name',
        'mime_type',
        'file_size',
        'fileable_type',
        'fileable_id',
    ];

    /**
     * Get the owning fileable model (polymorphic).
     */
    public function fileable(): MorphTo
    {
        return $this->morphTo();
    }

    /**
     * Check if the file is an image.
     */
    public function isImage(): bool
    {
        return str_starts_with($this->mime_type, 'image/');
    }

    /**
     * Get publicly accessible URL or asset URL for the file.
     */
    public function getUrl(): string
    {
        if (str_starts_with($this->file_path, 'assets/')) {
            return asset($this->file_path);
        }

        return Storage::disk($this->disk)->url($this->file_path);
    }
}
