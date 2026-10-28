<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\View\View;

class AuthController extends Controller
{
    /**
     * Show the user sign-in form.
     */
    public function showLoginForm(Request $request): View|RedirectResponse
    {
        if (Auth::guard('admin')->check()) {
            return redirect()->route('admin.dashboard');
        }

        if (Auth::guard('web')->check()) {
            if ($request->filled('redirect')) {
                return redirect($request->input('redirect'));
            }

            return redirect()->route('home');
        }

        if ($request->filled('redirect')) {
            session()->put('url.intended', $request->input('redirect'));
        }

        return view('auth.login');
    }

    /**
     * Handle incoming normal user authentication.
     * Supports email and password only. Strictly restricted to non-admin accounts.
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

        // Strictly reject Administrator accounts from logging in to User website
        if ($user->isAdmin()) {
            return back()
                ->withInput($request->only('email', 'login'))
                ->withErrors([
                    'email' => 'Administrator accounts cannot log in to the User portal.',
                ]);
        }

        Auth::guard('web')->login($user, $request->boolean('remember'));
        $request->session()->regenerate();
        session()->flash('welcome_user', $user->username);

        if ($request->filled('redirect')) {
            return redirect($request->input('redirect'));
        }

        return redirect()->intended(route('home'));
    }

    /**
     * Log user out of the application and invalidate session.
     */
    public function logout(Request $request): RedirectResponse
    {
        Auth::guard('web')->logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        app(\App\Services\GameStorageService::class)->resetGame2Revealed();

        return redirect()->route('home')->with('status', 'You have been logged out successfully.');
    }
}
