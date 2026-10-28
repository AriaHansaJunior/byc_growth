<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\View\View;

class AdminAuthController extends Controller
{
    /**
     * Show the administrator sign-in form.
     */
    public function showLoginForm(Request $request): View|RedirectResponse
    {
        if (Auth::guard('admin')->check()) {
            /** @var User $user */
            $user = Auth::guard('admin')->user();
            if ($user && $user->isAdmin()) {
                if ($request->filled('redirect')) {
                    return redirect($request->input('redirect'));
                }

                return redirect()->route('admin.dashboard');
            }
        }

        if ($request->filled('redirect')) {
            session()->put('url.intended', $request->input('redirect'));
        }

        return view('admin.login');
    }

    /**
     * Handle an incoming admin authentication request.
     * Supports email and password only. Strictly restricted to administrator accounts.
     */
    public function login(Request $request): RedirectResponse
    {
        $email = strtolower(trim((string) ($request->input('email') ?? $request->input('login') ?? '')));
        $password = (string) $request->input('password', '');

        if ($email === '' || $password === '') {
            return back()
                ->withInput($request->only('email', 'login'))
                ->withErrors([
                    'email' => 'Email and password are required.',
                ]);
        }

        // Query user strictly by email (username login removed as per system policy)
        $user = User::where('email', $email)->first();

        if (!$user || !Hash::check($password, $user->password)) {
            return back()
                ->withInput($request->only('email', 'login'))
                ->withErrors([
                    'email' => 'These credentials do not match our records.',
                ]);
        }

        // Strictly reject standard users from logging in to the admin portal
        if (!$user->isAdmin()) {
            return back()
                ->withInput($request->only('email', 'login'))
                ->withErrors([
                    'email' => 'Standard user accounts do not have administrator access.',
                ]);
        }

        Auth::guard('admin')->login($user, false);
        $request->session()->regenerate();
        session()->flash('welcome_user', $user->username);

        if ($request->filled('redirect')) {
            return redirect($request->input('redirect'));
        }

        $intended = $request->session()->pull('url.intended');
        if ($intended && !str_contains($intended, 'admin-ganteng')) {
            return redirect($intended);
        }

        return redirect()->route('admin.dashboard');
    }

    /**
     * Log the administrator out of the application and invalidate session.
     */
    public function logout(Request $request): RedirectResponse
    {
        Auth::guard('admin')->logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        app(\App\Services\GameStorageService::class)->resetGame2Revealed();

        return redirect()->route('admin.ganteng')->with('status', 'You have been logged out successfully.');
    }
}
