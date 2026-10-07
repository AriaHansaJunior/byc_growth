<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\ActivityController as BaseActivityController;
use App\Models\Activity;
use App\Services\MediaUploadService;
use App\Models\MediaFile;
use Carbon\Carbon;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ActivityController extends BaseActivityController
{
    public function __construct(MediaUploadService $mediaService)
    {
        parent::__construct($mediaService);
    }

    /**
     * Display the Admin Activities Management portal.
     * Manages event timelines, announcements, and photo galleries with filtering.
     */
    public function index(Request $request = null): View
    {
        $request = $request ?? request();

        $query = Activity::with('photos');

        // Search: Name or Description
        if ($request->filled('search')) {
            $search = trim($request->input('search'));
            $escaped = addcslashes($search, '%_\\');
            $query->where(function ($q) use ($escaped) {
                $q->where('name', 'like', "%{$escaped}%")
                  ->orWhere('description', 'like', "%{$escaped}%");
            });
        }

        // Filter: Specific Date
        if ($request->filled('date')) {
            $date = trim($request->input('date'));
            try {
                $parsedDate = Carbon::parse($date)->toDateString();
                $query->where(function ($q) use ($parsedDate) {
                    $q->whereDate('start_date', $parsedDate)
                      ->orWhereDate('event_date', $parsedDate)
                      ->orWhereDate('end_date', $parsedDate);
                });
            } catch (\Throwable $e) {
                // Ignore invalid date safely
            }
        }

        // Sorting: date_desc, date_asc, name_asc, name_desc
        $sort = $request->input('sort', 'date_desc');
        switch ($sort) {
            case 'date_asc':
                $query->orderByRaw('COALESCE(start_date, event_date) asc')
                      ->orderBy('created_at', 'asc');
                break;
            case 'name_asc':
                $query->orderBy('name', 'asc');
                break;
            case 'name_desc':
                $query->orderBy('name', 'desc');
                break;
            case 'date_desc':
            default:
                $query->orderByRaw('COALESCE(start_date, event_date) desc')
                      ->orderBy('created_at', 'desc');
                break;
        }

        $activities = $query->get();

        return view('admin.activities', [
            'activities' => $activities,
            'totalCount' => Activity::count(),
            'filteredCount' => $activities->count(),
            'totalPhotos' => $activities->sum(fn ($a) => $a->photos->count()),
            'filters' => [
                'search' => $request->input('search', ''),
                'date' => $request->input('date', ''),
                'sort' => $sort,
            ],
        ]);
    }

    /**
     * Remove multiple activities and all associated photos.
     */
    public function batchDestroy(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'ids' => 'required|array|min:1',
            'ids.*' => 'required|integer|exists:activities,id',
        ]);

        $activities = Activity::with('photos')->whereIn('id', $validated['ids'])->get();
        $count = $activities->count();

        foreach ($activities as $activity) {
            foreach ($activity->photos as $photo) {
                $this->mediaService->deleteMediaFile($photo);
            }

            $remainingMedia = MediaFile::where('fileable_type', Activity::class)
                ->where('fileable_id', $activity->id)
                ->get();
            foreach ($remainingMedia as $media) {
                $this->mediaService->deleteMediaFile($media);
            }

            $activity->delete();
        }

        $adminUser = \Illuminate\Support\Facades\Auth::guard('admin')->user() ?? \Illuminate\Support\Facades\Auth::guard('web')->user() ?? \Illuminate\Support\Facades\Auth::user();
        \App\Models\AuditLog::record($adminUser, 'batch_deleted', 'activity', null, "Batch deleted {$count} activities");

        return redirect()->route('admin.activities')
            ->with('success', "Selected {$count} " . ($count === 1 ? 'activity has' : 'activities have') . ' been deleted successfully.');
    }
}
