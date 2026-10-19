<?php

namespace App\Console\Commands;

use App\Models\HomepageSlidePhoto;
use App\Models\MediaFile;
use App\Models\MemberPhoto;
use App\Services\ImageOptimizerService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Schema;

class CompressExistingPhotos extends Command
{
    protected $signature = 'photos:compress-existing';
    protected $description = 'Generate compressed versions for all existing member photos, homepage slides, and media files';

    public function handle(ImageOptimizerService $optimizer): int
    {
        $this->info('Starting retroactive compression of existing photos...');

        // 1. Member Photos
        if (Schema::hasColumn('member_photos', 'compressed_photo_data')) {
            $memberPhotos = MemberPhoto::whereNull('compressed_photo_data')
                ->orWhere('compressed_photo_data', '')
                ->get();

            $this->info("Found {$memberPhotos->count()} member photos to compress.");
            foreach ($memberPhotos as $mp) {
                if (!empty($mp->photo_data)) {
                    $compressed = $optimizer->compressBase64($mp->photo_data, 550, 80);
                    if ($compressed && $compressed !== $mp->photo_data) {
                        $mp->update(['compressed_photo_data' => $compressed]);
                        $this->line(" - Compressed member photo ID: {$mp->id}");
                    }
                }
            }
        }

        // 2. Homepage Slides
        if (Schema::hasColumn('homepage_slide_photos', 'compressed_image_data')) {
            $slides = HomepageSlidePhoto::whereNull('compressed_image_data')
                ->orWhere('compressed_image_data', '')
                ->get();

            $this->info("Found {$slides->count()} homepage slides to compress.");
            foreach ($slides as $sp) {
                if (!empty($sp->image_data)) {
                    $compressed = $optimizer->compressBase64($sp->image_data, 1200, 82);
                    if ($compressed && $compressed !== $sp->image_data) {
                        $sp->update(['compressed_image_data' => $compressed]);
                        $this->line(" - Compressed slide photo ID: {$sp->id}");
                    }
                }
            }
        }

        // 3. MediaFiles (Activities, Cash Transaction Proofs, Game Clues)
        if (Schema::hasColumn('media_files', 'compressed_path')) {
            $mediaFiles = MediaFile::whereNull('compressed_path')->get();
            $this->info("Found {$mediaFiles->count()} media files to check/compress.");

            foreach ($mediaFiles as $media) {
                if (!$media->isImage()) {
                    continue;
                }

                $sourcePath = public_path($media->file_path);
                if (!File::exists($sourcePath)) {
                    continue;
                }

                $dir = dirname($sourcePath);
                $ext = pathinfo($sourcePath, PATHINFO_EXTENSION) ?: 'jpg';
                $base = pathinfo($sourcePath, PATHINFO_FILENAME);
                $targetFilename = $base . '_thumb.' . $ext;
                $targetPath = $dir . DIRECTORY_SEPARATOR . $targetFilename;

                if (!File::exists($targetPath)) {
                    $maxDim = 900;
                    $ok = $optimizer->compressImageFile($sourcePath, $targetPath, $maxDim, 80);
                    if ($ok && File::exists($targetPath)) {
                        $relPath = str_replace(public_path(), '', $targetPath);
                        $relPath = ltrim(str_replace('\\', '/', $relPath), '/');
                        $media->update(['compressed_path' => $relPath]);
                        $this->line(" - Compressed media file ID {$media->id}: {$targetFilename}");
                    }
                } else {
                    $relPath = str_replace(public_path(), '', $targetPath);
                    $relPath = ltrim(str_replace('\\', '/', $relPath), '/');
                    $media->update(['compressed_path' => $relPath]);
                }
            }
        }

        $this->info('Photo compression completed successfully.');

        return 0;
    }
}
