<?php

namespace App\Filament\Teacher\Pages;

use App\Models\User;
use Filament\Auth\Pages\EditProfile;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use SensitiveParameter;

/**
 * /teacher/profile: the teacher's own account and public profile (bio,
 * specialty, photo), shown on their teacher page and course pages.
 * The email is managed by the admin.
 */
class EditTeacherProfile extends EditProfile
{
    private const PROFILE_FIELDS = ['bio', 'specialty', 'avatar'];

    public static function isSimple(): bool
    {
        return false;
    }

    public function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make(__('profile.account'))
                    ->schema([
                        $this->getNameFormComponent(),
                        TextInput::make('email')
                            ->label(__('filament-panels::auth/pages/edit-profile.form.email.label'))
                            ->email()
                            ->disabled()
                            ->dehydrated(false)
                            ->helperText(__('profile.email_managed_by_admin')),
                        $this->getPasswordFormComponent(),
                        $this->getPasswordConfirmationFormComponent(),
                        $this->getCurrentPasswordFormComponent(),
                    ]),
                Section::make(__('profile.public_profile'))
                    ->description(__('profile.public_profile_hint'))
                    ->schema([
                        TextInput::make('specialty')
                            ->label(__('users.fields.specialty'))
                            ->maxLength(255),
                        Textarea::make('bio')
                            ->label(__('users.fields.bio'))
                            ->rows(5)
                            ->maxLength(2000),
                        FileUpload::make('avatar')
                            ->label(__('users.fields.avatar'))
                            ->image()
                            ->avatar()
                            ->imageEditor()
                            ->maxSize(2048)
                            ->disk('public')
                            ->directory('avatars'),
                    ]),
            ]);
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    protected function mutateFormDataBeforeFill(array $data): array
    {
        /** @var User $user */
        $user = $this->getUser();
        $profile = $user->teacherProfile;

        return [
            ...$data,
            'specialty' => $profile?->specialty,
            'bio' => $profile?->bio,
            'avatar' => $profile?->avatar,
        ];
    }

    /**
     * @param  array<string, mixed>  $data
     */
    protected function handleRecordUpdate(Model $record, #[SensitiveParameter] array $data): Model
    {
        /** @var User $record */
        $profileData = Arr::only($data, self::PROFILE_FIELDS);
        $oldAvatar = $record->teacherProfile?->avatar;

        DB::transaction(function () use ($record, $data, $profileData) {
            parent::handleRecordUpdate($record, Arr::except($data, self::PROFILE_FIELDS));
            $record->teacherProfile()->updateOrCreate([], $profileData);
        });

        if ($oldAvatar && $oldAvatar !== ($profileData['avatar'] ?? null)) {
            Storage::disk('public')->delete($oldAvatar);
        }

        return $record;
    }
}
