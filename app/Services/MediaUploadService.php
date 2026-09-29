<?php

namespace App\Services;

use App\Models\MediaFile;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;

class MediaUploadService
{
    protected string $uploadDir;

    public function __construct()
    {
        $this->uploadDir = public_path('assets/images/uploads');
        if (!File::isDirectory($this->uploadDir)) {
            File::makeDirectory($this->uploadDir, 0755, true, true);
        }
    }

    /**
     * Store an uploaded image and create a MediaFile record.
     */
    public function storeImage(UploadedFile $file, ?string $prefix = 'media', ?string $fileableType = null, ?int $fileableId = null): MediaFile
    {
        $extension = $file->getClientOriginalExtension() ?: 'jpg';
        $filename = ($prefix ? $prefix . '_' : '') . time() . '_' . Str::random(10) . '.' . $extension;
        $file->move($this->uploadDir, $filename);

        $relativePath = 'assets/images/uploads/' . $filename;
        $filePath = $this->uploadDir . '/' . $filename;
        $fileSize = File::exists($filePath) ? File::size($filePath) : 0;

        return MediaFile::create([
            'disk' => 'public',
            'file_path' => $relativePath,
            'original_name' => $file->getClientOriginalName(),
            'mime_type' => $file->getClientMimeType() ?: 'image/jpeg',
            'file_size' => $fileSize,
            'fileable_type' => $fileableType,
            'fileable_id' => $fileableId,
        ]);
    }

    /**
     * Delete an uploaded file and its MediaFile record.
     */
    public function deleteMediaFile(MediaFile $media): bool
    {
        if (str_starts_with($media->file_path, 'assets/images/uploads/')) {
            $fullPath = public_path($media->file_path);
            if (File::exists($fullPath)) {
                File::delete($fullPath);
            }
        }

        return $media->delete();
    }
}
