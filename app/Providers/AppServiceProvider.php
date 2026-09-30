<?php

namespace App\Providers;

use App\Models\Member;
use Carbon\Carbon;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        View::composer(['layouts.app', 'layouts.admin', 'welcome', 'user.*'], function ($view) {
            try {
                if (Schema::hasTable('members')) {
                    $birthdayMembers = Member::with('photo')
                        ->where('is_active', true)
                        ->birthdayToday()
                        ->orderBy('full_name', 'asc')
                        ->get();
                    $view->with('birthdayMembers', $birthdayMembers);
                } else {
                    $view->with('birthdayMembers', collect());
                }
            } catch (\Throwable $e) {
                $view->with('birthdayMembers', collect());
            }
        });
    }
}
