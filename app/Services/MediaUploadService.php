<?php

namespace App\Services;

use App\Models\MediaFile;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

class MediaUploadService
{
    protected string $uploadDir;
    protected ImageOptimizerService $optimizer;

    public function __construct(ImageOptimizerService $optimizer)
    {
        $this->optimizer = $optimizer;
        $this->uploadDir = public_path('assets/images/uploads');
        if (!File::isDirectory($this->uploadDir)) {
            File::makeDirectory($this->uploadDir, 0755, true, true);
        }
    }

    /**
     * Store an uploaded image:
     * - Keeps FULL-SIZE original untouched.
     * - Creates a lightweight compressed version for web output.
     * - Creates a MediaFile record.
     */
    public function storeImage(UploadedFile $file, ?string $prefix = 'media', ?string $fileableType = null, ?int $fileableId = null): MediaFile
    {
        $extension = strtolower($file->getClientOriginalExtension() ?: 'jpg');
        $dangerousExtensions = ['php', 'phtml', 'phar', 'exe', 'sh', 'bat', 'cmd', 'cgi', 'pl', 'py', 'js', 'html', 'htm'];
        if (in_array($extension, $dangerousExtensions, true)) {
            $extension = 'bin';
        }

        $cleanPrefix = $prefix ? preg_replace('/[^a-zA-Z0-9_-]/', '', $prefix) . '_' : '';
        $baseName = $cleanPrefix . time() . '_' . Str::random(10);
        $fullFilename = $baseName . '_full.' . $extension;
        $compressedFilename = $baseName . '_thumb.' . $extension;

        // Save original full-size file
        $file->move($this->uploadDir, $fullFilename);
        $fullPath = $this->uploadDir . '/' . $fullFilename;
        $compressedPath = $this->uploadDir . '/' . $compressedFilename;

        // Generate compressed web-optimized output version (e.g. max 900px, quality 80)
        $compressedSuccess = $this->optimizer->compressImageFile($fullPath, $compressedPath, 900, 80);

        $relativePath = 'assets/images/uploads/' . $fullFilename;
        $compressedRelativePath = $compressedSuccess && File::exists($compressedPath)
            ? 'assets/images/uploads/' . $compressedFilename
            : $relativePath;

        $fileSize = File::exists($fullPath) ? File::size($fullPath) : 0;

        $data = [
            'disk' => 'public',
            'file_path' => $relativePath,
            'original_name' => $file->getClientOriginalName(),
            'mime_type' => $file->getClientMimeType() ?: 'image/jpeg',
            'file_size' => $fileSize,
            'fileable_type' => $fileableType,
            'fileable_id' => $fileableId,
        ];

        // Only include compressed_path if the column exists in database schema
        if (Schema::hasColumn('media_files', 'compressed_path')) {
            $data['compressed_path'] = $compressedRelativePath;
        }

        return MediaFile::create($data);
    }

    /**
     * Delete an uploaded file (both full-size original and compressed version) and its MediaFile record.
     */
    public function deleteMediaFile(MediaFile $media): bool
    {
        // 1. Delete original full-size file
        $filename = basename($media->file_path);
        $fullPath = $this->uploadDir . DIRECTORY_SEPARATOR . $filename;
        if (File::exists($fullPath) && is_file($fullPath)) {
            File::delete($fullPath);
        }

        // 2. Delete compressed version if distinct
        if (!empty($media->compressed_path)) {
            $compFilename = basename($media->compressed_path);
            $compPath = $this->uploadDir . DIRECTORY_SEPARATOR . $compFilename;
            if (File::exists($compPath) && is_file($compPath)) {
                File::delete($compPath);
            }
        }

        return $media->delete();
    }
}
