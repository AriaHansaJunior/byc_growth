<?php

use App\Http\Controllers\ActivityController;
use App\Http\Controllers\Admin\ActivityController as AdminActivityController;
use App\Http\Controllers\Admin\AdminDashboardController;
use App\Http\Controllers\Admin\BirthdayWishController;
use App\Http\Controllers\Admin\CashManagementController;
use App\Http\Controllers\Admin\GameController as AdminGameController;
use App\Http\Controllers\Admin\HomepageController;
use App\Http\Controllers\Admin\MemberController;
use App\Http\Controllers\Admin\RoleController;
use App\Http\Controllers\Auth\AdminAuthController;
use App\Http\Controllers\Auth\AuthController;
use App\Http\Controllers\BirthdayController;
use App\Http\Controllers\GameController;
use App\Http\Controllers\PageController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Web Routes — BYC GROWTH 2.0
|--------------------------------------------------------------------------
|
| Public website architecture, interactive games, and administrator portal.
|
*/

// Public Website Architecture (Direct Access Without Login)
Route::get('/', [PageController::class, 'home'])->name('home');
Route::get('/about', [PageController::class, 'about'])->name('about');
Route::get('/activity', [ActivityController::class, 'index'])->name('activity');
Route::get('/members', [PageController::class, 'members'])->name('members');
Route::get('/game-center', [GameController::class, 'gameCenter'])->name('game.center');

// Legacy Contact Route — Redirects Safely to Combined About & Contact Page
Route::get('/contact', [PageController::class, 'contact'])->name('contact');

// Birthday System Endpoints
Route::get('/birthday/today', [BirthdayController::class, 'today'])->name('birthday.today');
Route::post('/birthday/letter', [BirthdayController::class, 'submitLetter'])->name('birthday.letter');
Route::match(['put', 'patch', 'post'], '/birthday/letter/{id}', [BirthdayController::class, 'updateLetter'])->name('birthday.letter.update');
Route::get('/birthday/letter/{id}', [BirthdayController::class, 'showLetter'])->name('birthday.letter.show');
Route::get('/birthday-wishes', [BirthdayController::class, 'wishes'])->name('birthday.wishes');

// Interactive Games (Public Access Preserved)
Route::get('/guess-me', [GameController::class, 'guessMe'])->name('game.guess-me');
Route::get('/growth-100', [GameController::class, 'growth100'])->name('game.growth-100');
Route::get('/final', [GameController::class, 'finalScore'])->name('game.final');
Route::get('/game/image/{filename}', [GameController::class, 'getImage'])->name('game.image');

// Public Gameplay State Navigation
Route::prefix('game')->name('game.')->group(function () {
    Route::post('/guess-me/state', [GameController::class, 'updateGame1State'])->name('guess-me.state');
    Route::post('/growth-100/state', [GameController::class, 'updateGame2State'])->name('growth-100.state');
});

