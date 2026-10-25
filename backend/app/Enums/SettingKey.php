<?php

namespace App\Enums;

/**
 * Keys of the admin-editable academy settings, with their .env fallback.
 */
enum SettingKey: string
{
    /** International format without "+", e.g. 201012345678 (used for wa.me links). */
    case WhatsAppNumber = 'whatsapp_number';

    public function default(): ?string
    {
        $value = match ($this) {
            self::WhatsAppNumber => config('academy.whatsapp_number'),
        };

        return is_string($value) && $value !== '' ? $value : null;
    }
}
