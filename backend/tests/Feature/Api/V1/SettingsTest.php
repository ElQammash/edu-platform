<?php

namespace Tests\Feature\Api\V1;

use App\Enums\SettingKey;
use App\Services\SettingsService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SettingsTest extends TestCase
{
    use RefreshDatabase;

    public function test_whatsapp_number_falls_back_to_config(): void
    {
        config(['academy.whatsapp_number' => '201000000000']);

        $this->getJson(route('api.v1.settings'))
            ->assertOk()
            ->assertJsonPath('data.whatsapp_number', '201000000000');
    }

    public function test_saved_whatsapp_number_wins_and_is_normalized(): void
    {
        config(['academy.whatsapp_number' => '201000000000']);
        app(SettingsService::class)->set(SettingKey::WhatsAppNumber, '+20 101 234 5678');

        $this->getJson(route('api.v1.settings'))
            ->assertJsonPath('data.whatsapp_number', '201012345678');
    }

    public function test_whatsapp_number_is_null_when_not_configured(): void
    {
        config(['academy.whatsapp_number' => null]);

        $this->getJson(route('api.v1.settings'))
            ->assertJsonPath('data.whatsapp_number', null);
    }
}
