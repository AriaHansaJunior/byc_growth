<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\HomepageSlide;
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
        $slides = HomepageSlide::with('media')
            ->ordered()
            ->get();

        return view('admin.homepage', [
            'slides' => $slides,
        ]);
    }

    /**
     * Store a newly uploaded slideshow photo.
     */
    public function storeSlide(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'image' => 'required|file|image|mimes:jpeg,png,jpg,webp,gif|max:5120',
            'title' => 'nullable|string|max:255',
            'caption' => 'nullable|string|max:500',
        ]);

        $media = null;

        try {
            DB::beginTransaction();

            $maxOrder = (int) HomepageSlide::max('sort_order');
            $nextOrder = $maxOrder + 1;

            $slide = HomepageSlide::create([
                'title' => $validated['title'] ?? null,
                'caption' => $validated['caption'] ?? null,
                'sort_order' => $nextOrder,
                'is_active' => true,
            ]);

            $media = $this->mediaService->storeImage(
                $request->file('image'),
                'hero_slide',
                HomepageSlide::class,
                $slide->id
            );

            $slide->update(['media_file_id' => $media->id]);

            DB::commit();

            return redirect()->route('admin.homepage')->with('success', 'Slideshow photo added successfully.');
        } catch (\Throwable $e) {
            DB::rollBack();

            if ($media) {
                $this->mediaService->deleteMediaFile($media);
            }

            Log::error('Failed to upload slideshow photo: ' . $e->getMessage(), ['exception' => $e]);

            return back()->withInput()->withErrors(['error' => 'Failed to upload slideshow photo. Please try again.']);
        }
    }

    /**
     * Delete an existing slideshow image and clean up associated media.
     */
    public function destroySlide(int $id): RedirectResponse
    {
        $slide = HomepageSlide::with('media')->findOrFail($id);

        try {
            DB::beginTransaction();

            $media = $slide->media;

            $slide->delete();

            if ($media && $media->fileable_type === HomepageSlide::class) {
                $this->mediaService->deleteMediaFile($media);
            }

            $this->normalizeSlideOrders();

            DB::commit();

            return redirect()->route('admin.homepage')->with('success', 'Slideshow image deleted successfully.');
        } catch (\Throwable $e) {
            DB::rollBack();
            Log::error('Failed to delete slideshow slide: ' . $e->getMessage(), ['exception' => $e]);

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

        return redirect()->route('admin.homepage')->with('success', 'Slides reordered successfully.');
    }

    /**
     * Move a slide one position up in the sequence.
     */
    public function moveSlideUp(int $id): RedirectResponse
    {
        $this->normalizeSlideOrders();

        $slide = HomepageSlide::findOrFail($id);
        $prevSlide = HomepageSlide::where('sort_order', '<', $slide->sort_order)
            ->orderBy('sort_order', 'desc')
            ->first();

        if ($prevSlide) {
            DB::transaction(function () use ($slide, $prevSlide) {
                $prevOrder = $prevSlide->sort_order;
                $slideOrder = $slide->sort_order;

                $slide->update(['sort_order' => $prevOrder]);
                $prevSlide->update(['sort_order' => $slideOrder]);
            });
        }

        return redirect()->route('admin.homepage')->with('success', 'Slide order updated successfully.');
    }

    /**
     * Move a slide one position down in the sequence.
     */
    public function moveSlideDown(int $id): RedirectResponse
    {
        $this->normalizeSlideOrders();

        $slide = HomepageSlide::findOrFail($id);
        $nextSlide = HomepageSlide::where('sort_order', '>', $slide->sort_order)
            ->orderBy('sort_order', 'asc')
            ->first();

        if ($nextSlide) {
            DB::transaction(function () use ($slide, $nextSlide) {
                $nextOrder = $nextSlide->sort_order;
                $slideOrder = $slide->sort_order;

                $slide->update(['sort_order' => $nextOrder]);
                $nextSlide->update(['sort_order' => $slideOrder]);
            });
        }

        return redirect()->route('admin.homepage')->with('success', 'Slide order updated successfully.');
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
