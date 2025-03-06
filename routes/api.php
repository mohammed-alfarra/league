<?php

use App\Http\Controllers\LeagueController;
use Illuminate\Support\Facades\Route;

Route::post('/init-league', [LeagueController::class, 'initialize'])->name('api.init-league');
Route::post('/play-next-week', [LeagueController::class, 'playNextWeek'])->name('api.play-next-week');
Route::post('/play-all-weeks', [LeagueController::class, 'playAllWeeks'])->name('api.play-all-weeks');
Route::post('/update-match', [LeagueController::class, 'updateMatch'])->name('api.update-match');
