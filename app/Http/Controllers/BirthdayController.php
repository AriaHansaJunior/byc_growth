<?php

namespace App\Http\Controllers;

use App\Models\BirthdayLetter;
use App\Models\Member;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class BirthdayController extends Controller
{
    /**
     * Get members celebrating their birthday today in Surabaya (Asia/Jakarta).
     */
    public function today()
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
     */
    public function submitLetter(Request $request)
    {
        $validated = $request->validate([
            'member_id' => 'required|exists:members,id',
            'sender_name' => 'nullable|string|max:255',
            'message' => 'required|string|min:3|max:3000',
        ]);

        $letter = BirthdayLetter::create([
            'member_id' => $validated['member_id'],
            'sender_name' => $validated['sender_name'] ?: 'A BYC Friend',
            'message' => $validated['message'],
        ]);

        if ($request->wantsJson() || $request->ajax()) {
            return response()->json([
                'success' => true,
                'message' => 'Your birthday letter has been sent with love!',
                'letter_id' => $letter->id,
            ]);
        }

        return back()->with('success', 'Your birthday letter has been sent with love!');
    }
}
