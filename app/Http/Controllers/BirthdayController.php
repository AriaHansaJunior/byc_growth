<?php

namespace App\Http\Controllers;

use App\Models\BirthdayLetter;
use App\Models\Member;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class BirthdayController extends Controller
{
    /**
     * Get members celebrating their birthday today in Surabaya (Asia/Jakarta).
     * Public information endpoint for displaying the birthday celebratory modal.
     */
    public function today(): JsonResponse
    {
        $today = Carbon::now('Asia/Jakarta');
        $members = Member::with('photo')
            ->where('is_active', true)
            ->whereNotNull('date_of_birth')
            ->whereRaw("DATE_FORMAT(date_of_birth, '%m-%d') = ?", [$today->format('m-d')])
            ->get();

        return response()->json([
            'date' => $today->format('Y-m-d'),
            'count' => $members->count(),
            'members' => $members->map(function ($m) {
                return [
                    'id' => $m->id,
                    'full_name' => $m->full_name,
                    'photo_url' => $m->photo_url,
                ];
            }),
        ]);
    }

    /**
     * Submit a birthday letter for a celebrating member.
     * Enforces authentication, self-wish prevention, and one-letter-per-sender-recipient-year invariant.
     */
    public function submitLetter(Request $request)
    {
        // 1. Sender must be an authenticated user
        $user = Auth::guard('web')->user() ?? Auth::guard('admin')->user() ?? Auth::user();
        if (!$user) {
            if ($request->expectsJson() || $request->wantsJson() || $request->ajax()) {
                return response()->json([
                    'message' => 'Unauthenticated. Please sign in to send a birthday letter.',
                ], 401);
            }

            return redirect()->route('login')->with('error', 'Please sign in to send a birthday letter.');
        }

        // 2. Validate input payload
        $validated = $request->validate([
            'member_id' => 'required|exists:members,id',
            'message' => 'required|string|min:3|max:3000',
            'is_anonymous' => 'nullable|boolean',
            'is_self_wish' => 'nullable|boolean',
            'sender_name' => 'nullable|string|max:255',
        ]);

        $recipient = Member::findOrFail($validated['member_id']);

        // 3. Determine if this is a personal self birthday resolution
        $isOwnerMember = (bool) ($user->member_id && (int) $user->member_id === (int) $recipient->id);

        // Guard against crafted requests attempting to create a self-resolution for another member
        if ($request->boolean('is_self_wish') && !$isOwnerMember) {
            if ($request->wantsJson() || $request->ajax()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Forbidden. You can only write a birthday resolution for yourself.',
                ], 403);
            }
            abort(403, 'Forbidden. You can only write a birthday resolution for yourself.');
        }

        $isSelfWish = $isOwnerMember;

        // 4. Server-authoritative birthday year (Asia/Jakarta timezone)
        $birthdayYear = (int) Carbon::now('Asia/Jakarta')->year;

        // 5. Invariant: ONE sender user + ONE birthday recipient member + ONE birthday year = ONE letter/resolution
        $existingLetter = BirthdayLetter::where('user_id', $user->id)
            ->where('member_id', $recipient->id)
            ->where('birthday_year', $birthdayYear)
            ->first();

        if ($existingLetter) {
            $duplicateMsg = $isSelfWish
                ? 'You have already saved your birthday resolution for this year.'
                : 'You have already sent a birthday letter to this member for this year.';

            if ($request->wantsJson() || $request->ajax()) {
                return response()->json([
                    'success' => false,
                    'message' => $duplicateMsg,
                    'existing_letter_id' => $existingLetter->id,
                ], 422);
            }

            return back()->withErrors(['member_id' => $duplicateMsg]);
        }

        // 6. Anonymous / Display preference handling
        // Personal resolutions are never anonymous
        $isAnonymous = $isSelfWish ? false : $request->boolean('is_anonymous');
        $senderName = $isAnonymous
            ? 'Anonymous'
            : ($user->member ? $user->member->full_name : (!empty($validated['sender_name']) ? $validated['sender_name'] : $user->name));

        $letter = BirthdayLetter::create([
            'member_id' => $recipient->id,
            'user_id' => $user->id,
            'birthday_year' => $birthdayYear,
            'is_anonymous' => $isAnonymous,
            'is_self_wish' => $isSelfWish,
            'sender_name' => $senderName,
            'message' => $validated['message'],
        ]);

        $successMsg = $isSelfWish
            ? 'Your birthday resolution has been saved!'
            : 'Your birthday letter has been sent with love!';

        if ($request->wantsJson() || $request->ajax()) {
            return response()->json([
                'success' => true,
                'message' => $successMsg,
                'is_self_wish' => $isSelfWish,
                'letter_id' => $letter->id,
                'letter' => $letter,
            ]);
        }

        return back()->with('success', $successMsg);
    }

    /**
     * Update an existing birthday letter.
     * Normal users can only edit their own letter while the recipient's birthday date is still today.
     * Administrators have full control and can edit at any time.
     */
    public function updateLetter(Request $request, int $id): JsonResponse
    {
        $user = Auth::guard('web')->user() ?? Auth::guard('admin')->user() ?? Auth::user();
        if (!$user) {
            return response()->json(['message' => 'Unauthenticated.'], 401);
        }

        $letter = BirthdayLetter::with('member')->findOrFail($id);

        // Authorization check
        if (!$user->isAdmin()) {
            // Must be the sender's own letter
            if ((int) $letter->user_id !== (int) $user->id) {
                return response()->json([
                    'success' => false,
                    'message' => 'Forbidden. You can only edit your own birthday letters.',
                ], 403);
            }

            // If self-resolution, must also match the authenticated user's linked member_id
            if ($letter->isSelfWish() && (!$user->member_id || (int) $letter->member_id !== (int) $user->member_id)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Forbidden. You can only edit your own birthday resolution.',
                ], 403);
            }

            // Normal user can only edit while recipient's birthday date is still today in Asia/Jakarta
            if (!$letter->member || !$letter->member->isBirthdayToday()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Birthday letters can only be edited during the recipient\'s birthday.',
                ], 422);
            }
        }

        $validated = $request->validate([
            'message' => 'required|string|min:3|max:3000',
            'is_anonymous' => 'nullable|boolean',
        ]);

        if ($letter->isSelfWish()) {
            $letter->is_anonymous = false;
        } elseif ($request->has('is_anonymous')) {
            $isAnonymous = $request->boolean('is_anonymous');
            $letter->is_anonymous = $isAnonymous;

            $senderUser = $letter->user;
            $letter->sender_name = $isAnonymous
                ? 'Anonymous'
                : ($senderUser && $senderUser->member ? $senderUser->member->full_name : ($senderUser ? $senderUser->name : $letter->sender_name));
        }

        $letter->message = $validated['message'];
        $letter->save();

        return response()->json([
            'success' => true,
            'message' => $letter->isSelfWish()
                ? 'Birthday resolution updated successfully.'
                : 'Birthday letter updated successfully.',
            'letter' => $letter,
        ]);
    }

    /**
     * Show a single birthday letter with strict authorization and privacy rules.
     */
    public function showLetter(Request $request, int $id): JsonResponse
    {
        $user = Auth::guard('web')->user() ?? Auth::guard('admin')->user() ?? Auth::user();
        if (!$user) {
            return response()->json(['message' => 'Unauthenticated.'], 401);
        }

        $letter = BirthdayLetter::with(['member', 'user.member'])->findOrFail($id);

        $isSender = (int) $letter->user_id === (int) $user->id;
        $isRecipient = $user->member_id && (int) $user->member_id === (int) $letter->member_id;
        $isAdmin = $user->isAdmin();

        // Self-resolution is strictly private to the resolution owner (or admin)
        if ($letter->isSelfWish() && !$isAdmin && !($isSender && $isRecipient)) {
            return response()->json([
                'success' => false,
                'message' => 'Forbidden. You do not have access to view this personal resolution.',
            ], 403);
        }

        if (!$isAdmin && !$isSender && !$isRecipient) {
            return response()->json([
                'success' => false,
                'message' => 'Forbidden. You do not have access to view this birthday letter.',
            ], 403);
        }

        $canSeeRealSender = $isAdmin || $isSender || !$letter->is_anonymous;

        return response()->json([
            'id' => $letter->id,
            'member_id' => $letter->member_id,
            'birthday_year' => $letter->birthday_year,
            'message' => $letter->message,
            'is_anonymous' => $letter->is_anonymous,
            'is_self_wish' => (bool) $letter->is_self_wish,
            'sender_name' => $canSeeRealSender ? $letter->display_name : 'Anonymous',
            'can_edit' => $letter->canBeEditedBy($user),
            'created_at' => $letter->created_at,
            'updated_at' => $letter->updated_at,
            'sender' => $canSeeRealSender && $letter->user ? [
                'id' => $letter->user->id,
                'name' => $letter->user->name,
                'member_name' => $letter->user->member ? $letter->user->member->full_name : null,
            ] : null,
        ]);
    }

    /**
     * Birthday Wishes & Archive View (/birthday-wishes and /birthday-wishes/received).
     * Displays received birthday wishes for the authenticated member (excluding self-resolutions),
     * or the administrative directory for administrators.
     */
    public function wishes(Request $request)
    {
        $user = Auth::guard('web')->user() ?? Auth::guard('admin')->user() ?? Auth::user();
        if (!$user) {
            if ($request->expectsJson() || $request->wantsJson()) {
                return response()->json(['message' => 'Unauthenticated.'], 401);
            }

            return redirect()->route('login', [
                'redirect' => $request->fullUrl(),
            ])->with('error', 'Please sign in to access Birthday Wishes.');
        }

        $today = Carbon::now('Asia/Jakarta');
        $currentYear = (int) $today->year;

        // Security Guard: Future years must NEVER be accessible
        if ($request->filled('year') && (int) $request->input('year') > $currentYear) {
            abort(403, 'Forbidden. Future years cannot be accessed.');
        }

        $isAdmin = $user->isAdmin();
        $isRecipientMember = (bool) $user->member;

        // Security Guard: Non-admin users cannot inspect another member's wishes via query parameters
        if (!$isAdmin && $request->filled('member_id')) {
            $requestedMemberId = (int) $request->input('member_id');
            if (!$user->member || $requestedMemberId !== (int) $user->member->id) {
                abort(403, 'Forbidden. You cannot view another member\'s birthday wishes.');
            }
        }

        // Find active celebrants today
        $todayCelebrants = Member::where('is_active', true)->birthdayToday($today)->get();
        $todayCelebrantIds = $todayCelebrants->pluck('id')->all();

        // Check if user has written a wish for today's celebrant(s) in current year
        $senderLettersToday = BirthdayLetter::with(['member.photo', 'user.member'])
            ->where('user_id', $user->id)
            ->where('is_self_wish', false)
            ->whereIn('member_id', $todayCelebrantIds)
            ->where('birthday_year', $currentYear)
            ->get();
        $isSenderToday = $senderLettersToday->isNotEmpty();

        // Access Rule: Non-admin, non-member recipient, and non-sender today cannot access
        if (!$isAdmin && !$isRecipientMember && !$isSenderToday) {
            abort(403, 'Access denied. You do not have permission to view birthday wishes at this time.');
        }

        // ARCHETYPE 1: ADMINISTRATOR
        if ($isAdmin) {
            $availableYears = BirthdayLetter::where('birthday_year', '<=', $currentYear)
                ->distinct()
                ->orderBy('birthday_year', 'desc')
                ->pluck('birthday_year')
                ->all();

            if (!in_array($currentYear, $availableYears)) {
                array_unshift($availableYears, $currentYear);
                rsort($availableYears);
            }

            $selectedYear = $request->filled('year') ? (int) $request->input('year') : null;
            $selectedMemberId = $request->filled('member_id') ? (int) $request->input('member_id') : null;

            $query = BirthdayLetter::with(['member.photo', 'user.member'])
                ->where('birthday_year', '<=', $currentYear);

            if ($selectedYear) {
                $query->where('birthday_year', $selectedYear);
            }

            if ($selectedMemberId) {
                $query->where('member_id', $selectedMemberId);
            }

            $wishes = $query->orderBy('birthday_year', 'desc')->orderBy('created_at', 'desc')->get();
            $members = Member::where('is_active', true)->orderBy('full_name', 'asc')->get();

            return view('user.birthday-wishes', [
                'mode' => 'admin',
                'user' => $user,
                'wishes' => $wishes,
                'availableYears' => $availableYears,
                'selectedYear' => $selectedYear,
                'selectedMemberId' => $selectedMemberId,
                'members' => $members,
                'currentYear' => $currentYear,
                'today' => $today,
                'senderLettersToday' => collect(),
            ]);
        }

        // ARCHETYPE 2: BIRTHDAY RECIPIENT (MEMBER ACCOUNT) — Received Wishes Only
        if ($isRecipientMember) {
            $recipientMember = $user->member;

            $availableYears = BirthdayLetter::where('member_id', $recipientMember->id)
                ->where('is_self_wish', false)
                ->where(function ($q) use ($user) {
                    $q->whereNull('user_id')->orWhere('user_id', '!=', $user->id);
                })
                ->where('birthday_year', '<=', $currentYear)
                ->distinct()
                ->orderBy('birthday_year', 'desc')
                ->pluck('birthday_year')
                ->all();

            if (!in_array($currentYear, $availableYears)) {
                array_unshift($availableYears, $currentYear);
                rsort($availableYears);
            }

            $selectedYear = $request->filled('year') ? (int) $request->input('year') : $currentYear;

            // Recipient can only access years relevant to their own data
            if ($selectedYear > $currentYear || (!in_array($selectedYear, $availableYears) && $selectedYear !== $currentYear)) {
                abort(403, 'Forbidden. Requested year is not accessible.');
            }

            $wishes = BirthdayLetter::with(['user.member'])
                ->where('member_id', $recipientMember->id)
                ->where('birthday_year', $selectedYear)
                ->where('is_self_wish', false)
                ->where(function ($q) use ($user) {
                    $q->whereNull('user_id')->orWhere('user_id', '!=', $user->id);
                })
                ->orderBy('created_at', 'desc')
                ->get();

            return view('user.birthday-wishes', [
                'mode' => 'recipient',
                'user' => $user,
                'recipientMember' => $recipientMember,
                'wishes' => $wishes,
                'availableYears' => $availableYears,
                'selectedYear' => $selectedYear,
                'currentYear' => $currentYear,
                'today' => $today,
                'senderLettersToday' => $senderLettersToday,
            ]);
        }

        // ARCHETYPE 3: SENDER ACCESSING RECEIVED ROUTE DIRECTS TO SENT WISHES
        return redirect()->route('birthday.wishes.sent');
    }

    /**
     * Sent Birthday Wishes View (/birthday-wishes/sent).
     * Dedicated page for users to review and manage greetings they have sent to other members.
     */
    public function sentWishes(Request $request)
    {
        $user = Auth::guard('web')->user() ?? Auth::guard('admin')->user() ?? Auth::user();
        if (!$user) {
            if ($request->expectsJson() || $request->wantsJson()) {
                return response()->json(['message' => 'Unauthenticated.'], 401);
            }

            return redirect()->route('login', [
                'redirect' => $request->fullUrl(),
            ])->with('error', 'Please sign in to view your sent birthday wishes.');
        }

        if (!$user->isAdmin() && $request->filled('user_id') && (int) $request->input('user_id') !== (int) $user->id) {
            abort(403, 'Forbidden. You cannot view another member\'s sent birthday wishes.');
        }

        $today = Carbon::now('Asia/Jakarta');
        $currentYear = (int) $today->year;

        if ($request->filled('year') && (int) $request->input('year') > $currentYear) {
            abort(403, 'Forbidden. Future years cannot be accessed.');
        }

        $sentLetters = BirthdayLetter::with(['member.photo', 'user.member'])
            ->where('user_id', $user->id)
            ->where('is_self_wish', false)
            ->when($user->member_id, fn($q) => $q->where('member_id', '!=', $user->member_id))
            ->orderBy('birthday_year', 'desc')
            ->orderBy('created_at', 'desc')
            ->get();

        return view('user.birthday-wishes-sent', [
            'user' => $user,
            'wishes' => $sentLetters,
            'currentYear' => $currentYear,
            'today' => $today,
        ]);
    }

    /**
     * Personal Birthday Resolution View (/birthday-resolution).
     * Dedicated page for the authenticated member to write, view, and edit their own personal resolutions.
     */
    public function resolution(Request $request)
    {
        $user = Auth::guard('web')->user() ?? Auth::guard('admin')->user() ?? Auth::user();
        if (!$user) {
            if ($request->expectsJson() || $request->wantsJson()) {
                return response()->json(['message' => 'Unauthenticated.'], 401);
            }

            return redirect()->route('login', [
                'redirect' => $request->fullUrl(),
            ])->with('error', 'Please sign in to view your birthday resolution.');
        }

        $today = Carbon::now('Asia/Jakarta');
        $currentYear = (int) $today->year;

        if ($request->filled('year') && (int) $request->input('year') > $currentYear) {
            abort(403, 'Forbidden. Future years cannot be accessed.');
        }

        if (!$user->isAdmin()) {
            if ($request->filled('user_id') && (int) $request->input('user_id') !== (int) $user->id) {
                abort(403, 'Forbidden. You cannot view another member\'s birthday resolution.');
            }
            if ($request->filled('member_id') && (!$user->member || (int) $request->input('member_id') !== (int) $user->member->id)) {
                abort(403, 'Forbidden. You cannot view another member\'s birthday resolution.');
            }
        }

        if (!$user->member) {
            abort(403, 'A linked member profile is required to manage personal birthday resolutions.');
        }

        $recipientMember = $user->member;

        $resolutions = BirthdayLetter::with(['member'])
            ->where('user_id', $user->id)
            ->where('member_id', $recipientMember->id)
            ->where('is_self_wish', true)
            ->where('birthday_year', '<=', $currentYear)
            ->orderBy('birthday_year', 'desc')
            ->orderBy('created_at', 'desc')
            ->get();

        $currentResolution = $resolutions->firstWhere('birthday_year', $currentYear);
        $canWriteOrEditToday = $recipientMember->isBirthdayToday();

        return view('user.birthday-resolution', [
            'user' => $user,
            'recipientMember' => $recipientMember,
            'resolutions' => $resolutions,
            'currentResolution' => $currentResolution,
            'canWriteOrEditToday' => $canWriteOrEditToday,
            'currentYear' => $currentYear,
            'today' => $today,
        ]);
    }
}