// Protected Game Management Endpoints (Requires Auth & Admin Role)
Route::middleware(['auth', 'admin'])->prefix('game')->name('game.')->group(function () {
    Route::post('/update-score', [GameController::class, 'updateScore'])->name('update-score');
    Route::post('/assign-round-points', [GameController::class, 'assignRoundPoints'])->name('assign-round-points');
    Route::post('/teams/configure', [GameController::class, 'configureTeams'])->name('teams.configure');
    Route::post('/reset', [GameController::class, 'resetGame'])->name('reset');

    // Game 1 — Guess Me! Management
    Route::post('/guess-me/round', [GameController::class, 'saveGuessMeRound'])->name('guess-me.save-round');
    Route::match(['put', 'patch'], '/guess-me/round/{id}', [GameController::class, 'saveGuessMeRound'])->name('guess-me.update-round');
    Route::post('/guess-me/batch', [GameController::class, 'saveGuessMeBatchRounds'])->name('guess-me.batch');
    Route::delete('/guess-me/round/{id}', [GameController::class, 'deleteGuessMeRound'])->name('guess-me.delete-round');

    // Game 2 — BYC Growth 100 Management
    Route::post('/growth-100/round', [GameController::class, 'saveGrowth100Round'])->name('growth-100.save-round');
    Route::match(['put', 'patch'], '/growth-100/round/{id}', [GameController::class, 'saveGrowth100Round'])->name('growth-100.update-round');
    Route::post('/growth-100/batch', [GameController::class, 'saveGrowth100BatchRounds'])->name('growth-100.batch');
    Route::delete('/growth-100/round/{id}', [GameController::class, 'deleteGrowth100Round'])->name('growth-100.delete-round');

    // Answer-level Management
    Route::post('/growth-100/round/{roundId}/answers', [AdminGameController::class, 'addGrowth100Answer'])->name('growth-100.add-answer');
    Route::match(['put', 'patch', 'post'], '/growth-100/answers/{id}', [AdminGameController::class, 'updateGrowth100Answer'])->name('growth-100.update-answer');
    Route::delete('/growth-100/answers/{id}', [AdminGameController::class, 'deleteGrowth100Answer'])->name('growth-100.delete-answer');
});

// Normal User Authentication
Route::get('/login', [AuthController::class, 'showLoginForm'])->name('login');
Route::post('/login', [AuthController::class, 'login'])->name('login.submit');
Route::post('/logout', [AuthController::class, 'logout'])->name('logout');

// Hidden Admin Login Entry (/admin-ganteng) & Legacy Compatibility
Route::get('/admin-ganteng', [AdminAuthController::class, 'showLoginForm'])->name('admin.ganteng');
Route::post('/admin-ganteng', [AdminAuthController::class, 'login'])->name('admin.ganteng.submit');
Route::get('/admin/login', [AdminAuthController::class, 'showLoginForm'])->name('admin.login');
Route::post('/admin/login', [AdminAuthController::class, 'login'])->name('admin.login.submit');

