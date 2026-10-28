<?php

namespace App\Http\Controllers;

use App\Models\HomepageSlide;
use App\Models\Member;
use App\Models\MemberPhoto;
use App\Models\MediaFile;
use App\Services\GameStorageService;
use App\Services\ImageOptimizerService;
use App\Services\MediaUploadService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schema;
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

    /**
     * Member Profile Management View (/profile)
     */
    public function profile(): View|RedirectResponse
    {
        /** @var \App\Models\User $user */
        $user = Auth::guard('web')->user() ?? Auth::guard('admin')->user() ?? Auth::user();
        if (!$user) {
            return redirect()->route('login', [
                'redirect' => route('profile'),
            ])->with('error', 'Please sign in to view your profile.');
        }

        $user->loadMissing(['member.memberPhoto', 'member.photo']);

        return view('user.profile', [
            'user' => $user,
            'member' => $user->member,
        ]);
    }

    /**
     * Update Member Profile Details (/profile)
     * Allows user to update their profile photo, email, and password.
     * Profile photo is immediately synchronized with the Member Directory.
     */
    public function updateProfile(Request $request): RedirectResponse
    {
        /** @var \App\Models\User $user */
        $user = Auth::guard('web')->user() ?? Auth::guard('admin')->user() ?? Auth::user();
        if (!$user) {
            return redirect()->route('login')->with('error', 'Please sign in to update your profile.');
        }

        $validated = $request->validate([
            'email' => 'required|email|max:255|unique:users,email,' . $user->id,
            'new_password' => 'nullable|string|min:6',
            'photo' => 'nullable|image|mimes:jpeg,png,jpg,webp|max:51200',
            'photo_base64' => 'nullable|string',
        ]);

        $user->email = strtolower(trim($validated['email']));

        if (!empty($validated['new_password'])) {
            $user->password = Hash::make($validated['new_password']);
        }

        $user->save();

        // Handle profile photo upload (supports base64 data URI from canvas cropper or file upload)
        $photoBase64 = null;
        $mime = 'image/jpeg';
        $originalName = 'profile_avatar.jpg';

        if ($request->filled('photo_base64') && str_starts_with($request->input('photo_base64'), 'data:image/')) {
            $photoBase64 = $request->input('photo_base64');
            if (preg_match('/^data:(image\/[a-zA-Z0-9\+\-\.]+);base64,/', $photoBase64, $m)) {
                $mime = $m[1];
            }
            $originalName = 'cropped_avatar_' . time() . '.jpg';
        } elseif ($request->hasFile('photo') && $request->file('photo')->isValid()) {
            $file = $request->file('photo');
            $mime = $file->getClientMimeType() ?: 'image/jpeg';
            $content = file_get_contents($file->getRealPath());
            $photoBase64 = 'data:' . $mime . ';base64,' . base64_encode($content);
            $originalName = $file->getClientOriginalName();
        }

        if ($photoBase64) {
            $member = $user->member;
            if (!$member) {
                $member = Member::create([
                    'full_name' => $user->name ?: $user->username,
                    'is_active' => true,
                    'last_action_by' => $user->email,
                    'last_action_type' => 'created',
                    'last_action_at' => now(),
                ]);
                $user->member_id = $member->id;
                $user->save();
            }

            /** @var ImageOptimizerService $optimizer */
            $optimizer = app(ImageOptimizerService::class);
            $rawBinary = base64_decode(preg_replace('/^data:image\/[a-zA-Z0-9\+\-\.]+;base64,/', '', $photoBase64));
            $rawSize = strlen($rawBinary);

            $optimizedBase64 = $rawSize > 300 * 1024
                ? ($optimizer->compressBase64($photoBase64, 1000, 85) ?: $photoBase64)
                : $photoBase64;

            $data = [
                'member_id' => $member->id,
                'photo_data' => $optimizedBase64,
                'original_name' => $originalName,
                'mime_type' => $mime,
                'file_size' => $rawSize,
                'updated_at' => now(),
            ];

            if (Schema::hasColumn('member_photos', 'compressed_photo_data')) {
                $data['compressed_photo_data'] = $optimizer->compressBase64($optimizedBase64, 550, 80) ?: $optimizedBase64;
            }

            $memberPhoto = $member->memberPhoto;
            if ($memberPhoto) {
                $memberPhoto->update($data);
            } else {
                MemberPhoto::create($data);
            }

            // Touch member to bust caches everywhere
            $member->touch();

            if ($member->photo_file_id) {
                /** @var MediaUploadService $mediaService */
                $mediaService = app(MediaUploadService::class);
                if ($member->photo) {
                    $mediaService->deleteMediaFile($member->photo);
                }
                $member->update(['photo_file_id' => null]);
            }
        }

        return redirect()->route('profile')->with('success', 'Your profile details have been updated successfully.');
    }
}
