<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Academy defaults
    |--------------------------------------------------------------------------
    |
    | Fallbacks for the settings the admin edits in the panel (Settings page).
    | A value saved in the panel always wins over these.
    |
    */

    // International format without "+", e.g. 201012345678.
    'whatsapp_number' => env('ACADEMY_WHATSAPP_NUMBER'),

    // Meeting services allowed for live lessons (https only; subdomains included).
    'meeting_hosts' => ['zoom.us', 'meet.google.com', 'teams.microsoft.com', 'teams.live.com'],

    // Assignment submission files: one per submission, stored privately.
    'submissions' => [
        'max_file_kb' => (int) env('SUBMISSION_MAX_FILE_KB', 10240),
        'mimes' => ['pdf', 'jpg', 'jpeg', 'png', 'webp'],
    ],

];
