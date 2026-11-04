<?php

namespace App\Providers;

use App\Models\BirthdayLetter;
use App\Models\Member;
use App\Services\IdSequenceService;
use Carbon\Carbon;
use Illuminate\Foundation\Http\Events\RequestHandled;
use Illuminate\Routing\Events\RouteMatched;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Event;
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
        Event::listen(RouteMatched::class, function (RouteMatched $event) {
            if (!$event->request->isMethodSafe()) {
                IdSequenceService::resequenceAll();
            }
        });

        Event::listen(RequestHandled::class, function (RequestHandled $event) {
            if (!$event->request->isMethodSafe()) {
                IdSequenceService::resequenceAll();
            }
        });

        View::composer(['layouts.app', 'layouts.admin', 'welcome', 'user.*', 'components.*'], function ($view) {
            try {
                if (Schema::hasTable('members')) {
                    $birthdayMembers = Member::with(['photo', 'memberPhoto'])
                        ->where('is_active', true)
                        ->birthdayToday()
                        ->orderBy('full_name', 'asc')
                        ->get();
                    $view->with('birthdayMembers', $birthdayMembers);
                } else {
                    $view->with('birthdayMembers', collect());
                }

                if (Auth::check() && Schema::hasTable('birthday_letters')) {
                    $currentYear = (int) Carbon::now('Asia/Jakarta')->year;
                    $userLetters = BirthdayLetter::where('user_id', Auth::id())
                        ->where('birthday_year', $currentYear)
                        ->get()
                        ->keyBy('member_id');
                    $view->with('userBirthdayLetters', $userLetters);
                } else {
                    $view->with('userBirthdayLetters', collect());
                }
            } catch (\Throwable $e) {
                $view->with('birthdayMembers', collect());
                $view->with('userBirthdayLetters', collect());
            }
        });
    }
}
