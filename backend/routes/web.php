<?php

use App\Http\Controllers\Api\V1\SubmissionController;
use Illuminate\Support\Facades\Route;

// The public site lives in the React app (frontend/). The backend only serves
// the API under /api/v1 and the Filament panels.
Route::get('/', fn () => response()->json([
    'name' => config('app.name'),
    'api' => url('/api/v1'),
]))->name('home');

// Submission files for the Filament panels (the React app uses the API route).
// The controller authorizes against SubmissionPolicy; guests get a 403.
Route::get('submissions/{submission}/file', [SubmissionController::class, 'file'])->name('submissions.file');
