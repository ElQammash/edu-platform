<?php

namespace App\Console\Commands;

use App\Filament\Teacher\Resources\Questions\QuestionResource;
use App\Models\LessonComment;
use App\Models\User;
use App\Notifications\LessonQaData;
use App\Notifications\UnansweredQuestionsDigest;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Cache;

/**
 * Emails each teacher the student questions still waiting for an answer in
 * their courses. Runs once a day from the scheduler; a teacher with nothing
 * waiting gets no email, and a second run on the same day sends nothing.
 */
#[Signature('qa:send-teacher-digest')]
#[Description('Email teachers a daily digest of unanswered student questions')]
class SendUnansweredQuestionsDigest extends Command
{
    public function handle(): int
    {
        $sent = 0;
        $panelUrl = QuestionResource::getUrl('index', panel: 'teacher');

        $teachers = User::query()
            ->role('teacher')
            ->whereHas('courses.lessons.comments', fn ($q) => $q->unanswered())
            ->get();

        foreach ($teachers as $teacher) {
            $questions = LessonComment::query()
                ->unanswered()
                ->forTeacher($teacher->id)
                ->with(['user', 'lesson.section.course'])
                ->oldest()
                ->get();

            if ($questions->isEmpty() || ! Cache::add("qa-digest:{$teacher->id}:".today()->toDateString(), true, now()->addDays(2))) {
                continue;
            }

            $teacher->notify(new UnansweredQuestionsDigest(
                array_values($questions->take(UnansweredQuestionsDigest::LIMIT)->map(fn (LessonComment $question): array => [
                    'student' => $question->user->name,
                    'lesson' => $question->lesson->title,
                    'course' => $question->lesson->course()->title,
                    'excerpt' => LessonQaData::excerpt($question->body, 100),
                    'asked_at' => (string) $question->created_at?->toIso8601String(),
                ])->all()),
                $questions->count(),
                $panelUrl,
            ));
            $sent++;
        }

        $this->info("Queued {$sent} digest email(s).");

        return self::SUCCESS;
    }
}
