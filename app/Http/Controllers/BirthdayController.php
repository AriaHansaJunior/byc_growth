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
        if (!Auth::check()) {
            if ($request->expectsJson() || $request->wantsJson() || $request->ajax()) {
                return response()->json([
                    'message' => 'Unauthenticated. Please sign in to send a birthday letter.',
                ], 401);
            }

            return redirect()->route('admin.login')->with('error', 'Please sign in to send a birthday letter.');
        }

        /** @var User $user */
        $user = Auth::user();

        // 2. Validate input payload
        $validated = $request->validate([
            'member_id' => 'required|exists:members,id',
            'message' => 'required|string|min:3|max:3000',
            'is_anonymous' => 'nullable|boolean',
            'sender_name' => 'nullable|string|max:255',
        ]);

        $recipient = Member::findOrFail($validated['member_id']);

        // 3. Self-Wish Prevention: A member cannot send a birthday letter to themselves
        if ($user->member_id && (int) $user->member_id === (int) $recipient->id) {
            if ($request->wantsJson() || $request->ajax()) {
                return response()->json([
                    'success' => false,
                    'message' => 'You cannot send a birthday letter to yourself.',
                ], 422);
            }

            return back()->withErrors(['member_id' => 'You cannot send a birthday letter to yourself.']);
        }

        // 4. Server-authoritative birthday year (Asia/Jakarta timezone)
        $birthdayYear = (int) Carbon::now('Asia/Jakarta')->year;

        // 5. Invariant: ONE sender user + ONE birthday recipient member + ONE birthday year = ONE letter
        $existingLetter = BirthdayLetter::where('user_id', $user->id)
            ->where('member_id', $recipient->id)
            ->where('birthday_year', $birthdayYear)
            ->first();

        if ($existingLetter) {
            if ($request->wantsJson() || $request->ajax()) {
                return response()->json([
                    'success' => false,
                    'message' => 'You have already sent a birthday letter to this member for this year.',
                    'existing_letter_id' => $existingLetter->id,
                ], 422);
            }

            return back()->withErrors(['member_id' => 'You have already sent a birthday letter to this member for this year.']);
        }

        // 6. Anonymous / Display preference handling
        $isAnonymous = $request->boolean('is_anonymous');
        $senderName = $isAnonymous
            ? 'Anonymous'
            : ($user->member ? $user->member->full_name : (!empty($validated['sender_name']) ? $validated['sender_name'] : $user->name));

        $letter = BirthdayLetter::create([
            'member_id' => $recipient->id,
            'user_id' => $user->id,
            'birthday_year' => $birthdayYear,
            'is_anonymous' => $isAnonymous,
            'sender_name' => $senderName,
            'message' => $validated['message'],
        ]);

        if ($request->wantsJson() || $request->ajax()) {
            return response()->json([
                'success' => true,
                'message' => 'Your birthday letter has been sent with love!',
                'letter_id' => $letter->id,
                'letter' => $letter,
            ]);
        }

        return back()->with('success', 'Your birthday letter has been sent with love!');
    }

    /**
     * Update an existing birthday letter.
     * Normal users can only edit their own letter while the recipient's birthday date is still today.
     * Administrators have full control and can edit at any time.
     */
    public function updateLetter(Request $request, int $id): JsonResponse
    {
        if (!Auth::check()) {
            return response()->json(['message' => 'Unauthenticated.'], 401);
        }

        /** @var User $user */
        $user = Auth::user();
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

        if ($request->has('is_anonymous')) {
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
            'message' => 'Birthday letter updated successfully.',
            'letter' => $letter,
        ]);
    }

    /**
     * Show a single birthday letter with strict authorization and privacy rules.
     */
    public function showLetter(Request $request, int $id): JsonResponse
    {
        if (!Auth::check()) {
            return response()->json(['message' => 'Unauthenticated.'], 401);
        }

        /** @var User $user */
        $user = Auth::user();
        $letter = BirthdayLetter::with(['member', 'user.member'])->findOrFail($id);

        $isSender = (int) $letter->user_id === (int) $user->id;
        $isRecipient = $user->member_id && (int) $user->member_id === (int) $letter->member_id;
        $isAdmin = $user->isAdmin();

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
     * Birthday Wishes & Archive View (/birthday-wishes).
     * Server-side authorization distinguishes between:
     * - Admin (can view all members/years and edit any wish)
     * - Birthday recipient celebrating today (can view received wishes and own archive)
     * - Sender who wrote a wish for today's celebrant (can review/edit own wish)
     * - Unauthenticated visitors / unauthorized users (strictly rejected)
     */
    public function wishes(Request $request)
    {
        if (!Auth::check()) {
            if ($request->expectsJson() || $request->wantsJson()) {
                return response()->json(['message' => 'Unauthenticated.'], 401);
            }

            return redirect()->route('admin.login', [
                'redirect' => $request->fullUrl(),
            ])->with('error', 'Please sign in to access Birthday Wishes.');
        }

        /** @var User $user */
        $user = Auth::user();
        $today = Carbon::now('Asia/Jakarta');
        $currentYear = (int) $today->year;

        // Security Guard: Future years must NEVER be accessible
        if ($request->filled('year') && (int) $request->input('year') > $currentYear) {
            abort(403, 'Forbidden. Future years cannot be accessed.');
        }

        $isAdmin = $user->isAdmin();
        $isRecipientCelebrating = $user->member && $user->member->isBirthdayToday($today);

        // Find active celebrants today
        $todayCelebrants = Member::where('is_active', true)->birthdayToday($today)->get();
        $todayCelebrantIds = $todayCelebrants->pluck('id')->all();

        // Check if user has written a wish for today's celebrant(s) in current year
        $senderLettersToday = BirthdayLetter::with(['member.photo', 'user.member'])
            ->where('user_id', $user->id)
            ->whereIn('member_id', $todayCelebrantIds)
            ->where('birthday_year', $currentYear)
            ->get();
        $isSenderToday = $senderLettersToday->isNotEmpty();

        // Access Rule: Non-admin, non-celebrant, and non-sender today cannot access
        if (!$isAdmin && !$isRecipientCelebrating && !$isSenderToday) {
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

            return view('pages.birthday-wishes', [
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

        // ARCHETYPE 2: BIRTHDAY RECIPIENT CELEBRATING TODAY
        if ($isRecipientCelebrating) {
            $recipientMember = $user->member;

            // Strict security: ignore/override any member_id query to prevent viewing others' archive
            $availableYears = BirthdayLetter::where('member_id', $recipientMember->id)
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
                ->orderBy('created_at', 'desc')
                ->get();

            return view('pages.birthday-wishes', [
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

        // ARCHETYPE 3: SENDER REVIEWING OWN WISH FOR TODAY'S CELEBRANT
        return view('pages.birthday-wishes', [
            'mode' => 'sender',
            'user' => $user,
            'wishes' => $senderLettersToday,
            'availableYears' => [$currentYear],
            'selectedYear' => $currentYear,
            'currentYear' => $currentYear,
            'today' => $today,
            'senderLettersToday' => $senderLettersToday,
        ]);
    }
}
