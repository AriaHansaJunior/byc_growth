<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class AdminDashboardController extends Controller
{
    /**
     * Display the administrator dashboard overview.
     */
    public function index(): View
    {
        $admin = Auth::user();

        return view('admin.dashboard', [
            'admin' => $admin,
        ]);
    }
}
