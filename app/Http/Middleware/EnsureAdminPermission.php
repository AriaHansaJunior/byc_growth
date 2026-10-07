<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class EnsureAdminPermission
{
    /**
     * Handle an incoming request.
     *
     * Ensures that the authenticated administrator has the required module permission.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     * @param  string  $permission
     */
    public function handle(Request $request, Closure $next, string $permission): Response
    {
        /** @var \App\Models\User|null $user */
        $user = Auth::guard('admin')->user() ?? Auth::guard('web')->user() ?? Auth::user();

        if (!$user instanceof \App\Models\User || !$user->isAdmin()) {
            if ($request->expectsJson() || $request->ajax()) {
                return response()->json(['success' => false, 'error' => 'Administrator authorization required.'], 403);
            }

            return redirect()->route('admin.ganteng')->with('error', 'Administrator authorization required.');
        }

        if (!$user->hasPermission($permission)) {
            $moduleLabels = [
                'homepage' => 'Homepage',
                'activities' => 'Activities',
                'members' => 'Members',
                'games' => 'Games',
                'birthday_wishes' => 'Birthday Wishes',
                'cash_management' => 'Cash Management',
                'roles' => 'Roles & Accounts',
            ];
            $label = $moduleLabels[$permission] ?? ucfirst(str_replace('_', ' ', $permission));

            if ($request->expectsJson() || $request->ajax() || $request->wantsJson()) {
                return response()->json([
                    'success' => false,
                    'error' => "Access denied. You do not have permission to access the {$label} module.",
                ], 403);
            }

            return redirect()->route('admin.dashboard')->with('error', "Access denied. You do not have permission to access the {$label} module.");
        }

        return $next($request);
    }
}
