<?php

namespace App\Filament\Admin\Resources\Users\Tables;

use App\Enums\Role;
use App\Filament\Admin\Resources\Users\Schemas\UserForm;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class UsersTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(fn (Builder $query) => $query->with('roles'))
            ->columns([
                TextColumn::make('name')
                    ->label(__('users.fields.name'))
                    ->searchable()
                    ->sortable(),
                TextColumn::make('email')
                    ->label(__('users.fields.email'))
                    ->searchable(),
                TextColumn::make('roles.name')
                    ->label(__('users.fields.role'))
                    ->badge()
                    ->formatStateUsing(fn (string $state): string => Role::from($state)->label())
                    ->color(fn (string $state): string => match (Role::from($state)) {
                        Role::Admin => 'danger',
                        Role::Teacher => 'warning',
                        Role::Student => 'info',
                    }),
                TextColumn::make('created_at')
                    ->label(__('users.fields.created_at'))
                    ->dateTime()
                    ->sortable(),
            ])
            ->defaultSort('created_at', 'desc')
            ->filters([
                SelectFilter::make('role')
                    ->label(__('users.fields.role'))
                    ->options(UserForm::roleOptions())
                    ->query(fn (Builder $query, array $data): Builder => filled($data['value'])
                        ? $query->whereHas('roles', fn (Builder $roles) => $roles->where('name', $data['value']))
                        : $query),
            ])
            ->recordActions([
                EditAction::make(),
            ]);
    }
}
