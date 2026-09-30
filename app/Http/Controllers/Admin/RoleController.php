<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
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
    public function index(): View
    {
        $users = User::with('member')->orderBy('created_at', 'desc')->get();
        $members = Member::where('is_active', true)->orderBy('full_name', 'asc')->get();

        return view('admin.roles', [
            'users' => $users,
            'members' => $members,
            'currentUser' => Auth::user(),
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
            'member_id' => !empty($validated['member_id']) ? (int) $validated['member_id'] : null,
        ];

        if (!empty($validated['username'])) {
            $userData['username'] = strtolower($validated['username']);
        }

        $user = User::create($userData);

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

        $user->delete();

        if ($request->wantsJson() || $request->ajax()) {
            return response()->json([
                'success' => true,
                'message' => 'Account deleted successfully.',
            ]);
        }

        return redirect()->route('admin.roles')->with('success', 'Account deleted successfully.');
    }
}
