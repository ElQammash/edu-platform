<?php

use Illuminate\Support\Facades\Route;

// The public site lives in the React app (frontend/). The backend only serves
// the API under /api/v1 and the Filament panels.
Route::get('/', fn () => response()->json([
    'name' => config('app.name'),
    'api' => url('/api/v1'),
]))->name('home');
