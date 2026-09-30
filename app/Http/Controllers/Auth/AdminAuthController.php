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
        if (Auth::check()) {
            if ($request->filled('redirect')) {
                return redirect($request->input('redirect'));
            }

            /** @var User $user */
            $user = Auth::user();
            if ($user->isAdmin()) {
                return redirect()->route('admin.dashboard');
            }

            return redirect()->route('home');
        }

        if ($request->filled('redirect')) {
            session()->put('url.intended', $request->input('redirect'));
        }

        return view('admin.login');
    }

    /**
     * Handle an incoming admin authentication request.
     * Supports both email and username.
     */
    public function login(Request $request): RedirectResponse
    {
        $loginInput = trim($request->input('login') ?? $request->input('email') ?? '');
        $password = (string) $request->input('password', '');

        if ($loginInput === '' || $password === '') {
            return back()
                ->withInput($request->only('login', 'email'))
                ->withErrors([
                    'login' => 'Email or username and password are required.',
                    'email' => 'Email or username and password are required.',
                ]);
        }

        // Query user by email or username
        $user = User::where('email', $loginInput)
            ->orWhere('username', $loginInput)
            ->first();

        if (!$user || !Hash::check($password, $user->password)) {
            return back()
                ->withInput($request->only('login', 'email'))
                ->withErrors([
                    'login' => 'These credentials do not match our records.',
                    'email' => 'These credentials do not match our records.',
                ]);
        }

        // Enforce administrator role requirement unless returning to a non-admin redirect target
        if (!$user->isAdmin()) {
            if ($request->filled('redirect') && !str_starts_with($request->input('redirect'), '/admin')) {
                Auth::login($user, $request->boolean('remember'));
                $request->session()->regenerate();
                session()->flash('welcome_user', $user->username);
                return redirect($request->input('redirect'));
            }

            return back()
                ->withInput($request->only('login', 'email'))
                ->withErrors([
                    'login' => 'These credentials do not have administrator access.',
                    'email' => 'These credentials do not have administrator access.',
                ]);
        }

        Auth::login($user, $request->boolean('remember'));
        $request->session()->regenerate();
        session()->flash('welcome_user', $user->username);

        if ($request->filled('redirect')) {
            return redirect($request->input('redirect'));
        }

        $intended = $request->session()->pull('url.intended');
        if ($intended) {
            return redirect($intended);
        }

        return redirect()->route('admin.dashboard');
    }

    /**
     * Log the administrator out of the application and invalidate session.
     */
    public function logout(Request $request): RedirectResponse
    {
        Auth::logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('admin.ganteng')->with('status', 'You have been logged out successfully.');
    }
}
