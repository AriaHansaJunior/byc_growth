<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\MediaFile;
use App\Models\Member;
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
     * Display the Admin Members Management portal.
     */
    public function index(): \Illuminate\View\View
    {
        $members = Member::with('photo')
            ->orderBy('full_name', 'asc')
            ->get();

        return view('admin.members', [
            'members' => $members,
            'totalCount' => $members->count(),
            'activeCount' => $members->where('is_active', true)->count(),
        ]);
    }

    /**
     * Store a newly created member in storage.
     */
    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'full_name' => 'required|string|max:255',
            'date_of_birth' => 'nullable|date',
            'photo' => 'nullable|image|mimes:jpeg,png,jpg,webp|max:5120',
        ]);

        $photoFileId = null;
        if ($request->hasFile('photo') && $request->file('photo')->isValid()) {
            $media = $this->mediaService->storeImage(
                $request->file('photo'),
                'member',
                Member::class
            );
            $photoFileId = $media->id;
        }

        $member = Member::create([
            'full_name' => $validated['full_name'],
            'date_of_birth' => $validated['date_of_birth'] ?? null,
            'photo_file_id' => $photoFileId,
            'is_active' => true,
        ]);

        if ($photoFileId) {
            $media = MediaFile::find($photoFileId);
            if ($media) {
                $media->update(['fileable_id' => $member->id]);
            }
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
        $member = Member::findOrFail($id);

        $validated = $request->validate([
            'full_name' => 'required|string|max:255',
            'date_of_birth' => 'nullable|date',
            'photo' => 'nullable|image|mimes:jpeg,png,jpg,webp|max:5120',
            'remove_photo' => 'nullable|boolean',
        ]);

        $photoFileId = $member->photo_file_id;

        if ($request->boolean('remove_photo') && $photoFileId) {
            $oldMedia = MediaFile::find($photoFileId);
            if ($oldMedia) {
                $this->mediaService->deleteMediaFile($oldMedia);
            }
            $photoFileId = null;
        }

        if ($request->hasFile('photo') && $request->file('photo')->isValid()) {
            // Delete old photo if exists
            if ($photoFileId) {
                $oldMedia = MediaFile::find($photoFileId);
                if ($oldMedia) {
                    $this->mediaService->deleteMediaFile($oldMedia);
                }
            }

            $media = $this->mediaService->storeImage(
                $request->file('photo'),
                'member',
                Member::class,
                $member->id
            );
            $photoFileId = $media->id;
        }

        $updateData = [
            'full_name' => $validated['full_name'],
            'photo_file_id' => $photoFileId,
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
}
