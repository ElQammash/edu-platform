<?php

use App\Http\Controllers\Api\V1\AuthController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API v1
|--------------------------------------------------------------------------
|
| All endpoints for the React app (and a future mobile app) are versioned
| under /api/v1. A breaking change gets a new prefix instead of changing v1.
|
*/

Route::prefix('v1')->name('api.v1.')->group(function () {
    Route::prefix('auth')->name('auth.')->group(function () {
        Route::middleware('throttle:6,1')->group(function () {
            Route::post('register', [AuthController::class, 'register'])->name('register');
            Route::post('login', [AuthController::class, 'login'])->name('login');
        });

        Route::middleware('auth:sanctum')->group(function () {
            Route::post('logout', [AuthController::class, 'logout'])->name('logout');
            Route::get('me', [AuthController::class, 'me'])->name('me');
        });
    });
});
