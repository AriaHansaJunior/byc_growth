<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Activity;
use Illuminate\View\View;

class ActivityController extends Controller
{
    /**
     * Display the Admin Activities Management portal.
     * Manages event timelines, announcements, and photo galleries.
     */
    public function index(): View
    {
        $activities = Activity::with('photos')
            ->orderBy('event_date', 'desc')
            ->orderBy('created_at', 'desc')
            ->get();

        return view('admin.activities', [
            'activities' => $activities,
            'totalCount' => $activities->count(),
            'totalPhotos' => $activities->sum(fn ($a) => $a->photos->count()),
        ]);
    }
}
