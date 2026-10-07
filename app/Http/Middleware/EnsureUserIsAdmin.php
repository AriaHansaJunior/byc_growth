<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class EnsureUserIsAdmin
{
    /**
     * Handle an incoming request.
     *
     * Ensures that only authenticated users with the 'admin' role can access the route.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        /** @var \App\Models\User|null $user */
        $user = Auth::guard('admin')->user();

        if (!$user && Auth::guard('web')->check()) {
            $webUser = Auth::guard('web')->user();
            if ($webUser && $webUser->isAdmin()) {
                $user = $webUser;
                Auth::guard('admin')->setUser($user);
            }
        }

        if (!$user) {
            if ($request->expectsJson()) {
                return response()->json(['message' => 'Unauthenticated.'], 401);
            }

            return redirect()->route('admin.ganteng')->with('error', 'Please sign in to access the administrator portal.');
        }

        if (!$user->isAdmin()) {
            if ($request->expectsJson()) {
                return response()->json(['message' => 'Forbidden. Administrator role required.'], 403);
            }

            abort(403, 'Unauthorized access. Administrator role is required.');
        }

        Auth::shouldUse('admin');

        return $next($request);
    }
}
