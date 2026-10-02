<?php

namespace App\Http\Controllers;

use App\Models\Activity;
use App\Models\MediaFile;
use App\Services\MediaUploadService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ActivityController extends Controller
{
    protected MediaUploadService $mediaService;

    public function __construct(MediaUploadService $mediaService)
    {
        $this->mediaService = $mediaService;
    }

    /**
     * Display the public Activity timeline/gallery page.
     */
    public function index(): View
    {
        $activities = Activity::with('photos')
            ->orderByRaw('COALESCE(start_date, event_date) desc')
            ->orderBy('created_at', 'desc')
            ->get();

        return view('user.activities', [
            'activities' => $activities,
        ]);
    }

    /**
     * Store a newly created activity (Admin only).
     */
    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'start_date' => 'nullable|date',
            'end_date' => 'nullable|date',
            'event_date' => 'nullable|date',
            'description' => 'required|string',
            'photos' => 'nullable|array',
            'photos.*' => 'image|mimes:jpeg,png,jpg,webp,gif|max:51200',
        ]);

        $startDate = $validated['start_date'] ?? $validated['event_date'] ?? now()->toDateString();
        $endDate = $validated['end_date'] ?? $startDate;
        if ($endDate < $startDate) {
            $endDate = $startDate;
        }

        $activity = Activity::create([
            'name' => $validated['name'],
            'start_date' => $startDate,
            'end_date' => $endDate,
            'event_date' => $startDate,
            'description' => $validated['description'],
        ]);

        if ($request->hasFile('photos')) {
            foreach ($request->file('photos') as $photoFile) {
                if ($photoFile->isValid()) {
                    $this->mediaService->storeImage(
                        $photoFile,
                        'activity',
                        Activity::class,
                        $activity->id
                    );
                }
            }
        }

        $target = ($request->header('referer') && str_contains($request->header('referer'), '/admin/activities'))
            ? route('admin.activities')
            : route('activity');

        return redirect($target)->with('success', 'Activity created successfully.');
    }

    /**
     * Update the specified activity (Admin only).
     */
    public function update(Request $request, int $id): RedirectResponse
    {
        $activity = Activity::findOrFail($id);

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'start_date' => 'nullable|date',
            'end_date' => 'nullable|date',
            'event_date' => 'nullable|date',
            'description' => 'required|string',
            'photos' => 'nullable|array',
            'photos.*' => 'image|mimes:jpeg,png,jpg,webp,gif|max:51200',
            'remove_photo_ids' => 'nullable|array',
            'remove_photo_ids.*' => 'integer|exists:media_files,id',
        ]);

        $fallbackStart = $activity->start_date ? $activity->start_date->format('Y-m-d') : ($activity->event_date ? $activity->event_date->format('Y-m-d') : now()->toDateString());
        $startDate = $validated['start_date'] ?? $validated['event_date'] ?? $fallbackStart;
        $endDate = $validated['end_date'] ?? $startDate;
        if ($endDate < $startDate) {
            $endDate = $startDate;
        }

        $activity->update([
            'name' => $validated['name'],
            'start_date' => $startDate,
            'end_date' => $endDate,
            'event_date' => $startDate,
            'description' => $validated['description'],
        ]);

        // Delete requested photos
        if (!empty($validated['remove_photo_ids'])) {
            $photosToDelete = MediaFile::where('fileable_type', Activity::class)
                ->where('fileable_id', $activity->id)
                ->whereIn('id', $validated['remove_photo_ids'])
                ->get();

            foreach ($photosToDelete as $photo) {
                $this->mediaService->deleteMediaFile($photo);
            }
        }

        // Upload new photos
        if ($request->hasFile('photos')) {
            foreach ($request->file('photos') as $photoFile) {
                if ($photoFile->isValid()) {
                    $this->mediaService->storeImage(
                        $photoFile,
                        'activity',
                        Activity::class,
                        $activity->id
                    );
                }
            }
        }

        $target = ($request->header('referer') && str_contains($request->header('referer'), '/admin/activities'))
            ? route('admin.activities')
            : route('activity');

        return redirect($target)->with('success', 'Activity updated successfully.');
    }

    /**
     * Remove the specified activity and all supporting photos (Admin only).
     */
    public function destroy(Request $request, int $id): RedirectResponse
    {
        $activity = Activity::findOrFail($id);

        // Delete associated photos
        foreach ($activity->photos as $photo) {
            $this->mediaService->deleteMediaFile($photo);
        }

        // Clean up any remaining media files pointing to this activity
        $remainingMedia = MediaFile::where('fileable_type', Activity::class)
            ->where('fileable_id', $activity->id)
            ->get();
        foreach ($remainingMedia as $media) {
            $this->mediaService->deleteMediaFile($media);
        }

        $activity->delete();

        $target = ($request->header('referer') && str_contains($request->header('referer'), '/admin/activities'))
            ? route('admin.activities')
            : route('activity');

        return redirect($target)->with('success', 'Activity deleted successfully.');
    }
}
