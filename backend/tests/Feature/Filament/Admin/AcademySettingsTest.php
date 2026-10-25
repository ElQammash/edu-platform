<?php

namespace Tests\Feature\Filament\Admin;

use App\Enums\SettingKey;
use App\Filament\Admin\Pages\AcademySettings;
use App\Models\User;
use App\Services\SettingsService;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class AcademySettingsTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Filament::setCurrentPanel('admin');
    }

    public function test_admin_saves_the_whatsapp_number_digits_only(): void
    {
        $this->actingAs(User::factory()->admin()->create());

        Livewire::test(AcademySettings::class)
            ->fillForm(['whatsapp_number' => '+20 101 234 5678'])
            ->call('save')
            ->assertHasNoFormErrors();

        $this->assertSame('201012345678', app(SettingsService::class)->get(SettingKey::WhatsAppNumber));
    }

    public function test_invalid_numbers_are_rejected(): void
    {
        $this->actingAs(User::factory()->admin()->create());

        Livewire::test(AcademySettings::class)
            ->fillForm(['whatsapp_number' => 'call me'])
            ->call('save')
            ->assertHasFormErrors(['whatsapp_number']);
    }

    public function test_teachers_cannot_open_the_settings_page(): void
    {
        $this->actingAs(User::factory()->teacher()->create())
            ->get(AcademySettings::getUrl())
            ->assertForbidden();
    }
}
