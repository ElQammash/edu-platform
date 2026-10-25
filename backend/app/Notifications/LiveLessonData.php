<?php

namespace App\Notifications;

use App\Models\Lesson;
use Carbon\CarbonImmutable;

/**
 * The plain data live-lesson notifications store and email, taken as a
 * snapshot so it survives the lesson being changed or deleted.
 */
final class LiveLessonData
{
    /**
     * @return array{lesson_id: int, lesson_title: string, course_id: int, course_slug: string, course_title: string, starts_at: string|null, url: string}
     */
    public static function of(Lesson $lesson): array
    {
        $course = $lesson->course();

        return [
            'lesson_id' => $lesson->id,
            'lesson_title' => $lesson->title,
            'course_id' => $course->id,
            'course_slug' => $course->slug,
            'course_title' => $course->title,
            'starts_at' => $lesson->live_at?->toIso8601String(),
            'url' => rtrim((string) config('app.frontend_url'), '/').'/lessons/'.$lesson->id,
        ];
    }

    /**
     * "Monday 27 October, 18:00 (Cairo time)" in the current locale.
     */
    public static function formatTime(?CarbonImmutable $time): string
    {
        if ($time === null) {
            return '';
        }

        // Laravel keeps Carbon's locale in step with the app locale, which is the
        // recipient's language while a notification is rendered.
        return $time->setTimezone((string) config('app.timezone'))
            ->translatedFormat((string) __('live.mail.time_format')).' ('.__('live.mail.timezone').')';
    }
}
