<?php

use Illuminate\Http\Request;
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
    Route::get('/user', fn (Request $request) => $request->user())
        ->middleware('auth:sanctum')
        ->name('user');
});
