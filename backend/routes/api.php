<?php

use App\Http\Controllers\Api\V1\AssignmentController;
use App\Http\Controllers\Api\V1\AuthController;
use App\Http\Controllers\Api\V1\CourseController;
use App\Http\Controllers\Api\V1\EnrollmentController;
use App\Http\Controllers\Api\V1\GradesController;
use App\Http\Controllers\Api\V1\LessonController;
use App\Http\Controllers\Api\V1\LessonProgressController;
use App\Http\Controllers\Api\V1\LiveSessionController;
use App\Http\Controllers\Api\V1\LocaleController;
use App\Http\Controllers\Api\V1\NotificationController;
use App\Http\Controllers\Api\V1\ProfileController;
use App\Http\Controllers\Api\V1\QuizAttemptController;
use App\Http\Controllers\Api\V1\QuizController;
use App\Http\Controllers\Api\V1\SettingsController;
use App\Http\Controllers\Api\V1\SubmissionController;
use App\Http\Controllers\Api\V1\TeacherController;
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

    Route::get('settings', [SettingsController::class, 'show'])->name('settings');

    // Public catalog
    Route::get('courses', [CourseController::class, 'index'])->name('courses.index');
    Route::get('courses/{course}', [CourseController::class, 'show'])->name('courses.show');
    Route::get('teachers/{teacher}', [TeacherController::class, 'show'])->whereNumber('teacher')->name('teachers.show');

    // Open to guests for free previews; LessonPolicy decides the rest.
    Route::get('lessons/{lesson}', [LessonController::class, 'show'])->name('lessons.show');
    Route::get('lessons/{lesson}/calendar.ics', [LiveSessionController::class, 'calendar'])->name('lessons.calendar');

    Route::middleware('auth:sanctum')->group(function () {
        Route::post('courses/{course}/enroll', [EnrollmentController::class, 'store'])->name('courses.enroll');
        Route::get('me/courses', [EnrollmentController::class, 'index'])->name('me.courses');
        Route::put('me/locale', [LocaleController::class, 'update'])->name('me.locale');
        Route::get('me/profile', [ProfileController::class, 'show'])->name('me.profile');
        Route::post('me/profile', [ProfileController::class, 'update'])->name('me.profile.update');

        Route::get('assignments/{assignment}', [AssignmentController::class, 'show'])->name('assignments.show');
        Route::post('assignments/{assignment}/submission', [SubmissionController::class, 'store'])
            ->middleware('throttle:20,1')
            ->name('assignments.submit');
        Route::get('submissions/{submission}/file', [SubmissionController::class, 'file'])->name('submissions.file');

        Route::get('quizzes/{quiz}', [QuizController::class, 'show'])->name('quizzes.show');
        Route::post('quizzes/{quiz}/attempts', [QuizController::class, 'start'])
            ->middleware('throttle:20,1')
            ->name('quizzes.start');
        Route::get('attempts/{attempt}', [QuizAttemptController::class, 'show'])->name('attempts.show');
        // Autosave is frequent; submit is once.
        Route::put('attempts/{attempt}/answers', [QuizAttemptController::class, 'saveAnswers'])
            ->middleware('throttle:120,1')
            ->name('attempts.answers');
        Route::post('attempts/{attempt}/submit', [QuizAttemptController::class, 'submit'])->name('attempts.submit');

        Route::get('me/grades', [GradesController::class, 'index'])->name('me.grades');

        Route::post('lessons/{lesson}/join', [LiveSessionController::class, 'join'])
            ->middleware('throttle:30,1')
            ->name('lessons.join');
        Route::get('me/live-sessions', [LiveSessionController::class, 'upcoming'])->name('me.live-sessions');

        Route::get('me/notifications', [NotificationController::class, 'index'])->name('me.notifications');
        Route::post('me/notifications/read-all', [NotificationController::class, 'markAllRead'])->name('me.notifications.read-all');
        Route::post('me/notifications/{notification}/read', [NotificationController::class, 'markRead'])->name('me.notifications.read');

        Route::middleware('throttle:60,1')->group(function () {
            Route::post('lessons/{lesson}/progress', [LessonProgressController::class, 'store'])->name('lessons.progress');
            Route::post('lessons/{lesson}/complete', [LessonProgressController::class, 'complete'])->name('lessons.complete');
        });
    });
});
