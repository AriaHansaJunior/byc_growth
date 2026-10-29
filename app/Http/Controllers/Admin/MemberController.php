<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Models\MediaFile;
use App\Models\Member;
use App\Models\MemberPhoto;
use App\Models\User;
use App\Services\MediaUploadService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Services\ImageOptimizerService;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schema;

class MemberController extends Controller
{
    protected MediaUploadService $mediaService;
    protected ImageOptimizerService $optimizer;

    public function __construct(MediaUploadService $mediaService, ImageOptimizerService $optimizer)
    {
        $this->mediaService = $mediaService;
        $this->optimizer = $optimizer;
    }

    /**
     * Display the Admin Members Management portal with filtering and sorting.
     */
    public function index(Request $request = null): \Illuminate\View\View
    {
        $request = $request ?? request();

        $query = Member::with(['photo', 'memberPhoto', 'user']);

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
        $users = User::where('role', 'user')->orderBy('username')->get();

        return view('admin.members', [
            'members' => $members,
            'users' => $users,
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
     * Show the form for creating a new member on a dedicated full page.
     */
    public function create(): \Illuminate\View\View
    {
        $users = User::where('role', 'user')->orderBy('username')->get();

        return view('admin.members-create', [
            'users' => $users,
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
            'photo' => 'nullable|file|mimes:jpeg,png,jpg,webp,gif,heic,heif,bin|extensions:jpeg,png,jpg,webp,gif,heic,heif|max:51200',
            'photo_base64' => 'nullable|string',
            'user_id' => 'nullable',
            'new_user_email' => 'nullable|string|email|max:255',
            'new_user_username' => 'nullable|string|min:3|max:60|regex:/^[a-zA-Z0-9_]+$/',
            'new_user_password' => 'nullable|string|min:6',
        ], [
            'date_of_birth.before_or_equal' => 'Date of birth cannot be in the future (maximum date is today).',
        ]);

        $adminUser = Auth::guard('admin')->user() ?? Auth::guard('web')->user() ?? Auth::user();
        $adminEmail = $adminUser ? $adminUser->email : 'admin@bycgrowth.org';

        $member = Member::create([
            'full_name' => $validated['full_name'],
            'date_of_birth' => $validated['date_of_birth'] ?? null,
            'is_active' => true,
            'last_action_by' => $adminEmail,
            'last_action_type' => 'created',
            'last_action_at' => now(),
        ]);

        AuditLog::record($adminUser, 'created', 'member', $member->id, "Created member '{$member->full_name}'");

        if ($request->filled('photo_base64') && str_starts_with($request->input('photo_base64'), 'data:image/')) {
            $base64 = $request->input('photo_base64');
            $mime = 'image/jpeg';
            if (preg_match('/^data:(image\/[a-zA-Z0-9\+\-\.]+);base64,/', $base64, $m)) {
                $mime = $m[1];
            }
            $rawBinary = base64_decode(preg_replace('/^data:image\/[a-zA-Z0-9\+\-\.]+;base64,/', '', $base64));
            $contentLen = strlen($rawBinary);

            $photoBase64 = $contentLen > 300 * 1024
                ? ($this->optimizer->compressBase64($base64, 1000, 85) ?: $base64)
                : $base64;

            $data = [
                'member_id' => $member->id,
                'photo_data' => $photoBase64,
                'original_name' => 'member_photo_' . time() . '.jpg',
                'mime_type' => $mime,
                'file_size' => $contentLen,
            ];

            if (Schema::hasColumn('member_photos', 'compressed_photo_data')) {
                $data['compressed_photo_data'] = $this->optimizer->compressBase64($photoBase64, 550, 80) ?: $photoBase64;
            }

            MemberPhoto::create($data);
        } elseif ($request->hasFile('photo') && $request->file('photo')->isValid()) {
            $file = $request->file('photo');
            $ext = strtolower($file->getClientOriginalExtension());
            $mime = $file->getClientMimeType() ?: 'image/jpeg';
            if (in_array($ext, ['heic', 'heif'], true) && (!str_starts_with($mime, 'image/') || $mime === 'image/jpeg')) {
                $mime = 'image/' . $ext;
            }
            $content = file_get_contents($file->getRealPath());
            $base64 = 'data:' . $mime . ';base64,' . base64_encode($content);

            // Compress to maximum 1000px if file is large to protect MySQL packet limit
            $photoBase64 = strlen($content) > 300 * 1024
                ? ($this->optimizer->compressBase64($base64, 1000, 85) ?: $base64)
                : $base64;

            $data = [
                'member_id' => $member->id,
                'photo_data' => $photoBase64,
                'original_name' => $file->getClientOriginalName(),
                'mime_type' => $mime,
                'file_size' => strlen($content),
            ];

            if (Schema::hasColumn('member_photos', 'compressed_photo_data')) {
                $data['compressed_photo_data'] = $this->optimizer->compressBase64($photoBase64, 550, 80) ?: $photoBase64;
            }

            MemberPhoto::create($data);
        }

        $this->handleUserLinking($request, $member);

        $target = ($request->is('admin/*') || ($request->header('referer') && str_contains($request->header('referer'), '/admin/members')))
            ? route('admin.members')
            : route('members');

        return redirect($target)->with('success', 'Member added successfully.');
    }

    /**
     * Update the specified member in storage.
     */
    public function update(Request $request, int $id): RedirectResponse
    {
        $member = Member::with(['photo', 'memberPhoto', 'user'])->findOrFail($id);

        $validated = $request->validate([
            'full_name' => 'required|string|max:255',
            'date_of_birth' => 'nullable|date|before_or_equal:today',
            'photo' => 'nullable|file|mimes:jpeg,png,jpg,webp,gif,heic,heif,bin|extensions:jpeg,png,jpg,webp,gif,heic,heif|max:51200',
            'photo_base64' => 'nullable|string',
            'remove_photo' => 'nullable|boolean',
            'user_id' => 'nullable',
            'new_user_email' => 'nullable|string|email|max:255',
            'new_user_username' => 'nullable|string|min:3|max:60|regex:/^[a-zA-Z0-9_]+$/',
            'new_user_password' => 'nullable|string|min:6',
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

        if ($request->filled('photo_base64') && str_starts_with($request->input('photo_base64'), 'data:image/')) {
            $base64 = $request->input('photo_base64');
            $mime = 'image/jpeg';
            if (preg_match('/^data:(image\/[a-zA-Z0-9\+\-\.]+);base64,/', $base64, $m)) {
                $mime = $m[1];
            }
            $rawBinary = base64_decode(preg_replace('/^data:image\/[a-zA-Z0-9\+\-\.]+;base64,/', '', $base64));
            $contentLen = strlen($rawBinary);

            $photoBase64 = $contentLen > 300 * 1024
                ? ($this->optimizer->compressBase64($base64, 1000, 85) ?: $base64)
                : $base64;

            $updateData = [
                'photo_data' => $photoBase64,
                'original_name' => 'member_photo_' . time() . '.jpg',
                'mime_type' => $mime,
                'file_size' => $contentLen,
            ];

            if (Schema::hasColumn('member_photos', 'compressed_photo_data')) {
                $updateData['compressed_photo_data'] = $this->optimizer->compressBase64($photoBase64, 550, 80) ?: $photoBase64;
            }

            $memberPhoto = $member->memberPhoto;
            if ($memberPhoto) {
                $memberPhoto->update($updateData);
            } else {
                $updateData['member_id'] = $member->id;
                MemberPhoto::create($updateData);
            }

            if ($member->photo) {
                $this->mediaService->deleteMediaFile($member->photo);
                $member->update(['photo_file_id' => null]);
            }
        } elseif ($request->hasFile('photo') && $request->file('photo')->isValid()) {
            $file = $request->file('photo');
            $ext = strtolower($file->getClientOriginalExtension());
            $mime = $file->getClientMimeType() ?: 'image/jpeg';
            if (in_array($ext, ['heic', 'heif'], true) && (!str_starts_with($mime, 'image/') || $mime === 'image/jpeg')) {
                $mime = 'image/' . $ext;
            }
            $content = file_get_contents($file->getRealPath());
            $base64 = 'data:' . $mime . ';base64,' . base64_encode($content);

            // Compress to maximum 1000px if file is large to protect MySQL packet limit
            $photoBase64 = strlen($content) > 300 * 1024
                ? ($this->optimizer->compressBase64($base64, 1000, 85) ?: $base64)
                : $base64;

            $updateData = [
                'photo_data' => $photoBase64,
                'original_name' => $file->getClientOriginalName(),
                'mime_type' => $mime,
                'file_size' => strlen($content),
            ];

            if (Schema::hasColumn('member_photos', 'compressed_photo_data')) {
                $updateData['compressed_photo_data'] = $this->optimizer->compressBase64($photoBase64, 550, 80) ?: $photoBase64;
            }

            $memberPhoto = $member->memberPhoto;
            if ($memberPhoto) {
                $memberPhoto->update($updateData);
            } else {
                $updateData['member_id'] = $member->id;
                MemberPhoto::create($updateData);
            }

            if ($member->photo) {
                $this->mediaService->deleteMediaFile($member->photo);
                $member->update(['photo_file_id' => null]);
            }
        }

        $adminUser = Auth::guard('admin')->user() ?? Auth::guard('web')->user() ?? Auth::user();
        $adminEmail = $adminUser ? $adminUser->email : 'admin@bycgrowth.org';

        $updateData = [
            'full_name' => $validated['full_name'],
            'last_action_by' => $adminEmail,
            'last_action_type' => 'edited',
            'last_action_at' => now(),
        ];

        if (array_key_exists('date_of_birth', $validated)) {
            $updateData['date_of_birth'] = $validated['date_of_birth'];
        }

        $member->update($updateData);

        AuditLog::record($adminUser, 'edited', 'member', $member->id, "Updated member '{$member->full_name}'");

        $this->handleUserLinking($request, $member);

        $target = ($request->is('admin/*') || ($request->header('referer') && str_contains($request->header('referer'), '/admin/members')))
            ? route('admin.members')
            : route('members');

        return redirect($target)->with('success', 'Member updated successfully.');
    }

    /**
     * Handle user account linking or creating a new user account for a member.
     */
    protected function handleUserLinking(Request $request, Member $member): void
    {
        $userId = $request->input('user_id');
        $newEmail = trim((string) $request->input('new_user_email', ''));

        if ($userId === '__new__' || (!empty($newEmail) && empty($userId))) {
            // Unlink any currently linked user
            User::where('member_id', $member->id)->update(['member_id' => null]);

            $request->validate([
                'new_user_email' => 'required|email|max:255|unique:users,email',
                'new_user_username' => 'nullable|string|min:3|max:60|regex:/^[a-zA-Z0-9_]+$/|unique:users,username',
                'new_user_password' => 'nullable|string|min:6',
            ]);

            $email = strtolower($newEmail);
            $password = $request->filled('new_user_password') ? $request->input('new_user_password') : 'password123';
            $username = $request->filled('new_user_username') ? strtolower($request->input('new_user_username')) : null;

            User::create([
                'name' => $member->full_name,
                'username' => $username,
                'email' => $email,
                'password' => Hash::make($password),
                'role' => 'user',
                'member_id' => $member->id,
            ]);
        } elseif (!empty($userId) && is_numeric($userId)) {
            $chosenId = (int) $userId;
            // Unlink any other user attached to this member
            User::where('member_id', $member->id)->where('id', '!=', $chosenId)->update(['member_id' => null]);
            // Attach chosen user
            User::where('id', $chosenId)->update(['member_id' => $member->id]);
        } elseif ($request->isMethod('post') && !$member->user) {
            // Guarantee every newly created member has a linked user account
            $baseSlug = \Illuminate\Support\Str::slug($member->full_name, '.') ?: 'member';
            $candidateEmail = $baseSlug . '.' . $member->id . '@bycgrowth.org';
            $candidateUsername = \Illuminate\Support\Str::slug($member->full_name, '_') . '_' . $member->id;

            $counter = 1;
            while (User::where('email', $candidateEmail)->exists()) {
                $candidateEmail = $baseSlug . '.' . $member->id . '.' . $counter . '@bycgrowth.org';
                $counter++;
            }

            User::create([
                'name' => $member->full_name,
                'username' => $candidateUsername,
                'email' => $candidateEmail,
                'password' => Hash::make('password123'),
                'role' => 'user',
                'member_id' => $member->id,
            ]);
        } elseif ($request->has('user_id') && empty($userId)) {
            // Unlink account
            User::where('member_id', $member->id)->update(['member_id' => null]);
        }
    }

    /**
     * Remove the specified member from storage.
     */
    public function destroy(Request $request, int $id): RedirectResponse
    {
        $member = Member::findOrFail($id);
        $adminUser = Auth::guard('admin')->user() ?? Auth::guard('web')->user() ?? Auth::user();
        $memberName = $member->full_name;
        $memberId = $member->id;

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

        AuditLog::record($adminUser, 'deleted', 'member', $memberId, "Deleted member '{$memberName}'");

        $target = ($request->is('admin/*') || ($request->header('referer') && str_contains($request->header('referer'), '/admin/members')))
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
        $adminUser = Auth::guard('admin')->user() ?? Auth::guard('web')->user() ?? Auth::user();

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

        AuditLog::record($adminUser, 'batch_deleted', 'member', null, "Batch deleted {$count} members");

        return redirect()->route('admin.members')
            ->with('success', "Selected {$count} " . ($count === 1 ? 'member has' : 'members have') . ' been removed successfully.');
    }
}
