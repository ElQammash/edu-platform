<?php

namespace App\Observers;

use App\Enums\LessonType;
use App\Models\Lesson;
use App\Models\User;
use App\Notifications\LiveLessonChanged;
use App\Notifications\LiveLessonData;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\Notification;

/**
 * Tells enrolled students when an upcoming live lesson is moved or
 * cancelled. Moving it also re-arms its reminders, since they are keyed
 * on the scheduled time.
 */
class LessonObserver
{
    public function updated(Lesson $lesson): void
    {
        $wasLive = $lesson->getOriginal('type') === LessonType::Live;
        /** @var CarbonImmutable|null $previous */
        $previous = $lesson->getOriginal('live_at');
        $wasUpcoming = $wasLive && $previous !== null && $previous->isFuture();

        if (! $wasUpcoming) {
            return;
        }

        if (! $lesson->isLive()) {
            $this->notify($lesson, LiveLessonChanged::CANCELLED, $previous);

            return;
        }

        if ($lesson->wasChanged('live_at') && ! $lesson->live_at?->equalTo($previous)) {
            $this->notify($lesson, LiveLessonChanged::RESCHEDULED, $previous);
        }
    }

    public function deleting(Lesson $lesson): void
    {
        if ($lesson->isLive() && $lesson->live_at?->isFuture()) {
            $this->notify($lesson, LiveLessonChanged::CANCELLED, $lesson->live_at);
        }
    }

    private function notify(Lesson $lesson, string $type, ?CarbonImmutable $previous): void
    {
        $lesson->loadMissing('section.course');
        $students = User::query()
            ->whereHas('enrollments', fn ($q) => $q->where('course_id', $lesson->course()->id))
            ->get();

        if ($students->isEmpty()) {
            return;
        }

        $data = LiveLessonData::of($lesson);
        if ($type === LiveLessonChanged::CANCELLED) {
            // Say when the cancelled session was supposed to be.
            $data['starts_at'] = $previous?->toIso8601String();
        }

        Notification::send($students, new LiveLessonChanged($type, $data, $previous?->toIso8601String()));
    }
}
