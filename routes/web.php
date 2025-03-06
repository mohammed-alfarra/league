<?php

use App\Http\Controllers\LeagueController;
use Illuminate\Support\Facades\Route;

// Main page - League simulation
Route::get('/', [LeagueController::class, 'index'])->name('home');
