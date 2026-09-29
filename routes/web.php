<?php

use App\Http\Controllers\GameController;
use App\Http\Controllers\PageController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Web Routes — BYC GROWTH 2.0
|--------------------------------------------------------------------------
|
| Main website architecture, portal destinations, and interactive games.
|
*/

// Website Architecture & Portals
Route::get('/', [PageController::class, 'home'])->name('home');
Route::get('/about', [PageController::class, 'about'])->name('about');
Route::get('/members', [PageController::class, 'members'])->name('members');
Route::get('/game-center', [GameController::class, 'gameCenter'])->name('game.center');
Route::get('/cash-management', [PageController::class, 'cashManagement'])->name('cash-management');
Route::get('/contact', [PageController::class, 'contact'])->name('contact');

// Interactive Games (Preserved)
Route::get('/guess-me', [GameController::class, 'guessMe'])->name('game.guess-me');
Route::get('/growth-100', [GameController::class, 'growth100'])->name('game.growth-100');
Route::get('/final', [GameController::class, 'finalScore'])->name('game.final');
Route::get('/game/image/{filename}', [GameController::class, 'getImage'])->name('game.image');

// Game State & CRUD Endpoints (Preserved)
Route::prefix('game')->name('game.')->group(function () {
    Route::post('/update-score', [GameController::class, 'updateScore'])->name('update-score');
    Route::post('/reset', [GameController::class, 'resetGame'])->name('reset');

    // Game 1 — Guess Me!
    Route::post('/guess-me/state', [GameController::class, 'updateGame1State'])->name('guess-me.state');
    Route::post('/guess-me/round', [GameController::class, 'saveGuessMeRound'])->name('guess-me.save-round');
    Route::delete('/guess-me/round/{id}', [GameController::class, 'deleteGuessMeRound'])->name('guess-me.delete-round');

    // Game 2 — BYC Growth 100
    Route::post('/growth-100/state', [GameController::class, 'updateGame2State'])->name('growth-100.state');
    Route::post('/growth-100/round', [GameController::class, 'saveGrowth100Round'])->name('growth-100.save-round');
    Route::delete('/growth-100/round/{id}', [GameController::class, 'deleteGrowth100Round'])->name('growth-100.delete-round');
});
