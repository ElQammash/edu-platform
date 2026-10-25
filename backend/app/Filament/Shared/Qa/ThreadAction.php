<?php

namespace App\Filament\Shared\Qa;

use App\Models\LessonComment;
use App\Models\User;
use App\Services\LessonQaService;
use Filament\Actions\Action;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\Toggle;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Section;
use Filament\Support\Icons\Heroicon;
use Illuminate\Support\Facades\Auth;

/**
 * Open a question in a modal: the question and its replies, a reply box,
 * the accepted answer and the resolved switch, saved in one go.
 */
class ThreadAction
{
    public static function make(): Action
    {
        return Action::make('thread')
            ->label(__('qa.actions.reply'))
            ->icon(Heroicon::OutlinedChatBubbleLeftRight)
            ->authorize(fn (LessonComment $record): bool => Auth::user()?->can('reply', $record) ?? false)
            ->modalHeading(fn (LessonComment $record): string => $record->lesson->title)
            ->modalDescription(fn (LessonComment $record): string => $record->lesson->course()->title)
            ->modalWidth('3xl')
            ->fillForm(fn (LessonComment $record): array => [
                'accepted_reply_id' => $record->accepted_reply_id,
                'resolved' => $record->isResolved(),
            ])
            ->schema(fn (LessonComment $record): array => [
                Section::make($record->user->name)
                    ->description($record->created_at?->diffForHumans())
                    ->compact()
                    ->schema([
                        TextEntry::make('question_body')
                            ->hiddenLabel()
                            ->state($record->body)
                            ->extraAttributes(['dir' => 'auto', 'class' => 'whitespace-pre-line']),
                    ]),
                ...$record->replies->map(fn (LessonComment $reply): Section => Section::make($reply->user->name)
                    ->description($reply->created_at?->diffForHumans())
                    ->icon($reply->id === $record->accepted_reply_id ? Heroicon::OutlinedCheckBadge : null)
                    ->iconColor('success')
                    ->afterHeader($reply->by_staff ? [
                        TextEntry::make("staff_{$reply->id}")->hiddenLabel()->state(__('qa.thread.teacher'))->badge(),
                    ] : [])
                    ->compact()
                    ->schema([
                        TextEntry::make("reply_{$reply->id}")
                            ->hiddenLabel()
                            ->state($reply->body)
                            ->extraAttributes(['dir' => 'auto', 'class' => 'whitespace-pre-line']),
                    ]))->all(),
                Textarea::make('body')
                    ->label(__('qa.fields.reply'))
                    ->rows(4)
                    ->maxLength(LessonComment::MAX_LENGTH),
                Select::make('accepted_reply_id')
                    ->label(__('qa.actions.accept'))
                    ->options($record->replies->mapWithKeys(fn (LessonComment $reply): array => [
                        $reply->id => $reply->user->name.': '.str($reply->body)->limit(60),
                    ])->all())
                    ->visible($record->replies->isNotEmpty()),
                Toggle::make('resolved')
                    ->label(__('qa.status.resolved')),
            ])
            ->modalSubmitActionLabel(__('qa.actions.send'))
            ->action(function (LessonComment $record, array $data): void {
                /** @var User $user */
                $user = Auth::user();
                $qa = app(LessonQaService::class);

                if (filled($data['body'] ?? null)) {
                    $qa->reply($user, $record, trim((string) $data['body']));
                }

                $resolved = (bool) ($data['resolved'] ?? false);
                $accepted = $data['accepted_reply_id'] ?? null;
                if (filled($accepted) && (int) $accepted !== $record->accepted_reply_id) {
                    $reply = $record->replies->firstWhere('id', (int) $accepted);
                    if ($reply instanceof LessonComment) {
                        // Accepting an answer resolves the question.
                        $qa->accept($user, $reply);
                        $resolved = true;
                    }
                }

                $record->refresh();
                if ($resolved && ! $record->isResolved()) {
                    $qa->resolve($record);
                } elseif (! $resolved && $record->isResolved()) {
                    $qa->reopen($record);
                }
            })
            ->successNotificationTitle(__('qa.replied'));
    }
}
