<?php

namespace App\Filament\Teacher\Widgets;

use App\Filament\Shared\Qa\QuestionsTable;
use App\Models\LessonComment;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget;
use Illuminate\Contracts\Support\Htmlable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;

/**
 * Student questions still waiting for the teacher's answer, oldest first,
 * answered right from the dashboard.
 */
class UnansweredQuestions extends TableWidget
{
    protected int|string|array $columnSpan = 'full';

    protected static ?int $sort = 2;

    protected function getTableHeading(): string|Htmlable|null
    {
        return __('qa.widget.heading');
    }

    public function table(Table $table): Table
    {
        return $table
            ->query(fn (): Builder => LessonComment::query()
                ->unanswered()
                ->forTeacher((int) Auth::id())
                ->with(['user', 'lesson.section.course', 'replies.user'])
                ->withCount('replies'))
            ->defaultSort('created_at')
            ->paginated([5])
            ->columns(QuestionsTable::columns())
            ->recordActions(QuestionsTable::actions())
            ->emptyStateHeading(__('qa.widget.empty'));
    }
}
