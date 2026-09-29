<?php

namespace App\Http\Controllers;

use App\Models\Member;
use App\Services\GameStorageService;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class PageController extends Controller
{
    protected GameStorageService $storageService;

    public function __construct(GameStorageService $storageService)
    {
        $this->storageService = $storageService;
    }

    /**
     * Homepage (/) — Main BYC GROWTH Information Hub
     */
    public function home(): View
    {
        $finalScores = $this->storageService->getFinalScores();

        return view('welcome', [
            'finalScores' => $finalScores,
        ]);
    }

    /**
     * Combined About + Contact Page (/about)
     */
    public function about(): View
    {
        return view('pages.about');
    }

    /**
     * Members Public Directory (/members)
     * All members are equal: displays ONLY photo and full name.
     */
    public function members(): View
    {
        $members = Member::with('photo')
            ->where('is_active', true)
            ->orderBy('full_name', 'asc')
            ->get();

        return view('pages.members', [
            'members' => $members,
        ]);
    }

    /**
     * Contact Us route redirects to the combined About & Contact page
     */
    public function contact(): RedirectResponse
    {
        return redirect()->route('about', [], 301);
    }
}
