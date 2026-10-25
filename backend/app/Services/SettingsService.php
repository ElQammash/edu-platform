<?php

namespace App\Services;

use App\Enums\SettingKey;
use App\Models\Setting;
use Illuminate\Support\Facades\Cache;

/**
 * Academy settings saved by the admin, falling back to config/.env.
 * Values are cached until they change.
 */
class SettingsService
{
    private const CACHE_KEY = 'academy-settings';

    public function get(SettingKey $key): ?string
    {
        return $this->all()[$key->value] ?? $key->default();
    }

    public function set(SettingKey $key, ?string $value): void
    {
        Setting::updateOrCreate(['key' => $key->value], ['value' => $value === '' ? null : $value]);

        Cache::forget(self::CACHE_KEY);
    }

    /**
     * WhatsApp number in wa.me format (digits only), or null when not configured.
     */
    public function whatsAppNumber(): ?string
    {
        $digits = preg_replace('/\D+/', '', (string) $this->get(SettingKey::WhatsAppNumber));

        return $digits !== '' ? $digits : null;
    }

    /**
     * @return array<string, string|null>
     */
    private function all(): array
    {
        /** @var array<string, string|null> */
        return Cache::rememberForever(self::CACHE_KEY, fn () => Setting::query()
            ->whereNotNull('value')
            ->pluck('value', 'key')
            ->all());
    }
}
