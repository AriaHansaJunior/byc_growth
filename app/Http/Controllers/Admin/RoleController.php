<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
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
        $users = User::orderBy('name', 'asc')->get();

        return view('admin.roles', [
            'users' => $users,
            'currentUser' => Auth::user(),
        ]);
    }

    /**
     * Store a newly created account.
     */
    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users,email',
            'password' => 'required|string|min:6',
            'role' => ['required', Rule::in(['admin', 'user'])],
        ]);

        User::create([
            'name' => $validated['name'],
            'email' => $validated['email'],
            'password' => Hash::make($validated['password']),
            'role' => $validated['role'],
        ]);

        return redirect()->route('admin.roles')->with('success', 'Account created successfully.');
    }

    /**
     * Update the specified account.
     */
    public function update(Request $request, int $id): RedirectResponse
    {
        $user = User::findOrFail($id);

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => ['required', 'string', 'email', 'max:255', Rule::unique('users')->ignore($user->id)],
            'password' => 'nullable|string|min:6',
            'role' => ['required', Rule::in(['admin', 'user'])],
        ]);

        $updateData = [
            'name' => $validated['name'],
            'email' => $validated['email'],
            'role' => $validated['role'],
        ];

        if (!empty($validated['password'])) {
            $updateData['password'] = Hash::make($validated['password']);
        }

        $user->update($updateData);

        return redirect()->route('admin.roles')->with('success', 'Account updated successfully.');
    }

    /**
     * Remove the specified account from storage.
     * Prevents deletion of the currently authenticated admin account.
     */
    public function destroy(int $id): RedirectResponse
    {
        $user = User::findOrFail($id);

        if ($user->id === Auth::id()) {
            return redirect()->route('admin.roles')->with('error', 'Cannot delete your own active administrator account.');
        }

        $user->delete();

        return redirect()->route('admin.roles')->with('success', 'Account deleted successfully.');
    }
}
