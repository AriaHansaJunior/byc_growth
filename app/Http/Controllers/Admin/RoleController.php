<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Models\Member;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class RoleController extends Controller
{
    /**
     * Display accounts and role management interface.
     */
    public function index(Request $request): View
    {
        $search = trim((string) $request->query('search', ''));
        $role = (string) $request->query('role', 'all');
        $sort = (string) $request->query('sort', 'newest');

        $query = User::with('member');

        if ($search !== '') {
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('username', 'like', "%{$search}%")
                  ->orWhere('email', 'like', "%{$search}%");
            });
        }

        if (in_array($role, ['admin', 'user'], true)) {
            $query->where('role', $role);
        }

        switch ($sort) {
            case 'oldest':
                $query->orderBy('created_at', 'asc');
                break;
            case 'username_asc':
                $query->orderBy('username', 'asc');
                break;
            case 'username_desc':
                $query->orderBy('username', 'desc');
                break;
            case 'name_asc':
                $query->orderBy('name', 'asc');
                break;
            case 'name_desc':
                $query->orderBy('name', 'desc');
                break;
            case 'newest':
            default:
                $query->orderBy('created_at', 'desc');
                break;
        }

        $users = $query->get();
        $members = Member::where('is_active', true)->orderBy('full_name', 'asc')->get();

        $totalUsersCount = User::count();
        $totalAdminsCount = User::where('role', 'admin')->count();
        $totalStandardUsersCount = User::where('role', 'user')->count();

        return view('admin.roles', [
            'users' => $users,
            'members' => $members,
            'currentUser' => Auth::user(),
            'totalUsersCount' => $totalUsersCount,
            'totalAdminsCount' => $totalAdminsCount,
            'totalStandardUsersCount' => $totalStandardUsersCount,
            'filters' => [
                'search' => $search,
                'role' => $role,
                'sort' => $sort,
            ],
            'availablePermissions' => User::AVAILABLE_PERMISSIONS,
        ]);
    }

    /**
     * Get specific user account details (JSON for modal / inspection).
     */
    public function show(int $id): JsonResponse
    {
        $user = User::with('member')->findOrFail($id);

        return response()->json([
            'id' => $user->id,
            'username' => $user->username,
            'name' => $user->name,
            'email' => $user->email,
            'role' => $user->role,
            'permissions' => $user->permissions ?? [],
            'member_id' => $user->member_id,
            'member_name' => $user->member ? $user->member->full_name : null,
            'created_at' => $user->created_at ? $user->created_at->format('M j, Y H:i') : null,
        ]);
    }

    /**
     * Store a newly created account.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'username' => [
                $request->has('username') ? 'required' : 'nullable',
                'string',
                'min:3',
                'max:60',
                'regex:/^[a-zA-Z0-9_]+$/',
                'unique:users,username',
            ],
            'name' => ['nullable', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255', 'unique:users,email'],
            'password' => ['required', 'string', 'min:6'],
            'role' => ['required', Rule::in(['admin', 'user'])],
            'member_id' => ['nullable', 'exists:members,id', 'unique:users,member_id'],
        ]);

        $userData = [
            'name' => !empty($validated['name']) ? $validated['name'] : ($validated['username'] ?? null),
            'email' => strtolower($validated['email']),
            'password' => Hash::make($validated['password']),
            'role' => $validated['role'],
            'permissions' => $validated['role'] === 'admin' ? [] : null,
            'member_id' => !empty($validated['member_id']) ? (int) $validated['member_id'] : null,
        ];

        if (!empty($validated['username'])) {
            $userData['username'] = strtolower($validated['username']);
        }

        $user = User::create($userData);

        AuditLog::record(Auth::user(), 'created', 'roles', $user->id, "Created account '{$user->email}' with role {$user->role}");

        if ($request->wantsJson() || $request->ajax()) {
            return response()->json([
                'success' => true,
                'message' => 'Account created successfully.',
                'user' => $user->fresh('member'),
            ], 201);
        }

        return redirect()->route('admin.roles')->with('success', 'Account created successfully.');
    }

    /**
     * Update the specified account.
     */
    public function update(Request $request, int $id)
    {
        $user = User::findOrFail($id);

        $validated = $request->validate([
            'username' => [
                $request->has('username') ? 'required' : 'nullable',
                'string',
                'min:3',
                'max:60',
                'regex:/^[a-zA-Z0-9_]+$/',
                Rule::unique('users', 'username')->ignore($user->id),
            ],
            'name' => ['nullable', 'string', 'max:255'],
            'email' => [
                'required',
                'string',
                'email',
                'max:255',
                Rule::unique('users', 'email')->ignore($user->id),
            ],
            'password' => ['nullable', 'string', 'min:6'],
            'role' => ['required', Rule::in(['admin', 'user'])],
            'permissions' => ['nullable', 'array'],
            'permissions.*' => ['string', Rule::in(array_keys(User::AVAILABLE_PERMISSIONS))],
            'member_id' => [
                'nullable',
                'exists:members,id',
                Rule::unique('users', 'member_id')->ignore($user->id),
            ],
        ]);

        $updateData = [
            'email' => strtolower($validated['email']),
            'role' => $validated['role'],
            'member_id' => !empty($validated['member_id']) ? (int) $validated['member_id'] : null,
        ];

        if ($validated['role'] === 'admin') {
            $updateData['permissions'] = array_values(array_unique($request->input('permissions', [])));
        } else {
            $updateData['permissions'] = null;
        }

        if (!empty($validated['name'])) {
            $updateData['name'] = $validated['name'];
        }

        if (!empty($validated['username'])) {
            $updateData['username'] = strtolower($validated['username']);
        }

        // Safe password update: only update password when a new password is explicitly supplied
        if (!empty($validated['password'])) {
            $updateData['password'] = Hash::make($validated['password']);
        }

        $user->update($updateData);

        AuditLog::record(Auth::user(), 'edited', 'roles', $user->id, "Updated account '{$user->email}'");

        if ($request->wantsJson() || $request->ajax()) {
            return response()->json([
                'success' => true,
                'message' => 'Account updated successfully.',
                'user' => $user->fresh('member'),
            ]);
        }

        return redirect()->route('admin.roles')->with('success', 'Account updated successfully.');
    }

    /**
     * Remove the specified account from storage.
     * Prevents deletion of the currently authenticated admin account.
     */
    public function destroy(Request $request, int $id)
    {
        $user = User::findOrFail($id);

        // Critical Rule: An admin MUST NOT be able to delete their own currently authenticated account
        if ($user->id === Auth::id()) {
            if ($request->wantsJson() || $request->ajax()) {
                return response()->json([
                    'success' => false,
                    'error' => 'Cannot delete your own active administrator account.',
                ], 403);
            }

            return redirect()->route('admin.roles')->with('error', 'Cannot delete your own active administrator account.');
        }

        $userEmail = $user->email;
        $userId = $user->id;
        $user->delete();

        AuditLog::record(Auth::user(), 'deleted', 'roles', $userId, "Deleted account '{$userEmail}'");

        if ($request->wantsJson() || $request->ajax()) {
            return response()->json([
                'success' => true,
                'message' => 'Account deleted successfully.',
            ]);
        }

        return redirect()->route('admin.roles')->with('success', 'Account deleted successfully.');
    }

    /**
     * Remove multiple accounts from storage.
     * Prevents deletion of the currently authenticated admin account.
     */
    public function batchDestroy(Request $request)
    {
        $validated = $request->validate([
            'ids' => ['required', 'array', 'min:1'],
            'ids.*' => ['required', 'integer', 'exists:users,id'],
        ]);

        $currentUserId = Auth::id();
        // Prevent deleting current logged-in admin account
        $targetIds = array_values(array_filter($validated['ids'], function ($id) use ($currentUserId) {
            return (int) $id !== (int) $currentUserId;
        }));

        if (empty($targetIds)) {
            if ($request->wantsJson() || $request->ajax()) {
                return response()->json([
                    'success' => false,
                    'error' => 'Cannot delete your own active administrator account.',
                ], 403);
            }

            return redirect()->route('admin.roles')->with('error', 'Cannot delete your own active administrator account.');
        }

        $targetUsers = User::whereIn('id', $targetIds)->get();
        $deletedCount = 0;
        foreach ($targetUsers as $user) {
            $user->delete();
            $deletedCount++;
        }

        AuditLog::record(Auth::user(), 'batch_deleted', 'roles', null, "Batch deleted {$deletedCount} accounts");

        $message = "Successfully deleted {$deletedCount} account" . ($deletedCount === 1 ? '' : 's') . '.';

        if ($request->wantsJson() || $request->ajax()) {
            return response()->json([
                'success' => true,
                'message' => $message,
                'deleted_count' => $deletedCount,
            ]);
        }

        return redirect()->route('admin.roles')->with('success', $message);
    }
}
