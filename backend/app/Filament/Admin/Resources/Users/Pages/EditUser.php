<?php

namespace App\Filament\Admin\Resources\Users\Pages;

use App\Filament\Admin\Resources\Users\UserResource;
use App\Models\User;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Support\Facades\Auth;

class EditUser extends EditRecord
{
    protected static string $resource = UserResource::class;

    protected function getHeaderActions(): array
    {
        return [
            // Admins cannot delete their own account and lock themselves out.
            DeleteAction::make()
                ->hidden(fn (User $record): bool => $record->is(Auth::user())),
        ];
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    protected function mutateFormDataBeforeFill(array $data): array
    {
        /** @var User $user */
        $user = $this->record;

        $data['role'] = $user->getRoleNames()->first();

        return $data;
    }

    protected function afterSave(): void
    {
        /** @var User $user */
        $user = $this->record;

        $user->syncRoles([$this->data['role']]);
    }
}
