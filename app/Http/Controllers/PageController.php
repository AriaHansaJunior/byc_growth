<?php

namespace App\Http\Controllers;

use App\Models\HomepageSlide;
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
        $slides = HomepageSlide::with(['slidePhoto', 'media'])
            ->active()
            ->ordered()
            ->get();

        return view('welcome', [
            'finalScores' => $finalScores,
            'slides' => $slides,
        ]);
    }

    /**
     * Combined About + Contact Page (/about)
     */
    public function about(): View
    {
        return view('user.about');
    }

    /**
     * Members Public Directory (/members)
     * All members are equal: displays ONLY photo and full name.
     */
    public function members(): View
    {
        $now = \Carbon\Carbon::now('Asia/Jakarta');
        $today = $now->format('m-d');
        $currentYear = (int) $now->year;

        $members = Member::with(['photo', 'memberPhoto', 'user'])
            ->where('is_active', true)
            ->orderByRaw("CASE WHEN date_of_birth IS NOT NULL AND DATE_FORMAT(date_of_birth, '%m-%d') = ? THEN 0 ELSE 1 END ASC", [$today])
            ->orderBy('full_name', 'asc')
            ->get();

        $userLetters = collect();
        if (\Illuminate\Support\Facades\Auth::check()) {
            $userLetters = \App\Models\BirthdayLetter::where('user_id', \Illuminate\Support\Facades\Auth::id())
                ->where('birthday_year', $currentYear)
                ->get()
                ->keyBy('member_id');
        }

        $users = \App\Models\User::where('role', 'user')->orderBy('username')->get();

        return view('user.members', [
            'members' => $members,
            'userLetters' => $userLetters,
            'users' => $users,
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
