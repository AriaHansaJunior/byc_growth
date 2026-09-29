<?php

use App\Http\Controllers\Admin\AdminDashboardController;
use App\Http\Controllers\Auth\AdminAuthController;
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
Route::get('/members', [PageController::class, 'members'])->name('members');
Route::get('/game-center', [GameController::class, 'gameCenter'])->name('game.center');
Route::get('/contact', [PageController::class, 'contact'])->name('contact');

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
    });

    // Cash Management Portal (Protected at Backend Level)
    Route::get('/cash-management', [PageController::class, 'cashManagement'])->name('cash-management');
});
