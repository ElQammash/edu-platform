<?php

namespace App\Filament\Shared\Courses;

use App\Models\Attendance;
use App\Models\Lesson;
use App\Models\User;
use App\Services\AttendanceService;
use Filament\Actions\Action;
use Filament\Forms\Components\CheckboxList;
use Filament\Support\Icons\Heroicon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

/**
 * The attendance sheet of a live lesson: every enrolled student, ticked
 * when present. Students who joined through the app are already ticked
 * (with their join time); the teacher can correct either way.
 */
class AttendanceAction
{
    public static function make(): Action
    {
        return Action::make('attendance')
            ->label(__('live.attendance.action'))
            ->icon(Heroicon::OutlinedClipboardDocumentCheck)
            ->color('gray')
            ->visible(fn (Lesson $record): bool => $record->isLive())
            ->authorize(fn (Lesson $record): bool => Auth::user()?->can('update', $record) ?? false)
            ->modalHeading(fn (Lesson $record): string => __('live.attendance.heading', ['lesson' => $record->title]))
            ->modalDescription(fn (Lesson $record): string => __('live.attendance.description', [
                'count' => $record->attendances()->count(),
                'total' => count(self::students($record)),
            ]))
            ->fillForm(fn (Lesson $record): array => [
                'present' => $record->attendances()->pluck('student_id')->all(),
            ])
            ->schema(fn (Lesson $record): array => [
                CheckboxList::make('present')
                    ->hiddenLabel()
                    ->options(collect(self::students($record))->mapWithKeys(fn (User $student) => [$student->id => $student->name])->all())
                    ->descriptions(fn (): array => $record->attendances()->get()
                        ->mapWithKeys(fn (Attendance $attendance) => [$attendance->student_id => $attendance->source === Attendance::SOURCE_JOIN
                            ? __('live.attendance.joined_at', ['time' => $attendance->attended_at->format('H:i')])
                            : __('live.attendance.marked_by_teacher')])
                        ->all())
                    ->searchable()
                    ->bulkToggleable()
                    ->columns(2),
            ])
            ->modalSubmitActionLabel(__('live.attendance.save'))
            ->action(function (Lesson $record, array $data): void {
                /** @var User $teacher */
                $teacher = Auth::user();
                $service = app(AttendanceService::class);
                $present = array_map('intval', $data['present'] ?? []);

                DB::transaction(function () use ($record, $present, $teacher, $service) {
                    foreach (self::students($record) as $student) {
                        $service->setPresent($record, $student, in_array($student->id, $present, true), $teacher);
                    }
                });
            })
            ->successNotificationTitle(__('live.attendance.saved'));
    }

    /**
     * @return list<User>
     */
    private static function students(Lesson $lesson): array
    {
        return array_values(User::query()
            ->whereHas('enrollments', fn ($q) => $q->where('course_id', $lesson->course()->id))
            ->orderBy('name')
            ->get()
            ->all());
    }
}
