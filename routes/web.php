<?php

use App\Http\Controllers\ActivityController;
use App\Http\Controllers\Admin\AdminDashboardController;
use App\Http\Controllers\Admin\CashManagementController;
use App\Http\Controllers\Admin\MemberController;
use App\Http\Controllers\Admin\RoleController;
use App\Http\Controllers\Auth\AdminAuthController;
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

// Birthday System Public Endpoints
Route::get('/birthday/today', [BirthdayController::class, 'today'])->name('birthday.today');
Route::post('/birthday/letter', [BirthdayController::class, 'submitLetter'])->name('birthday.letter');

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
    Route::post('/guess-me/batch', [GameController::class, 'saveGuessMeBatchRounds'])->name('guess-me.batch');
    Route::delete('/guess-me/round/{id}', [GameController::class, 'deleteGuessMeRound'])->name('guess-me.delete-round');

    // Game 2 — BYC Growth 100 Management
    Route::post('/growth-100/round', [GameController::class, 'saveGrowth100Round'])->name('growth-100.save-round');
    Route::post('/growth-100/batch', [GameController::class, 'saveGrowth100BatchRounds'])->name('growth-100.batch');
    Route::delete('/growth-100/round/{id}', [GameController::class, 'deleteGrowth100Round'])->name('growth-100.delete-round');
});

// Admin Authentication (Guest Only)
Route::prefix('admin')->name('admin.')->group(function () {
    Route::get('/login', [AdminAuthController::class, 'showLoginForm'])->name('login');
    Route::post('/login', [AdminAuthController::class, 'login'])->name('login.submit');
});

// Standard Login Fallback
Route::get('/login', fn () => redirect()->route('admin.login'))->name('login');

// Protected Administrator Routes (Requires Auth & Admin Role)
Route::middleware(['auth', 'admin'])->group(function () {
    Route::prefix('admin')->name('admin.')->group(function () {
        Route::get('/', fn () => redirect()->route('admin.dashboard'));
        Route::get('/dashboard', [AdminDashboardController::class, 'index'])->name('dashboard');
        Route::post('/logout', [AdminAuthController::class, 'logout'])->name('logout');

        // Member Management CRUD
        Route::post('/members', [MemberController::class, 'store'])->name('members.store');
        Route::match(['put', 'post'], '/members/{id}', [MemberController::class, 'update'])->name('members.update');
        Route::delete('/members/{id}', [MemberController::class, 'destroy'])->name('members.destroy');

        // Activity Management CRUD
        Route::post('/activities', [ActivityController::class, 'store'])->name('activities.store');
        Route::match(['put', 'post'], '/activities/{id}', [ActivityController::class, 'update'])->name('activities.update');
        Route::delete('/activities/{id}', [ActivityController::class, 'destroy'])->name('activities.destroy');

        // Role & Account Management
        Route::get('/roles', [RoleController::class, 'index'])->name('roles');
        Route::post('/roles', [RoleController::class, 'store'])->name('roles.store');
        Route::match(['put', 'post'], '/roles/{id}', [RoleController::class, 'update'])->name('roles.update');
        Route::delete('/roles/{id}', [RoleController::class, 'destroy'])->name('roles.destroy');

        // Cash Management Mutations & Shortcuts
        Route::post('/cash-management', [CashManagementController::class, 'store'])->name('cash.store');
        Route::get('/cash-management/shortcut/{memberId}', [CashManagementController::class, 'shortcut'])->name('cash.shortcut');
    });

    // Cash Management Portal (Protected at Backend Level)
    Route::get('/cash-management', [CashManagementController::class, 'index'])->name('cash-management');
});
