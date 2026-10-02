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
        if (Auth::check()) {
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

        Auth::login($user, true);
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
        Auth::logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('home')->with('status', 'You have been logged out successfully.');
    }
}
