<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Storage;

class MediaFile extends Model
{
    use HasFactory;

    protected $fillable = [
        'disk',
        'file_path',
        'compressed_path',
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
     * Get publicly accessible URL for the compressed/web-optimized version (lightweight for fast page load).
     */
    public function getUrl(): string
    {
        $path = !empty($this->compressed_path) ? $this->compressed_path : $this->file_path;

        if (str_starts_with($path, 'assets/')) {
            return asset($path);
        }

        return Storage::disk($this->disk)->url($path);
    }

    /**
     * Get publicly accessible URL for the FULL-SIZE original version (for new tab / download / inspection).
     */
    public function getFullUrl(): string
    {
        if (str_starts_with($this->file_path, 'assets/')) {
            return asset($this->file_path);
        }

        return Storage::disk($this->disk)->url($this->file_path);
    }

    /**
     * Download route URL for downloading the original full-size image.
     */
    public function getDownloadUrl(): string
    {
        return route('media.download', ['id' => $this->id]);
    }
}
