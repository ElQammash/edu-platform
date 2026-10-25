<?php

namespace App\Notifications;

use App\Models\LessonComment;
use Illuminate\Support\Str;

/**
 * The plain data Q&A notifications store and email: a snapshot, so the
 * notification still reads well if the post is edited or deleted.
 */
final class LessonQaData
{
    /**
     * @return array{comment_id: int, question_id: int, lesson_id: int, lesson_title: string, course_slug: string, course_title: string, author_name: string, excerpt: string, url: string}
     */
    public static function of(LessonComment $comment): array
    {
        $lesson = $comment->lesson;
        $course = $lesson->course();
        $questionId = $comment->parent_id ?? $comment->id;

        return [
            'comment_id' => $comment->id,
            'question_id' => $questionId,
            'lesson_id' => $lesson->id,
            'lesson_title' => $lesson->title,
            'course_slug' => $course->slug,
            'course_title' => $course->title,
            'author_name' => $comment->user->name,
            'excerpt' => self::excerpt($comment->body),
            'url' => rtrim((string) config('app.frontend_url'), '/').'/lessons/'.$lesson->id.'#question-'.$questionId,
        ];
    }

    public static function excerpt(string $body, int $limit = 140): string
    {
        return Str::limit(trim((string) preg_replace('/\s+/u', ' ', $body)), $limit);
    }
}
