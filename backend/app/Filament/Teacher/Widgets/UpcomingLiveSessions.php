<?php

namespace App\Filament\Teacher\Widgets;

use App\Enums\LessonType;
use App\Filament\Shared\Courses\AttendanceAction;
use App\Models\Lesson;
use Filament\Actions\Action;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget;
use Illuminate\Contracts\Support\Htmlable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;

/**
 * The teacher's next live sessions (and any running now), with a button to
 * start the meeting and the attendance sheet.
 */
class UpcomingLiveSessions extends TableWidget
{
    protected int|string|array $columnSpan = 'full';

    protected static ?int $sort = 1;

    protected function getTableHeading(): string|Htmlable|null
    {
        return __('live.widget.heading');
    }

    public function table(Table $table): Table
    {
        return $table
            ->query(fn (): Builder => Lesson::query()
                ->with('section.course')
                ->withCount(['attendances'])
                ->where('type', LessonType::Live)
                // Sessions at most a few hours old are kept, so one running now still shows.
                ->where('live_at', '>=', now()->subHours(6))
                ->whereHas('section.course', fn (Builder $q) => $q->where('teacher_id', Auth::id())))
            ->defaultSort('live_at')
            ->paginated([5])
            ->columns([
                TextColumn::make('title')
                    ->label(__('live.widget.lesson')),
                TextColumn::make('section.course.title')
                    ->label(__('live.widget.course'))
                    ->limit(30),
                TextColumn::make('live_at')
                    ->label(__('live.widget.starts'))
                    ->dateTime('D j M, H:i')
                    ->description(fn (Lesson $record): string => $record->live_at?->diffForHumans() ?? '')
                    ->badge()
                    ->color(fn (Lesson $record): string => match ($record->liveState()) {
                        Lesson::LIVE_NOW => 'success',
                        Lesson::LIVE_ENDED => 'gray',
                        default => 'info',
                    }),
                TextColumn::make('attendances_count')
                    ->label(__('live.attendance.action')),
            ])
            ->recordActions([
                Action::make('start')
                    ->label(__('live.widget.start'))
                    ->icon(Heroicon::OutlinedVideoCamera)
                    ->url(fn (Lesson $record): ?string => $record->meeting_url, shouldOpenInNewTab: true)
                    ->visible(fn (Lesson $record): bool => $record->liveState() !== Lesson::LIVE_ENDED),
                AttendanceAction::make(),
            ])
            ->emptyStateHeading(__('live.widget.empty'));
    }
}
