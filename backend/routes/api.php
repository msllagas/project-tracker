<?php

use App\Http\Controllers\Auth\AuthenticatedSessionController;
use App\Http\Controllers\ProjectController;
use App\Http\Middleware\EnsureRequestHasSession;
use Illuminate\Support\Facades\Route;

Route::post('/login', [AuthenticatedSessionController::class, 'store'])
    ->middleware([EnsureRequestHasSession::class, 'throttle:login'])
    ->name('login');

Route::middleware('auth:sanctum')->group(function () {
    Route::get('/user', [AuthenticatedSessionController::class, 'show'])->name('user');
    Route::post('/logout', [AuthenticatedSessionController::class, 'destroy'])
        ->middleware(EnsureRequestHasSession::class)
        ->name('logout');

    Route::apiResource('projects', ProjectController::class);
});
