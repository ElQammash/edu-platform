<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Services\SettingsService;
use Illuminate\Http\JsonResponse;

class SettingsController extends Controller
{
    /**
     * Public academy settings the React app needs (e.g. the WhatsApp
     * number behind "contact us" on paid courses).
     */
    public function show(SettingsService $settings): JsonResponse
    {
        return response()->json([
            'data' => [
                'whatsapp_number' => $settings->whatsAppNumber(),
            ],
        ]);
    }
}
