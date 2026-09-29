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
        View::composer('layouts.app', function ($view) {
            try {
                if (Schema::hasTable('members')) {
                    $today = Carbon::now('Asia/Jakarta');
                    $birthdayMembers = Member::with('photo')
                        ->where('is_active', true)
                        ->whereNotNull('date_of_birth')
                        ->whereRaw("DATE_FORMAT(date_of_birth, '%m-%d') = ?", [$today->format('m-d')])
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
