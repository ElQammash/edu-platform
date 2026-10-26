<?php

namespace App\Notifications;

use App\Filament\Admin\Resources\Questions\QuestionResource as AdminQuestionResource;
use App\Filament\Teacher\Resources\Questions\QuestionResource as TeacherQuestionResource;
use App\Models\LessonComment;
use App\Models\User;
use Filament\Actions\Action;
use Filament\Notifications\Notification as FilamentNotification;
use Filament\Support\Icons\Heroicon;
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

    /**
     * Staff read their notifications in the Filament panels, which list only
     * Filament-formatted ones: a title in the recipient's language (the
     * notification is rendered in it), the excerpt, and a link to the inbox.
     *
     * @return array<string, mixed>
     */
    public static function forPanel(User $notifiable, string $title, LessonComment $comment): array
    {
        $panel = $notifiable->isAdmin() ? 'admin' : 'teacher';
        $inbox = $panel === 'admin' ? AdminQuestionResource::class : TeacherQuestionResource::class;

        return FilamentNotification::make()
            ->title($title)
            ->body(self::excerpt($comment->body))
            ->icon(Heroicon::OutlinedChatBubbleLeftRight)
            ->actions([
                Action::make('open')
                    ->label(__('qa.actions.thread'))
                    ->url($inbox::getUrl('index', panel: $panel))
                    ->markAsRead(),
            ])
            ->getDatabaseMessage();
    }

    /**
     * Students read theirs in the React app, which builds the text itself.
     */
    public static function isForPanel(User $notifiable): bool
    {
        return $notifiable->isAdmin() || $notifiable->isTeacher();
    }

    public static function excerpt(string $body, int $limit = 140): string
    {
        return Str::limit(trim((string) preg_replace('/\s+/u', ' ', $body)), $limit);
    }
}
