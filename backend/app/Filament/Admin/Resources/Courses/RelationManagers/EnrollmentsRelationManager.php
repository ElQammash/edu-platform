<?php

namespace App\Filament\Admin\Resources\Courses\RelationManagers;

use App\Enums\Role;
use App\Models\Course;
use App\Models\Enrollment;
use App\Models\User;
use App\Services\EnrollmentService;
use Filament\Actions\CreateAction;
use Filament\Actions\DeleteAction;
use Filament\Forms\Components\Select;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;

/**
 * Students enrolled in the course. Students who pay online are enrolled
 * by their payment (payment_status = paid); the admin enrolls those who
 * paid outside the platform (manual).
 */
class EnrollmentsRelationManager extends RelationManager
{
    protected static string $relationship = 'enrollments';

    public static function getTitle(Model $ownerRecord, string $pageClass): string
    {
        return __('enrollments.admin.title');
    }

    public static function getModelLabel(): string
    {
        return __('enrollments.admin.label');
    }

    public function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Select::make('student_id')
                    ->label(__('enrollments.admin.fields.student'))
                    ->helperText(__('enrollments.admin.hints.student'))
                    ->searchable()
                    ->required()
                    ->getSearchResultsUsing(fn (string $search): array => $this->availableStudents()
                        ->where(fn (Builder $q) => $q->where('name', 'like', "%{$search}%")->orWhere('email', 'like', "%{$search}%"))
                        ->limit(20)
                        ->get()
                        ->mapWithKeys(fn (User $user): array => [$user->id => "{$user->name} ({$user->email})"])
                        ->all())
                    ->getOptionLabelUsing(fn (mixed $value): ?string => is_numeric($value) ? User::find((int) $value)?->name : null),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(fn (Builder $query) => $query->with(['student', 'enrolledBy']))
            ->defaultSort('enrolled_at', 'desc')
            ->columns([
                TextColumn::make('student.name')
                    ->label(__('enrollments.admin.fields.student'))
                    ->searchable(),
                TextColumn::make('student.email')
                    ->label(__('users.fields.email'))
                    ->searchable(),
                TextColumn::make('payment_status')
                    ->label(__('enrollments.admin.fields.payment_status'))
                    ->badge(),
                TextColumn::make('progress_percent')
                    ->label(__('enrollments.admin.fields.progress'))
                    ->suffix('%')
                    ->sortable(),
                TextColumn::make('enrolledBy.name')
                    ->label(__('enrollments.admin.fields.enrolled_by'))
                    ->placeholder('—'),
                TextColumn::make('enrolled_at')
                    ->label(__('enrollments.admin.fields.enrolled_at'))
                    ->dateTime()
                    ->sortable(),
            ])
            ->headerActions([
                CreateAction::make()
                    ->label(__('enrollments.admin.enroll'))
                    ->using(function (array $data): Enrollment {
                        /** @var Course $course */
                        $course = $this->getOwnerRecord();
                        /** @var User $admin */
                        $admin = Auth::user();

                        return app(EnrollmentService::class)
                            ->enrollManually(User::findOrFail((int) $data['student_id']), $course, $admin);
                    }),
            ])
            ->recordActions([
                DeleteAction::make()
                    ->label(__('enrollments.admin.unenroll')),
            ]);
    }

    /**
     * Students who are not enrolled in this course yet.
     *
     * @return Builder<User>
     */
    private function availableStudents(): Builder
    {
        /** @var Course $course */
        $course = $this->getOwnerRecord();

        return User::query()
            ->whereHas('roles', fn (Builder $q) => $q->where('name', Role::Student->value))
            ->whereDoesntHave('enrollments', fn (Builder $q) => $q->where('course_id', $course->id));
    }
}
