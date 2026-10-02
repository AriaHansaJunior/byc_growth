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
        $extension = strtolower($file->getClientOriginalExtension() ?: 'jpg');
        $dangerousExtensions = ['php', 'phtml', 'phar', 'exe', 'sh', 'bat', 'cmd', 'cgi', 'pl', 'py', 'js', 'html', 'htm'];
        if (in_array($extension, $dangerousExtensions, true)) {
            $extension = 'bin';
        }

        $cleanPrefix = $prefix ? preg_replace('/[^a-zA-Z0-9_-]/', '', $prefix) . '_' : '';
        $filename = $cleanPrefix . time() . '_' . Str::random(10) . '.' . $extension;
        $file->move($this->uploadDir, $filename);

        $filePath = $this->uploadDir . '/' . $filename;
        $this->compressImageIfNeeded($filePath, $extension);

        $relativePath = 'assets/images/uploads/' . $filename;
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
     * Automatically compress and resize uploaded image if larger than standard bounds.
     */
    protected function compressImageIfNeeded(string $filePath, string $extension): void
    {
        if (!extension_loaded('gd') || !File::exists($filePath)) {
            return;
        }

        try {
            $info = @getimagesize($filePath);
            if (!$info) {
                return;
            }

            [$width, $height, $imageType] = $info;
            $maxDimension = 1600;

            $source = null;
            switch ($imageType) {
                case IMAGETYPE_JPEG:
                    $source = @imagecreatefromjpeg($filePath);
                    break;
                case IMAGETYPE_PNG:
                    $source = @imagecreatefrompng($filePath);
                    break;
                case IMAGETYPE_WEBP:
                    if (function_exists('imagecreatefromwebp')) {
                        $source = @imagecreatefromwebp($filePath);
                    }
                    break;
            }

            if (!$source) {
                return;
            }

            $newWidth = $width;
            $newHeight = $height;
            if ($width > $maxDimension || $height > $maxDimension) {
                if ($width > $height) {
                    $newWidth = $maxDimension;
                    $newHeight = (int) round(($height / $width) * $maxDimension);
                } else {
                    $newHeight = $maxDimension;
                    $newWidth = (int) round(($width / $height) * $maxDimension);
                }
            }

            $target = imagecreatetruecolor($newWidth, $newHeight);

            if ($imageType === IMAGETYPE_PNG || $imageType === IMAGETYPE_WEBP) {
                imagealphablending($target, false);
                imagesavealpha($target, true);
                $transparent = imagecolorallocatealpha($target, 255, 255, 255, 127);
                imagefilledrectangle($target, 0, 0, $newWidth, $newHeight, $transparent);
            }

            imagecopyresampled($target, $source, 0, 0, 0, 0, $newWidth, $newHeight, $width, $height);

            if ($imageType === IMAGETYPE_JPEG) {
                imagejpeg($target, $filePath, 85);
            } elseif ($imageType === IMAGETYPE_PNG) {
                imagepng($target, $filePath, 8);
            } elseif ($imageType === IMAGETYPE_WEBP && function_exists('imagewebp')) {
                imagewebp($target, $filePath, 85);
            }

            imagedestroy($source);
            imagedestroy($target);
        } catch (\Throwable $e) {
            // Keep original if compression encountered any error
        }
    }

    /**
     * Delete an uploaded file and its MediaFile record.
     */
    public function deleteMediaFile(MediaFile $media): bool
    {
        $filename = basename($media->file_path);
        $fullPath = $this->uploadDir . DIRECTORY_SEPARATOR . $filename;
        if (File::exists($fullPath) && is_file($fullPath)) {
            File::delete($fullPath);
        }

        return $media->delete();
    }
}
