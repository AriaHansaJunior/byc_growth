<?php

namespace App\Http\Controllers;

use App\Models\HomepageSlide;
use App\Models\Member;
use App\Services\GameStorageService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
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
     * Default sorted A-Z by full name. If authenticated user is linked to a member profile,
     * their own profile is prioritised at index 0 (top-left).
     */
    public function members(Request $request): View
    {
        $now = \Carbon\Carbon::now('Asia/Jakarta');
        $currentYear = (int) $now->year;

        // Detect linked member for authenticated normal user
        $authUser = Auth::guard('web')->user() ?? Auth::user();
        $userMemberId = ($authUser && !$authUser->isAdmin() && !empty($authUser->member_id))
            ? (int) $authUser->member_id
            : null;

        $query = Member::with(['photo', 'memberPhoto', 'user'])
            ->where('is_active', true);

        // Search: Full Name
        $search = trim((string) $request->input('search', ''));
        if ($search !== '') {
            $escaped = addcslashes($search, '%_\\');
            $query->where('full_name', 'like', "%{$escaped}%");
        }

        // Priority 1: Current linked user profile at position 1 (top-left)
        if ($userMemberId) {
            $query->orderByRaw("CASE WHEN id = ? THEN 0 ELSE 1 END ASC", [$userMemberId]);
        }

        // Default order: Alphabetical A-Z by full name
        $query->orderBy('full_name', 'asc');

        $members = $query->get();

        $userLetters = collect();
        if (Auth::check()) {
            $userLetters = \App\Models\BirthdayLetter::where('user_id', Auth::id())
                ->where('birthday_year', $currentYear)
                ->get()
                ->keyBy('member_id');
        }

        $users = \App\Models\User::where('role', 'user')->orderBy('username')->get();

        return view('user.members', [
            'members' => $members,
            'userLetters' => $userLetters,
            'users' => $users,
            'currentUserMemberId' => $userMemberId,
            'search' => $search,
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
