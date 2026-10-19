<?php

namespace App\Services;

use Illuminate\Support\Facades\File;

class ImageOptimizerService
{
    /**
     * Compress an image file from source to target path.
     * Keeps original intact at source, creates compressed version at target.
     *
     * @param string $sourcePath Absolute path to the source image
     * @param string $targetPath Absolute path for the compressed image
     * @param int $maxDimension Max width or height (default 900px)
     * @param int $quality JPEG/WebP quality (0-100, default 80)
     * @return bool True on success, false if failed
     */
    public function compressImageFile(string $sourcePath, string $targetPath, int $maxDimension = 900, int $quality = 80): bool
    {
        if (!extension_loaded('gd') || !File::exists($sourcePath)) {
            return false;
        }

        try {
            $info = @getimagesize($sourcePath);
            if (!$info) {
                return false;
            }

            [$width, $height, $imageType] = $info;
            if ($width <= 0 || $height <= 0) {
                return false;
            }

            $source = $this->createImageResourceFromFile($sourcePath, $imageType);
            if (!$source) {
                return false;
            }

            // Correct EXIF orientation for JPEGs if possible
            $source = $this->autoRotateGdImage($source, $sourcePath);

            $width = imagesx($source);
            $height = imagesy($source);

            [$newWidth, $newHeight] = $this->calculateTargetDimensions($width, $height, $maxDimension);

            $target = imagecreatetruecolor($newWidth, $newHeight);

            // Handle transparency for PNG, WEBP, GIF
            if ($imageType === IMAGETYPE_PNG || $imageType === IMAGETYPE_WEBP || $imageType === IMAGETYPE_GIF) {
                imagealphablending($target, false);
                imagesavealpha($target, true);
                $transparent = imagecolorallocatealpha($target, 255, 255, 255, 127);
                imagefilledrectangle($target, 0, 0, $newWidth, $newHeight, $transparent);
            }

            imagecopyresampled($target, $source, 0, 0, 0, 0, $newWidth, $newHeight, $width, $height);

            // Ensure destination directory exists
            $targetDir = dirname($targetPath);
            if (!File::isDirectory($targetDir)) {
                File::makeDirectory($targetDir, 0755, true, true);
            }

            $saved = false;
            if ($imageType === IMAGETYPE_JPEG) {
                $saved = @imagejpeg($target, $targetPath, $quality);
            } elseif ($imageType === IMAGETYPE_PNG) {
                // Compression level for PNG is 0-9
                $pngCompression = (int) round((100 - $quality) / 10);
                $saved = @imagepng($target, $targetPath, min(9, max(0, $pngCompression)));
            } elseif ($imageType === IMAGETYPE_WEBP && function_exists('imagewebp')) {
                $saved = @imagewebp($target, $targetPath, $quality);
            } elseif ($imageType === IMAGETYPE_GIF) {
                $saved = @imagegif($target, $targetPath);
            } else {
                // Fallback to JPEG
                $saved = @imagejpeg($target, $targetPath, $quality);
            }

            imagedestroy($source);
            imagedestroy($target);

            return (bool) $saved;
        } catch (\Throwable $e) {
            return false;
        }
    }

