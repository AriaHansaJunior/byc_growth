<?php

namespace App\Http\Controllers;

use App\Models\HomepageSlide;
use App\Models\MediaFile;
use App\Models\Member;
use App\Services\ImageOptimizerService;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Schema;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class MediaController extends Controller
{
    protected ImageOptimizerService $optimizer;

    public function __construct(ImageOptimizerService $optimizer)
    {
        $this->optimizer = $optimizer;
    }

    /**
     * Serve a member photo:
     * - Compressed (sekian KB) when requested inside web page (`<img src="...">`).
     * - Full-size original when opened in a new tab (`Sec-Fetch-Dest: document`) or with `?full=1`.
     */
    public function memberPhoto(Request $request, int $id): Response
    {
        $member = Member::with(['memberPhoto', 'photo'])->find($id);
        if (!$member) {
            return $this->serveDummyAvatar();
        }

        // If member has dedicated database-stored photo (base64)
        if ($member->memberPhoto && !empty($member->memberPhoto->photo_data)) {
            $isFullRequest = $this->isFullSizeRequest($request);

            $dataUri = $member->memberPhoto->photo_data;

            if (!$isFullRequest) {
                // Serve compressed version for fast page loading
                if (!empty($member->memberPhoto->compressed_photo_data)) {
                    $dataUri = $member->memberPhoto->compressed_photo_data;
                } else {
                    // Compress on the fly and optionally cache
                    $compressed = $this->optimizer->compressBase64($member->memberPhoto->photo_data, 550, 80);
                    if ($compressed && $compressed !== $member->memberPhoto->photo_data) {
                        $dataUri = $compressed;
                        try {
                            if (Schema::hasColumn('member_photos', 'compressed_photo_data')) {
                                $member->memberPhoto->update(['compressed_photo_data' => $compressed]);
                            }
                        } catch (\Throwable $e) {
                            // Ignored if DB table not yet migrated
                        }
                    }
                }
            }

            $decoded = $this->optimizer->decodeBase64($dataUri);
            if ($decoded) {
                return response($decoded['binary'], 200, [
                    'Content-Type' => $decoded['mime'],
                    'Content-Length' => strlen($decoded['binary']),
                    'Cache-Control' => 'public, max-age=86400, must-revalidate',
                    'Vary' => 'Sec-Fetch-Dest, Accept',
                ]);
            }
        }

        // If member has file-based photo (MediaFile)
        if ($member->photo) {
            return $this->serveMediaFileResponse($request, $member->photo);
        }

        return $this->serveDummyAvatar();
    }

    /**
     * Serve a homepage slide photo:
     * - Compressed when displayed in carousel on home page.
     * - Full-size original when opened in new tab or requested with ?full=1.
     */
    public function slidePhoto(Request $request, int $id): Response
    {
        $slide = HomepageSlide::with(['slidePhoto', 'media'])->find($id);
        if (!$slide) {
            return $this->serveDummySlide();
        }

        if ($slide->slidePhoto && !empty($slide->slidePhoto->image_data)) {
            $isFullRequest = $this->isFullSizeRequest($request);
            $dataUri = $slide->slidePhoto->image_data;

            if (!$isFullRequest) {
                if (!empty($slide->slidePhoto->compressed_image_data)) {
                    $dataUri = $slide->slidePhoto->compressed_image_data;
                } else {
                    $compressed = $this->optimizer->compressBase64($slide->slidePhoto->image_data, 1200, 82);
                    if ($compressed && $compressed !== $slide->slidePhoto->image_data) {
                        $dataUri = $compressed;
                        try {
                            if (Schema::hasColumn('homepage_slide_photos', 'compressed_image_data')) {
                                $slide->slidePhoto->update(['compressed_image_data' => $compressed]);
                            }
                        } catch (\Throwable $e) {
                            // Ignored if DB table not yet migrated
                        }
                    }
                }
            }

            $decoded = $this->optimizer->decodeBase64($dataUri);
            if ($decoded) {
                return response($decoded['binary'], 200, [
                    'Content-Type' => $decoded['mime'],
                    'Content-Length' => strlen($decoded['binary']),
                    'Cache-Control' => 'public, max-age=86400, must-revalidate',
                    'Vary' => 'Sec-Fetch-Dest, Accept',
                ]);
            }
        }

        if ($slide->media) {
            return $this->serveMediaFileResponse($request, $slide->media);
        }

        return $this->serveDummySlide();
    }

    /**
     * Serve a MediaFile (activity photos, cash transaction proof/nota, game images).
     * - Returns compressed file by default for web page rendering.
     * - Returns original full-size file when opened in new tab or requested with ?full=1.
     */
    public function mediaFile(Request $request, int $id)
    {
        $media = MediaFile::findOrFail($id);

        return $this->serveMediaFileResponse($request, $media);
    }

    /**
     * Download original full-size media file (e.g. nota receipt).
     */
    public function download(Request $request, int $id): \Symfony\Component\HttpFoundation\Response
    {
        $media = MediaFile::findOrFail($id);
        $filename = $media->original_name ?: basename($media->file_path);

        // If stored directly in database
        if (!empty($media->file_data)) {
            $decoded = $this->optimizer->decodeBase64($media->file_data);
            if ($decoded) {
                return response($decoded['binary'], 200, [
                    'Content-Type' => $decoded['mime'],
                    'Content-Length' => strlen($decoded['binary']),
                    'Content-Disposition' => 'attachment; filename="' . addslashes($filename) . '"',
                    'Cache-Control' => 'no-cache, must-revalidate',
                ]);
            }
        }

        $fullPath = public_path($media->file_path);

        if (!File::exists($fullPath)) {
            abort(404, 'File not found on server.');
        }

        return response()->download($fullPath, $filename);
    }

    /**
     * Core helper: determine whether request wants full-size original or compressed output.
     * Chrome / Edge / Firefox send `Sec-Fetch-Dest: document` when opening a URL in a new tab!
     */
    protected function isFullSizeRequest(Request $request): bool
    {
        if ($request->has('full') || $request->query('size') === 'full') {
            return true;
        }

        // Top-level document navigation in modern browsers (Right click -> Open image in new tab)
        $fetchDest = strtolower((string) $request->header('Sec-Fetch-Dest', ''));
        if ($fetchDest === 'document') {
            return true;
        }

        return false;
    }

    /**
     * Serve a MediaFile as HTTP response.
     * First checks database-stored base64, falls back to disk file.
     */
    protected function serveMediaFileResponse(Request $request, MediaFile $media): Response
    {
        $isFull = $this->isFullSizeRequest($request);

        // 1. Serve directly from database if file_data is present
        if (!empty($media->file_data)) {
            $dataUri = $media->file_data;

            if (!$isFull && !empty($media->compressed_data)) {
                $dataUri = $media->compressed_data;
            } elseif (!$isFull) {
                $compressed = $this->optimizer->compressBase64($media->file_data, 900, 80);
                if ($compressed && $compressed !== $media->file_data) {
                    $dataUri = $compressed;
                    try {
                        if (Schema::hasColumn('media_files', 'compressed_data')) {
                            $media->update(['compressed_data' => $compressed]);
                        }
                    } catch (\Throwable $e) {
                        // Ignore silently
                    }
                }
            }

            $decoded = $this->optimizer->decodeBase64($dataUri);
            if ($decoded) {
                return response($decoded['binary'], 200, [
                    'Content-Type' => $decoded['mime'],
                    'Content-Length' => strlen($decoded['binary']),
                    'Cache-Control' => 'public, max-age=86400, must-revalidate',
                    'Vary' => 'Sec-Fetch-Dest, Accept',
                ]);
            }
        }

        // 2. Fallback to physical disk file
        $chosenPath = $media->file_path;
        if (!$isFull && !empty($media->compressed_path)) {
            $chosenPath = $media->compressed_path;
        }

        $absolutePath = public_path($chosenPath);
        if (!File::exists($absolutePath)) {
            $absolutePath = public_path($media->file_path);
        }

        if (!File::exists($absolutePath)) {
            abort(404, 'Image file not found.');
        }

        $content = File::get($absolutePath);
        $mime = $media->mime_type ?: File::mimeType($absolutePath) ?: 'image/jpeg';

        return response($content, 200, [
            'Content-Type' => $mime,
            'Content-Length' => strlen($content),
            'Cache-Control' => 'public, max-age=86400, must-revalidate',
            'Vary' => 'Sec-Fetch-Dest, Accept',
        ]);
    }

    /**
     * Dummy avatar fallback SVG.
     */
    protected function serveDummyAvatar(): Response
    {
        $svg = '<svg xmlns="http://www.w3.org/2000/svg" width="140" height="140" viewBox="0 0 140 140"><circle cx="70" cy="70" r="70" fill="#f5ede0"/><text x="50%" y="54%" font-family="Arial, sans-serif" font-size="44" font-weight="bold" fill="#1b4d3e" text-anchor="middle" dominant-baseline="middle">BYC</text></svg>';

        return response($svg, 200, [
            'Content-Type' => 'image/svg+xml',
            'Cache-Control' => 'public, max-age=86400',
        ]);
    }

    /**
     * Dummy slide fallback.
     */
    protected function serveDummySlide(): Response
    {
        $dummyPath = public_path('assets/images/group-photo-dummy.svg');
        if (File::exists($dummyPath)) {
            $svg = File::get($dummyPath);
            return response($svg, 200, [
                'Content-Type' => 'image/svg+xml',
                'Cache-Control' => 'public, max-age=86400',
            ]);
        }

        return $this->serveDummyAvatar();
    }
}
