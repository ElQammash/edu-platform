<?php

namespace App\Filament\Admin\Pages;

use App\Enums\SettingKey;
use App\Services\SettingsService;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Schemas\Components\Actions;
use Filament\Schemas\Components\EmbeddedSchema;
use Filament\Schemas\Components\Form;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;

/**
 * Academy-wide settings, such as the WhatsApp number behind the
 * "contact us" button on paid courses.
 *
 * @property-read Schema $form
 */
class AcademySettings extends Page
{
    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedCog6Tooth;

    protected static ?int $navigationSort = 100;

    /** @var array<string, mixed>|null */
    public ?array $data = [];

    public static function getNavigationLabel(): string
    {
        return __('settings.title');
    }

    public function getTitle(): string
    {
        return __('settings.title');
    }

    public function mount(SettingsService $settings): void
    {
        $this->form->fill([
            'whatsapp_number' => $settings->get(SettingKey::WhatsAppNumber),
        ]);
    }

    public function form(Schema $schema): Schema
    {
        return $schema
            ->statePath('data')
            ->components([
                Section::make(__('settings.sections.contact'))
                    ->schema([
                        TextInput::make('whatsapp_number')
                            ->label(__('settings.fields.whatsapp_number'))
                            ->helperText(__('settings.hints.whatsapp_number'))
                            ->tel()
                            ->prefix('+')
                            ->placeholder('201012345678')
                            ->regex('/^\+?[0-9 ]{10,20}$/')
                            ->dehydrateStateUsing(fn (?string $state): ?string => $state ? preg_replace('/\D+/', '', $state) : null),
                    ]),
            ]);
    }

    public function content(Schema $schema): Schema
    {
        return $schema->components([
            Form::make([EmbeddedSchema::make('form')])
                ->id('form')
                ->livewireSubmitHandler('save')
                ->footer([
                    Actions::make([
                        Action::make('save')
                            ->label(__('settings.save'))
                            ->submit('save'),
                    ]),
                ]),
        ]);
    }

    public function save(SettingsService $settings): void
    {
        /** @var array{whatsapp_number?: string|null} $data */
        $data = $this->form->getState();

        $settings->set(SettingKey::WhatsAppNumber, $data['whatsapp_number'] ?? null);

        Notification::make()
            ->success()
            ->title(__('settings.saved'))
            ->send();
    }
}