    /**
     * Compress a base64 Data URI string.
     * Returns a compressed base64 Data URI string.
     * If compression fails, returns the original base64.
     *
     * @param string $base64Data Data URI (e.g. data:image/jpeg;base64,...)
     * @param int $maxDimension Max width or height (default 650px)
     * @param int $quality Quality 0-100 (default 80)
     * @return string Compressed data URI
     */
    public function compressBase64(string $base64Data, int $maxDimension = 650, int $quality = 80): string
    {
        if (!extension_loaded('gd') || !str_starts_with($base64Data, 'data:image/')) {
            return $base64Data;
        }

        try {
            $parts = explode(',', $base64Data, 2);
            if (count($parts) < 2) {
                return $base64Data;
            }

            $rawBinary = base64_decode($parts[1]);
            if ($rawBinary === false) {
                return $base64Data;
            }

            $source = @imagecreatefromstring($rawBinary);
            if (!$source) {
                return $base64Data;
            }

            $width = imagesx($source);
            $height = imagesy($source);

            [$newWidth, $newHeight] = $this->calculateTargetDimensions($width, $height, $maxDimension);

            $target = imagecreatetruecolor($newWidth, $newHeight);

            // Determine mime type from header
            $header = $parts[0];
            $isPng = str_contains($header, 'png');
            $isWebp = str_contains($header, 'webp');
            $isGif = str_contains($header, 'gif');

            if ($isPng || $isWebp || $isGif) {
                imagealphablending($target, false);
                imagesavealpha($target, true);
                $transparent = imagecolorallocatealpha($target, 255, 255, 255, 127);
                imagefilledrectangle($target, 0, 0, $newWidth, $newHeight, $transparent);
            }

            imagecopyresampled($target, $source, 0, 0, 0, 0, $newWidth, $newHeight, $width, $height);

            ob_start();
            $mime = 'image/jpeg';
            if ($isPng) {
                $mime = 'image/png';
                $pngCompression = (int) round((100 - $quality) / 10);
                imagepng($target, null, min(9, max(0, $pngCompression)));
            } elseif ($isWebp && function_exists('imagewebp')) {
                $mime = 'image/webp';
                imagewebp($target, null, $quality);
            } else {
                $mime = 'image/jpeg';
                imagejpeg($target, null, $quality);
            }
            $compressedBinary = ob_get_clean();

            imagedestroy($source);
            imagedestroy($target);

            if ($compressedBinary && strlen($compressedBinary) > 0) {
                return 'data:' . $mime . ';base64,' . base64_encode($compressedBinary);
            }

            return $base64Data;
        } catch (\Throwable $e) {
            return $base64Data;
        }
    }

    /**
     * Decode base64 data URI to binary with its MIME type.
     *
     * @param string $base64Data
     * @return array{binary: string, mime: string}|null
     */
    public function decodeBase64(string $base64Data): ?array
    {
        if (!str_starts_with($base64Data, 'data:')) {
            return null;
        }

        $parts = explode(',', $base64Data, 2);
        if (count($parts) < 2) {
            return null;
        }

        preg_match('/data:([^;]+);base64/', $parts[0], $matches);
        $mime = $matches[1] ?? 'image/jpeg';
        $binary = base64_decode($parts[1]);

        if ($binary === false) {
            return null;
        }

        return [
            'binary' => $binary,
            'mime' => $mime,
        ];
    }

    /**
     * Calculate proportional dimensions not exceeding $maxDimension.
     */
    protected function calculateTargetDimensions(int $width, int $height, int $maxDimension): array
    {
        if ($width <= $maxDimension && $height <= $maxDimension) {
            return [$width, $height];
        }

        if ($width >= $height) {
            $newWidth = $maxDimension;
            $newHeight = (int) max(1, round(($height / $width) * $maxDimension));
        } else {
            $newHeight = $maxDimension;
            $newWidth = (int) max(1, round(($width / $height) * $maxDimension));
        }

        return [$newWidth, $newHeight];
    }

    /**
     * Create GD resource from file path based on image type.
     */
    protected function createImageResourceFromFile(string $filePath, int $imageType)
    {
        switch ($imageType) {
            case IMAGETYPE_JPEG:
                return @imagecreatefromjpeg($filePath);
            case IMAGETYPE_PNG:
                return @imagecreatefrompng($filePath);
            case IMAGETYPE_WEBP:
                return function_exists('imagecreatefromwebp') ? @imagecreatefromwebp($filePath) : null;
            case IMAGETYPE_GIF:
                return @imagecreatefromgif($filePath);
            default:
                $content = @file_get_contents($filePath);
                return $content ? @imagecreatefromstring($content) : null;
        }
    }

    /**
     * Rotate GD image if EXIF orientation tag is present.
     */
    protected function autoRotateGdImage($resource, string $filePath)
    {
        if (!function_exists('exif_read_data')) {
            return $resource;
        }

        try {
            $exif = @exif_read_data($filePath);
            if (empty($exif['Orientation'])) {
                return $resource;
            }

            switch ($exif['Orientation']) {
                case 3:
                    $rotated = imagerotate($resource, 180, 0);
                    imagedestroy($resource);
                    return $rotated;
                case 6:
                    $rotated = imagerotate($resource, -90, 0);
                    imagedestroy($resource);
                    return $rotated;
                case 8:
                    $rotated = imagerotate($resource, 90, 0);
                    imagedestroy($resource);
                    return $rotated;
                default:
                    return $resource;
            }
        } catch (\Throwable $e) {
            return $resource;
        }
    }
}
