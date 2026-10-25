<?php

namespace App\Filament\Shared\Grading;

use App\Models\Submission;
use App\Models\User;
use App\Services\GradingService;
use Filament\Actions\Action;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Section;
use Filament\Support\Icons\Heroicon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Number;

/**
 * Grade (or re-grade) a submission in a modal: shows the student's answer
 * and file, takes a score and feedback, and notifies the student.
 */
class GradeAction
{
    public static function make(): Action
    {
        return Action::make('grade')
            ->label(fn (Submission $record): string => $record->isGraded() ? __('grading.regrade') : __('grading.grade'))
            ->icon(Heroicon::OutlinedPencilSquare)
            ->color(fn (Submission $record): string => $record->isGraded() ? 'gray' : 'primary')
            ->authorize(fn (Submission $record): bool => Auth::user()?->can('grade', $record) ?? false)
            ->modalHeading(fn (Submission $record): string => __('grading.modal_heading', [
                'student' => $record->student->name,
                'assignment' => $record->assignment->title,
            ]))
            ->modalWidth('3xl')
            ->fillForm(fn (Submission $record): array => [
                'score' => $record->score,
                'feedback' => $record->feedback,
            ])
            ->schema(fn (Submission $record): array => [
                Section::make(__('grading.answer'))
                    ->compact()
                    ->schema([
                        TextEntry::make('late')
                            ->hiddenLabel()
                            ->state(__('grading.late_notice', ['due' => $record->assignment->due_at?->translatedFormat('j M Y, H:i')]))
                            ->badge()
                            ->color('danger')
                            ->visible($record->isLate()),
                        TextEntry::make('content')
                            ->hiddenLabel()
                            ->state($record->content ?? __('grading.no_text'))
                            ->extraAttributes(['dir' => 'auto', 'class' => 'whitespace-pre-line']),
                        TextEntry::make('file')
                            ->label(__('grading.file'))
                            ->state($record->hasFile()
                                ? $record->file_name.' ('.Number::fileSize((int) $record->file_size).')'
                                : null)
                            ->url($record->hasFile() ? route('submissions.file', $record) : null, shouldOpenInNewTab: true)
                            ->color('primary')
                            ->icon(Heroicon::OutlinedPaperClip)
                            ->visible($record->hasFile()),
                    ]),
                TextInput::make('score')
                    ->label(__('grading.score'))
                    ->numeric()
                    ->minValue(0)
                    ->maxValue($record->assignment->max_score)
                    ->step(0.25)
                    ->suffix('/ '.$record->assignment->max_score)
                    ->required(),
                Textarea::make('feedback')
                    ->label(__('grading.feedback'))
                    ->rows(4)
                    ->maxLength(5000),
            ])
            ->modalSubmitActionLabel(__('grading.save'))
            ->action(function (Submission $record, array $data): void {
                /** @var User $grader */
                $grader = Auth::user();

                app(GradingService::class)->grade($record, $grader, (float) $data['score'], $data['feedback'] ?? null);
            })
            ->successNotificationTitle(__('grading.saved'));
    }
}
