<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\BirthdayLetter;
use App\Models\Member;
use Illuminate\Http\Request;
use Illuminate\View\View;

class BirthdayWishController extends Controller
{
    /**
     * Display the Admin Birthday Wishes Management & Archive portal.
     */
    public function index(Request $request): View
    {
        $selectedYear = (int) $request->input('year', now()->year);
        $selectedMemberId = $request->input('member_id');

        $query = BirthdayLetter::with(['recipient', 'sender.member'])
            ->where('birthday_year', $selectedYear)
            ->orderBy('created_at', 'desc');

        if ($selectedMemberId) {
            $query->where('member_id', $selectedMemberId);
        }

        $letters = $query->paginate(20)->withQueryString();

        $members = Member::where('is_active', true)
            ->whereNotNull('date_of_birth')
            ->orderBy('full_name', 'asc')
            ->get();

        $years = BirthdayLetter::select('birthday_year')
            ->distinct()
            ->orderBy('birthday_year', 'desc')
            ->pluck('birthday_year')
            ->all();

        if (empty($years)) {
            $years = [now()->year];
        }

        return view('admin.birthday-wishes', [
            'letters' => $letters,
            'members' => $members,
            'years' => $years,
            'selectedYear' => $selectedYear,
            'selectedMemberId' => $selectedMemberId,
            'totalLetters' => BirthdayLetter::count(),
        ]);
    }
}
