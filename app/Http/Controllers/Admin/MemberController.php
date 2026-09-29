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

        return redirect()->route('members')->with('success', 'Member added successfully.');
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

        return redirect()->route('members')->with('success', 'Member updated successfully.');
    }

    /**
     * Remove the specified member from storage.
     */
    public function destroy(int $id): RedirectResponse
    {
        $member = Member::findOrFail($id);

        if ($member->photo_file_id) {
            $media = MediaFile::find($member->photo_file_id);
            if ($media) {
                $this->mediaService->deleteMediaFile($media);
            }
        }

        // Safely disassociate cash transactions to preserve financial history integrity
        if (class_exists(\App\Models\CashTransaction::class)) {
            \App\Models\CashTransaction::where('member_id', $member->id)->update(['member_id' => null]);
        }

        $member->delete();

        return redirect()->route('members')->with('success', 'Member removed successfully.');
    }
}
