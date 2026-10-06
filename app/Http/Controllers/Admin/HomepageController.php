<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\HomepageSlide;
use App\Models\HomepageSlidePhoto;
use App\Services\MediaUploadService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\View\View;

class HomepageController extends Controller
{
    protected MediaUploadService $mediaService;

    public function __construct(MediaUploadService $mediaService)
    {
        $this->mediaService = $mediaService;
    }

    /**
     * Display the Admin Homepage Management portal.
     * Manages hero slideshow, community highlights, and showcase data.
     */
    public function index(): View
    {
        $slides = HomepageSlide::with(['slidePhoto', 'media'])
            ->ordered()
            ->get();

        return view('admin.homepage', [
            'slides' => $slides,
        ]);
    }

    /**
     * Store a newly uploaded slideshow photo directly in the database.
     */
    public function storeSlide(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'image' => 'required|file|image|mimes:jpeg,png,jpg,webp,gif|max:5120',
            'title' => 'nullable|string|max:255',
            'caption' => 'nullable|string|max:500',
        ]);

        try {
            DB::beginTransaction();

            $maxOrder = (int) HomepageSlide::max('sort_order');
            $nextOrder = $maxOrder + 1;

            $file = $request->file('image');
            $originalName = $file->getClientOriginalName();
            $title = !empty($validated['title']) ? $validated['title'] : $originalName;
            $caption = $validated['caption'] ?? null;

            $mime = $file->getClientMimeType() ?: 'image/jpeg';
            $content = file_get_contents($file->getRealPath());
            $base64 = 'data:' . $mime . ';base64,' . base64_encode($content);

            $slide = HomepageSlide::create([
                'title' => $title,
                'caption' => $caption,
                'sort_order' => $nextOrder,
                'is_active' => true,
            ]);

            HomepageSlidePhoto::create([
                'homepage_slide_id' => $slide->id,
                'image_data' => $base64,
                'original_name' => $originalName,
                'mime_type' => $mime,
                'file_size' => strlen($content),
            ]);

            DB::commit();

            if ($request->wantsJson() || $request->ajax()) {
                return response()->json([
                    'success' => true,
                    'message' => 'Slideshow photo added successfully.',
                    'slide' => [
                        'id' => $slide->id,
                        'title' => $slide->title,
                        'image_url' => $base64,
                        'sort_order' => $slide->sort_order,
                    ],
                ]);
            }

            return redirect()->to(route('admin.homepage') . '#slideshow-management')->with('success', 'Slideshow photo added successfully.');
        } catch (\Throwable $e) {
            DB::rollBack();

            Log::error('Failed to upload slideshow photo: ' . $e->getMessage(), ['exception' => $e]);

            if ($request->wantsJson() || $request->ajax()) {
                return response()->json(['error' => 'Failed to upload slideshow photo. Please try again.'], 500);
            }

            return back()->withInput()->withErrors(['error' => 'Failed to upload slideshow photo. Please try again.']);
        }
    }

    /**
     * Update an existing slideshow photo's image / position directly in the database.
     */
    public function updateSlide(Request $request, int $id): RedirectResponse|JsonResponse
    {
        $slide = HomepageSlide::with(['slidePhoto', 'media'])->findOrFail($id);

        $validated = $request->validate([
            'image' => 'required|file|image|mimes:jpeg,png,jpg,webp,gif|max:5120',
            'title' => 'nullable|string|max:255',
            'caption' => 'nullable|string|max:500',
        ]);

        try {
            DB::beginTransaction();

            $file = $request->file('image');
            $originalName = $file->getClientOriginalName();
            $title = !empty($validated['title']) ? $validated['title'] : ($slide->title ?: $originalName);

            $mime = $file->getClientMimeType() ?: 'image/jpeg';
            $content = file_get_contents($file->getRealPath());
            $base64 = 'data:' . $mime . ';base64,' . base64_encode($content);

            $slide->update([
                'title' => $title,
                'caption' => $validated['caption'] ?? $slide->caption,
            ]);

            $slidePhoto = $slide->slidePhoto;
            if ($slidePhoto) {
                $slidePhoto->update([
                    'image_data' => $base64,
                    'original_name' => $originalName,
                    'mime_type' => $mime,
                    'file_size' => strlen($content),
                ]);
            } else {
                HomepageSlidePhoto::create([
                    'homepage_slide_id' => $slide->id,
                    'image_data' => $base64,
                    'original_name' => $originalName,
                    'mime_type' => $mime,
                    'file_size' => strlen($content),
                ]);
            }

            // Clean up old media file from disk if legacy record existed
            if ($slide->media && $slide->media->fileable_type === HomepageSlide::class) {
                $this->mediaService->deleteMediaFile($slide->media);
                $slide->update(['media_file_id' => null]);
            }

            DB::commit();

            if ($request->wantsJson() || $request->ajax()) {
                return response()->json([
                    'success' => true,
                    'message' => 'Slideshow photo updated successfully.',
                    'image_url' => $base64,
                ]);
            }

            return redirect()->to(route('admin.homepage') . '#slideshow-management')->with('success', 'Slideshow photo updated successfully.');
        } catch (\Throwable $e) {
            DB::rollBack();

            Log::error('Failed to update slideshow photo: ' . $e->getMessage(), ['exception' => $e]);

            if ($request->wantsJson() || $request->ajax()) {
                return response()->json(['error' => 'Failed to update slideshow photo. Please try again.'], 500);
            }

            return back()->withErrors(['error' => 'Failed to update slideshow photo. Please try again.']);
        }
    }

    /**
     * Delete an existing slideshow image and clean up associated media.
     */
    public function destroySlide(Request $request, int $id): RedirectResponse|JsonResponse
    {
        $slide = HomepageSlide::with(['slidePhoto', 'media'])->findOrFail($id);

        try {
            DB::beginTransaction();

            $media = $slide->media;

            $slide->delete();

            if ($media && $media->fileable_type === HomepageSlide::class) {
                $this->mediaService->deleteMediaFile($media);
            }

            $this->normalizeSlideOrders();

            DB::commit();

            if ($request->wantsJson() || $request->ajax()) {
                return response()->json([
                    'success' => true,
                    'message' => 'Slideshow image deleted successfully.',
                ]);
            }

            return redirect()->to(route('admin.homepage') . '#slideshow-management')->with('success', 'Slideshow image deleted successfully.');
        } catch (\Throwable $e) {
            DB::rollBack();
            Log::error('Failed to delete slideshow slide: ' . $e->getMessage(), ['exception' => $e]);

            if ($request->wantsJson() || $request->ajax()) {
                return response()->json(['error' => 'Failed to delete slideshow image.'], 500);
            }

            return back()->withErrors(['error' => 'Failed to delete slideshow image. Please try again.']);
        }
    }

    /**
     * Reorder slideshow images from an ordered array of slide IDs.
     */
    public function reorderSlides(Request $request): JsonResponse|RedirectResponse
    {
        $validated = $request->validate([
            'order' => 'required|array|min:1',
            'order.*' => 'integer|exists:homepage_slides,id',
        ]);

        $allSlideIds = HomepageSlide::pluck('id')->all();

        // Verify all IDs belong to homepage_slides and no foreign/tampered IDs
        if (count(array_diff($validated['order'], $allSlideIds)) > 0) {
            if ($request->wantsJson() || $request->ajax()) {
                return response()->json(['error' => 'Invalid slide IDs provided in reorder list.'], 422);
            }
            return back()->withErrors(['error' => 'Invalid slide IDs provided in reorder list.']);
        }

        // Verify no duplicate IDs in reorder payload
        if (count($validated['order']) !== count(array_unique($validated['order']))) {
            if ($request->wantsJson() || $request->ajax()) {
                return response()->json(['error' => 'Duplicate slide IDs detected in reorder list.'], 422);
            }
            return back()->withErrors(['error' => 'Duplicate slide IDs detected in reorder list.']);
        }

        DB::transaction(function () use ($validated) {
            foreach ($validated['order'] as $index => $id) {
                HomepageSlide::where('id', $id)->update(['sort_order' => $index + 1]);
            }
        });

        if ($request->wantsJson() || $request->ajax()) {
            return response()->json([
                'success' => true,
                'message' => 'Slides reordered successfully.',
            ]);
        }

        return redirect()->to(route('admin.homepage') . '#slideshow-management')->with('success', 'Slides reordered successfully.');
    }


    /**
     * Helper to keep sort orders normalized sequentially (1, 2, 3...).
     */
    protected function normalizeSlideOrders(): void
    {
        $slides = HomepageSlide::orderBy('sort_order', 'asc')
            ->orderBy('id', 'asc')
            ->get();

        foreach ($slides as $index => $slide) {
            $expected = $index + 1;
            if ($slide->sort_order !== $expected) {
                $slide->update(['sort_order' => $expected]);
            }
        }
    }
}
