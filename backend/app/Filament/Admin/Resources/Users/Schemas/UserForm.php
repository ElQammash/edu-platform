<?php

namespace App\Filament\Admin\Resources\Users\Schemas;

use App\Enums\Role;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Schema;
use Illuminate\Support\Collection;

class UserForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make(__('users.sections.account'))
                    ->columns(2)
                    ->schema([
                        TextInput::make('name')
                            ->label(__('users.fields.name'))
                            ->required()
                            ->maxLength(255),
                        TextInput::make('email')
                            ->label(__('users.fields.email'))
                            ->email()
                            ->required()
                            ->maxLength(255)
                            ->unique(ignoreRecord: true),
                        TextInput::make('password')
                            ->label(__('users.fields.password'))
                            ->password()
                            ->revealable()
                            ->required(fn (string $operation): bool => $operation === 'create')
                            ->dehydrated(fn (?string $state): bool => filled($state))
                            ->helperText(fn (string $operation) => $operation === 'edit' ? __('users.hints.password') : null),
                        // Saved through spatie/laravel-permission in the Create/Edit pages.
                        Select::make('role')
                            ->label(__('users.fields.role'))
                            ->options(self::roleOptions())
                            ->required()
                            ->live()
                            ->dehydrated(false),
                    ]),

                Section::make(__('users.sections.teacher_profile'))
                    ->relationship('teacherProfile', condition: fn (Get $get): bool => $get('role') === Role::Teacher->value)
                    ->visible(fn (Get $get): bool => $get('role') === Role::Teacher->value)
                    ->columns(2)
                    ->schema([
                        TextInput::make('specialty')
                            ->label(__('users.fields.specialty'))
                            ->maxLength(255),
                        Toggle::make('is_approved')
                            ->label(__('users.fields.is_approved'))
                            ->default(true)
                            ->inline(false),
                        Textarea::make('bio')
                            ->label(__('users.fields.bio'))
                            ->rows(4)
                            ->columnSpanFull(),
                        FileUpload::make('avatar')
                            ->label(__('users.fields.avatar'))
                            ->image()
                            ->avatar()
                            ->disk('public')
                            ->directory('avatars'),
                    ]),

                Section::make(__('users.sections.student_profile'))
                    ->relationship('studentProfile', condition: fn (Get $get): bool => $get('role') === Role::Student->value)
                    ->visible(fn (Get $get): bool => $get('role') === Role::Student->value)
                    ->columns(2)
                    ->schema([
                        TextInput::make('grade_level')
                            ->label(__('users.fields.grade_level'))
                            ->maxLength(50),
                        TextInput::make('parent_phone')
                            ->label(__('users.fields.parent_phone'))
                            ->tel()
                            ->regex('/^01[0125][0-9]{8}$/'),
                    ]),
            ]);
    }

    /**
     * @return array<string, string>
     */
    public static function roleOptions(): array
    {
        return Collection::make(Role::cases())
            ->mapWithKeys(fn (Role $role): array => [$role->value => $role->label()])
            ->all();
    }
}
