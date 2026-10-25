<?php

namespace App\Http\Controllers\Api\V1;

use App\Enums\CourseStatus;
use App\Enums\LessonType;
use App\Http\Controllers\Controller;
use App\Models\Lesson;
use App\Models\User;
use App\Services\AttendanceService;
use Carbon\CarbonImmutable;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Str;

class LiveSessionController extends Controller
{
    /**
     * POST /lessons/{id}/join: the meeting link, only inside the join window.
     * Students' attendance is recorded on the way.
     */
    public function join(Request $request, Lesson $lesson, AttendanceService $attendance): JsonResponse
    {
        $lesson->load('section.course');

        Gate::authorize('join', $lesson);

        /** @var User $user */
        $user = $request->user();

        if ($user->isStudent()) {
            $attendance->recordJoin($user, $lesson);
        }

        return response()->json(['data' => ['meeting_url' => $lesson->meeting_url]]);
    }

    /**
     * GET /me/live-sessions: upcoming and running sessions in the student's courses.
     */
    public function upcoming(Request $request): JsonResponse
    {
        /** @var User $user */
        $user = $request->user();
        $courseIds = $user->enrollments()->pluck('course_id');

        $lessons = Lesson::query()
            ->with('section.course')
            ->where('type', LessonType::Live)
            ->whereNotNull('live_at')
            // Started at most a day ago: anything still running is included.
            ->where('live_at', '>=', now()->subDay())
            ->whereHas('section.course', fn ($q) => $q->whereIn('courses.id', $courseIds)->where('status', CourseStatus::Published))
            ->orderBy('live_at')
            ->get()
            ->filter(fn (Lesson $lesson) => $lesson->liveState() !== Lesson::LIVE_ENDED)
            ->take(20)
            ->values()
            ->map(fn (Lesson $lesson) => [
                'lesson' => ['id' => $lesson->id, 'title' => $lesson->title],
                'course' => ['id' => $lesson->course()->id, 'slug' => $lesson->course()->slug, 'title' => $lesson->course()->title],
                'starts_at' => $lesson->live_at?->toIso8601String(),
                'ends_at' => $lesson->liveEndsAt()?->toIso8601String(),
                'state' => $lesson->liveState(),
                'can_join' => $lesson->canJoinNow(),
                'calendar_url' => route('api.v1.lessons.calendar', $lesson),
            ]);

        return response()->json(['data' => $lessons]);
    }

    /**
     * GET /lessons/{id}/calendar.ics: "add to calendar". Public for published
     * courses; carries a link to the lesson page, never the meeting link.
     */
    public function calendar(Lesson $lesson): Response
    {
        $lesson->load('section.course');
        $course = $lesson->course();

        abort_unless($lesson->isLive() && $course->isPublished(), 404);

        $format = fn (CarbonImmutable $date) => $date->utc()->format('Ymd\THis\Z');
        $escape = fn (string $text) => addcslashes(str_replace(["\r\n", "\n"], '\n', $text), ',;\\');
        $url = rtrim((string) config('app.frontend_url'), '/').'/lessons/'.$lesson->id;

        $ics = implode("\r\n", [
            'BEGIN:VCALENDAR',
            'VERSION:2.0',
            'PRODID:-//Edu Platform//Live lessons//EN',
            'CALSCALE:GREGORIAN',
            'METHOD:PUBLISH',
            'BEGIN:VEVENT',
            'UID:lesson-'.$lesson->id.'-'.$lesson->live_at?->timestamp.'@'.parse_url((string) config('app.url'), PHP_URL_HOST),
            'DTSTAMP:'.$format(now()),
            'DTSTART:'.$format($lesson->live_at ?? now()),
            'DTEND:'.$format($lesson->liveEndsAt() ?? now()),
            'SUMMARY:'.$escape($lesson->title.' - '.$course->title),
            'DESCRIPTION:'.$escape(__('live.calendar_description', ['url' => $url])),
            'URL:'.$url,
            'BEGIN:VALARM',
            'TRIGGER:-PT15M',
            'ACTION:DISPLAY',
            'DESCRIPTION:'.$escape($lesson->title),
            'END:VALARM',
            'END:VEVENT',
            'END:VCALENDAR',
        ])."\r\n";

        return response($ics, 200, [
            'Content-Type' => 'text/calendar; charset=utf-8',
            'Content-Disposition' => 'attachment; filename="'.Str::slug($lesson->title ?: 'lesson').'.ics"',
        ]);
    }
}
