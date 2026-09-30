<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\BirthdayLetter;
use App\Models\Member;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class BirthdayWishController extends Controller
{
    /**
     * Display the Admin Birthday Wishes Management & Archive portal.
     */
    public function index(Request $request): View
    {
        $currentYear = (int) Carbon::now('Asia/Jakarta')->year;

        // Security Guard: Future years must NEVER be accessible
        if ($request->filled('year') && (int) $request->input('year') > $currentYear) {
            abort(403, 'Forbidden. Future years cannot be accessed.');
        }

        $selectedYear = $request->filled('year') && $request->input('year') !== 'all'
            ? (int) $request->input('year')
            : ($request->input('year') === 'all' ? null : $currentYear);
        $selectedMemberId = $request->input('member_id');
        $searchKeyword = trim($request->input('search', ''));

        $query = BirthdayLetter::with(['recipient', 'user.member'])
            ->where('birthday_year', '<=', $currentYear)
            ->orderBy('created_at', 'desc');

        if ($selectedYear !== null) {
            $query->where('birthday_year', $selectedYear);
        }

        if ($selectedMemberId) {
            $query->where('member_id', $selectedMemberId);
        }

        if (!empty($searchKeyword)) {
            $escaped = addcslashes($searchKeyword, '%_\\');
            $query->where(function ($q) use ($escaped) {
                $q->where('message', 'like', "%{$escaped}%")
                  ->orWhere('sender_name', 'like', "%{$escaped}%")
                  ->orWhereHas('recipient', function ($rq) use ($escaped) {
                      $rq->where('full_name', 'like', "%{$escaped}%");
                  })
                  ->orWhereHas('user', function ($uq) use ($escaped) {
                      $uq->where('name', 'like', "%{$escaped}%")
                        ->orWhere('email', 'like', "%{$escaped}%");
                  });
            });
        }

        $letters = $query->paginate(15)->withQueryString();

        $members = Member::where('is_active', true)
            ->whereNotNull('date_of_birth')
            ->orderBy('full_name', 'asc')
            ->get();

        $years = BirthdayLetter::where('birthday_year', '<=', $currentYear)
            ->select('birthday_year')
            ->distinct()
            ->orderBy('birthday_year', 'desc')
            ->pluck('birthday_year')
            ->all();

        if (empty($years)) {
            $years = [$currentYear];
        } elseif (!in_array($currentYear, $years, true)) {
            array_unshift($years, $currentYear);
        }

        return view('admin.birthday-wishes', [
            'letters' => $letters,
            'members' => $members,
            'years' => $years,
            'currentYear' => $currentYear,
            'selectedYear' => $selectedYear,
            'selectedMemberId' => $selectedMemberId,
            'searchKeyword' => $searchKeyword,
            'totalLetters' => BirthdayLetter::where('birthday_year', '<=', $currentYear)->count(),
        ]);
    }

    /**
     * Get a specific birthday wish details (JSON for modal / audit).
     */
    public function show(Request $request, int $id): JsonResponse
    {
        $letter = BirthdayLetter::with(['recipient', 'user.member'])->findOrFail($id);

        $realSenderName = $letter->user
            ? ($letter->user->member ? $letter->user->member->full_name : $letter->user->name)
            : ($letter->sender_name ?: 'Unknown');

        return response()->json([
            'id' => $letter->id,
            'member_id' => $letter->member_id,
            'recipient_name' => $letter->recipient ? $letter->recipient->full_name : 'Unknown',
            'birthday_year' => $letter->birthday_year,
            'message' => $letter->message,
            'is_anonymous' => (bool) $letter->is_anonymous,
            'real_sender_name' => $realSenderName,
            'display_name' => $letter->display_name,
            'sender_email' => $letter->user ? $letter->user->email : null,
            'created_at' => $letter->created_at->format('M j, Y H:i:s'),
        ]);
    }

    /**
     * Update a birthday wish (Admin moderation: message or anonymity toggle).
     */
    public function update(Request $request, int $id)
    {
        $letter = BirthdayLetter::findOrFail($id);

        $validated = $request->validate([
            'message' => 'required|string|min:3|max:3000',
            'is_anonymous' => 'nullable|boolean',
        ]);

        $letter->message = $validated['message'];

        if ($request->has('is_anonymous')) {
            $letter->is_anonymous = $request->boolean('is_anonymous');
        }

        $letter->save();

        if ($request->wantsJson() || $request->ajax()) {
            return response()->json([
                'success' => true,
                'message' => 'Birthday wish updated successfully.',
                'letter' => $letter,
            ]);
        }

        return back()->with('success', 'Birthday wish updated successfully.');
    }

    /**
     * Delete a birthday wish (Admin moderation).
     */
    public function destroy(Request $request, int $id)
    {
        $letter = BirthdayLetter::findOrFail($id);
        $letter->delete();

        if ($request->wantsJson() || $request->ajax()) {
            return response()->json([
                'success' => true,
                'message' => 'Birthday wish deleted successfully.',
            ]);
        }

        return back()->with('success', 'Birthday wish deleted successfully.');
    }
}