// Protected Administrator Routes (Requires Auth & Admin Role)
Route::middleware(['auth', 'admin'])->group(function () {
    Route::prefix('admin')->name('admin.')->group(function () {
        Route::get('/', fn () => redirect()->route('admin.dashboard'));
        Route::get('/dashboard', [AdminDashboardController::class, 'index'])->name('dashboard');
        Route::post('/logout', [AdminAuthController::class, 'logout'])->name('logout');

        // Homepage Data & Slideshow Management
        Route::get('/homepage', [HomepageController::class, 'index'])->name('homepage');
        Route::post('/homepage/slides', [HomepageController::class, 'storeSlide'])->name('homepage.slides.store');
        Route::delete('/homepage/slides/{id}', [HomepageController::class, 'destroySlide'])->name('homepage.slides.destroy');
        Route::post('/homepage/slides/reorder', [HomepageController::class, 'reorderSlides'])->name('homepage.slides.reorder');
        Route::post('/homepage/slides/{id}/move-up', [HomepageController::class, 'moveSlideUp'])->name('homepage.slides.move-up');
        Route::post('/homepage/slides/{id}/move-down', [HomepageController::class, 'moveSlideDown'])->name('homepage.slides.move-down');

        // Member Management CRUD
        Route::get('/members', [MemberController::class, 'index'])->name('members');
        Route::post('/members', [MemberController::class, 'store'])->name('members.store');
        Route::match(['put', 'post'], '/members/{id}', [MemberController::class, 'update'])->name('members.update');
        Route::delete('/members/{id}', [MemberController::class, 'destroy'])->name('members.destroy');

        // Activity Management CRUD
        Route::get('/activities', [AdminActivityController::class, 'index'])->name('activities');
        Route::post('/activities', [AdminActivityController::class, 'store'])->name('activities.store');
        Route::match(['put', 'post'], '/activities/{id}', [AdminActivityController::class, 'update'])->name('activities.update');
        Route::delete('/activities/{id}', [AdminActivityController::class, 'destroy'])->name('activities.destroy');

        // Games Management (Scope 4)
        Route::get('/games', [AdminGameController::class, 'index'])->name('games');
        Route::post('/games/guess-me/round', [AdminGameController::class, 'saveGuessMeRound'])->name('games.guess-me.save-round');
        Route::match(['put', 'patch', 'post'], '/games/guess-me/round/{id}', [AdminGameController::class, 'saveGuessMeRound'])->name('games.guess-me.update-round');
        Route::delete('/games/guess-me/round/{id}', [AdminGameController::class, 'deleteGuessMeRound'])->name('games.guess-me.delete-round');
        Route::post('/games/guess-me/reorder', [AdminGameController::class, 'reorderGuessMeRounds'])->name('games.guess-me.reorder');

        Route::post('/games/growth-100/round', [AdminGameController::class, 'saveGrowth100Round'])->name('games.growth-100.save-round');
        Route::match(['put', 'patch', 'post'], '/games/growth-100/round/{id}', [AdminGameController::class, 'saveGrowth100Round'])->name('games.growth-100.update-round');
        Route::delete('/games/growth-100/round/{id}', [AdminGameController::class, 'deleteGrowth100Round'])->name('games.growth-100.delete-round');
        Route::post('/games/growth-100/reorder', [AdminGameController::class, 'reorderGrowth100Rounds'])->name('games.growth-100.reorder');

        Route::post('/games/growth-100/round/{roundId}/answers', [AdminGameController::class, 'addGrowth100Answer'])->name('games.growth-100.add-answer');
        Route::match(['put', 'patch', 'post'], '/games/growth-100/answers/{id}', [AdminGameController::class, 'updateGrowth100Answer'])->name('games.growth-100.update-answer');
        Route::delete('/games/growth-100/answers/{id}', [AdminGameController::class, 'deleteGrowth100Answer'])->name('games.growth-100.delete-answer');

        // Birthday Wishes Administration (Scope 5)
        Route::get('/birthday-wishes', [BirthdayWishController::class, 'index'])->name('birthday-wishes');
        Route::get('/birthday-wishes/{id}', [BirthdayWishController::class, 'show'])->name('birthday-wishes.show');
        Route::match(['put', 'patch', 'post'], '/birthday-wishes/{id}', [BirthdayWishController::class, 'update'])->name('birthday-wishes.update');
        Route::delete('/birthday-wishes/{id}', [BirthdayWishController::class, 'destroy'])->name('birthday-wishes.destroy');

        // Cash Management Portal (Scope 5)
        Route::get('/cash-management', [CashManagementController::class, 'adminIndex'])->name('cash-management');
        Route::post('/cash-management', [CashManagementController::class, 'store'])->name('cash.store');
        Route::get('/cash-management/transaction/{id}', [CashManagementController::class, 'show'])->name('cash.show');
        Route::match(['put', 'patch', 'post'], '/cash-management/{id}', [CashManagementController::class, 'update'])->name('cash.update');
        Route::delete('/cash-management/{id}', [CashManagementController::class, 'destroy'])->name('cash.destroy');
        Route::get('/cash-management/shortcut/{memberId}', [CashManagementController::class, 'shortcut'])->name('cash.shortcut');
        Route::get('/cash-management/member/{memberId}/shortcut', [CashManagementController::class, 'shortcut'])->name('cash.member.shortcut');

        // Role & Account Management
        Route::get('/roles', [RoleController::class, 'index'])->name('roles');
        Route::post('/roles', [RoleController::class, 'store'])->name('roles.store');
        Route::match(['put', 'post'], '/roles/{id}', [RoleController::class, 'update'])->name('roles.update');
        Route::delete('/roles/{id}', [RoleController::class, 'destroy'])->name('roles.destroy');
    });

    // Cash Management Portal (Protected at Backend Level)
    Route::get('/cash-management', [CashManagementController::class, 'index'])->name('cash-management');
});
