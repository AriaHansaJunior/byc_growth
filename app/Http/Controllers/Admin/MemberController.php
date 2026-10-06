<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\MediaFile;
use App\Models\Member;
use App\Models\MemberPhoto;
use App\Services\MediaUploadService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class MemberController extends Controller
{
    protected MediaUploadService $mediaService;

    public function __construct(MediaUploadService $mediaService)
    {
        $this->mediaService = $mediaService;
    }

    /**
     * Display the Admin Members Management portal with filtering and sorting.
     */
    public function index(Request $request = null): \Illuminate\View\View
    {
        $request = $request ?? request();

        $query = Member::with(['photo', 'memberPhoto']);

        // Search: Full Name
        if ($request->filled('search')) {
            $search = trim($request->input('search'));
            $escaped = addcslashes($search, '%_\\');
            $query->where('full_name', 'like', "%{$escaped}%");
        }

        // Filter: Birthday Today or All
        $filter = $request->input('filter', 'all');
        if ($filter === 'birthday_today') {
            $today = \Carbon\Carbon::now('Asia/Jakarta');
            $query->whereNotNull('date_of_birth')
                  ->whereMonth('date_of_birth', $today->month)
                  ->whereDay('date_of_birth', $today->day);
        }

        // Sorting: name_asc, name_desc, dob_asc, dob_desc, newest
        $sort = $request->input('sort', 'name_asc');
        switch ($sort) {
            case 'name_desc':
                $query->orderBy('full_name', 'desc');
                break;
            case 'dob_asc':
                $query->orderByRaw('date_of_birth is null, date_of_birth asc');
                break;
            case 'dob_desc':
                $query->orderByRaw('date_of_birth desc');
                break;
            case 'newest':
                $query->orderBy('created_at', 'desc');
                break;
            case 'name_asc':
            default:
                $query->orderBy('full_name', 'asc');
                break;
        }

        $members = $query->get();

        return view('admin.members', [
            'members' => $members,
            'totalCount' => Member::count(),
            'filteredCount' => $members->count(),
            'activeCount' => Member::where('is_active', true)->count(),
            'filters' => [
                'search' => $request->input('search', ''),
                'filter' => $filter,
                'sort' => $sort,
            ],
        ]);
    }

    /**
     * Store a newly created member in storage.
     */
    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'full_name' => 'required|string|max:255',
            'date_of_birth' => 'nullable|date|before_or_equal:today',
            'photo' => 'nullable|image|mimes:jpeg,png,jpg,webp,gif|max:51200',
        ], [
            'date_of_birth.before_or_equal' => 'Date of birth cannot be in the future (maximum date is today).',
        ]);

        $member = Member::create([
            'full_name' => $validated['full_name'],
            'date_of_birth' => $validated['date_of_birth'] ?? null,
            'is_active' => true,
        ]);

        if ($request->hasFile('photo') && $request->file('photo')->isValid()) {
            $file = $request->file('photo');
            $mime = $file->getClientMimeType() ?: 'image/jpeg';
            $content = file_get_contents($file->getRealPath());
            $base64 = 'data:' . $mime . ';base64,' . base64_encode($content);

            MemberPhoto::create([
                'member_id' => $member->id,
                'photo_data' => $base64,
                'original_name' => $file->getClientOriginalName(),
                'mime_type' => $mime,
                'file_size' => strlen($content),
            ]);
        }

        $target = ($request->header('referer') && str_contains($request->header('referer'), '/admin/members'))
            ? route('admin.members')
            : route('members');

        return redirect($target)->with('success', 'Member added successfully.');
    }

    /**
     * Update the specified member in storage.
     */
    public function update(Request $request, int $id): RedirectResponse
    {
        $member = Member::with(['photo', 'memberPhoto'])->findOrFail($id);

        $validated = $request->validate([
            'full_name' => 'required|string|max:255',
            'date_of_birth' => 'nullable|date|before_or_equal:today',
            'photo' => 'nullable|image|mimes:jpeg,png,jpg,webp,gif|max:51200',
            'remove_photo' => 'nullable|boolean',
        ], [
            'date_of_birth.before_or_equal' => 'Date of birth cannot be in the future (maximum date is today).',
        ]);

        if ($request->boolean('remove_photo')) {
            if ($member->memberPhoto) {
                $member->memberPhoto->delete();
            }
            if ($member->photo) {
                $this->mediaService->deleteMediaFile($member->photo);
                $member->update(['photo_file_id' => null]);
            }
        }

        if ($request->hasFile('photo') && $request->file('photo')->isValid()) {
            $file = $request->file('photo');
            $mime = $file->getClientMimeType() ?: 'image/jpeg';
            $content = file_get_contents($file->getRealPath());
            $base64 = 'data:' . $mime . ';base64,' . base64_encode($content);

            $memberPhoto = $member->memberPhoto;
            if ($memberPhoto) {
                $memberPhoto->update([
                    'photo_data' => $base64,
                    'original_name' => $file->getClientOriginalName(),
                    'mime_type' => $mime,
                    'file_size' => strlen($content),
                ]);
            } else {
                MemberPhoto::create([
                    'member_id' => $member->id,
                    'photo_data' => $base64,
                    'original_name' => $file->getClientOriginalName(),
                    'mime_type' => $mime,
                    'file_size' => strlen($content),
                ]);
            }

            if ($member->photo) {
                $this->mediaService->deleteMediaFile($member->photo);
                $member->update(['photo_file_id' => null]);
            }
        }

        $updateData = [
            'full_name' => $validated['full_name'],
        ];

        if (array_key_exists('date_of_birth', $validated)) {
            $updateData['date_of_birth'] = $validated['date_of_birth'];
        }

        $member->update($updateData);

        $target = ($request->header('referer') && str_contains($request->header('referer'), '/admin/members'))
            ? route('admin.members')
            : route('members');

        return redirect($target)->with('success', 'Member updated successfully.');
    }

    /**
     * Remove the specified member from storage.
     */
    public function destroy(Request $request, int $id): RedirectResponse
    {
        $member = Member::findOrFail($id);

        if ($member->photo_file_id) {
            $media = MediaFile::find($member->photo_file_id);
            if ($media) {
                $this->mediaService->deleteMediaFile($media);
            }
        }

        // Clean up any remaining media files associated with this member
        $otherMedia = MediaFile::where('fileable_type', Member::class)
            ->where('fileable_id', $member->id)
            ->get();
        foreach ($otherMedia as $media) {
            $this->mediaService->deleteMediaFile($media);
        }

        // Safely disassociate user accounts to preserve credentials without dangling member_id
        if (class_exists(\App\Models\User::class)) {
            \App\Models\User::where('member_id', $member->id)->update(['member_id' => null]);
        }

        // Safely disassociate cash transactions to preserve financial history integrity
        if (class_exists(\App\Models\CashTransaction::class)) {
            \App\Models\CashTransaction::where('member_id', $member->id)->update(['member_id' => null]);
        }

        $member->delete();

        $target = ($request->header('referer') && str_contains($request->header('referer'), '/admin/members'))
            ? route('admin.members')
            : route('members');

        return redirect($target)->with('success', 'Member removed successfully.');
    }

    /**
     * Remove multiple members from storage.
     */
    public function batchDestroy(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'ids' => 'required|array|min:1',
            'ids.*' => 'required|integer|exists:members,id',
        ]);

        $ids = $validated['ids'];
        $members = Member::whereIn('id', $ids)->get();
        $count = $members->count();

        foreach ($members as $member) {
            if ($member->photo_file_id) {
                $media = MediaFile::find($member->photo_file_id);
                if ($media) {
                    $this->mediaService->deleteMediaFile($media);
                }
            }

            $otherMedia = MediaFile::where('fileable_type', Member::class)
                ->where('fileable_id', $member->id)
                ->get();
            foreach ($otherMedia as $media) {
                $this->mediaService->deleteMediaFile($media);
            }
        }

        // Safely disassociate user accounts
        if (class_exists(\App\Models\User::class)) {
            \App\Models\User::whereIn('member_id', $ids)->update(['member_id' => null]);
        }

        // Safely disassociate cash transactions
        if (class_exists(\App\Models\CashTransaction::class)) {
            \App\Models\CashTransaction::whereIn('member_id', $ids)->update(['member_id' => null]);
        }

        Member::whereIn('id', $ids)->delete();

        return redirect()->route('admin.members')
            ->with('success', "Selected {$count} " . ($count === 1 ? 'member has' : 'members have') . ' been removed successfully.');
    }
}
