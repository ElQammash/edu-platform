<?php

// Keys missing from Filament's bundled Arabic; Laravel merges these into it.
return [
    'builder' => [
        'reordering' => [
            'item' => 'كتلة',
            'position' => ':label، الموضع :position من :count',
            'moved' => 'تم نقل :label إلى الموضع :position من :count',
        ],
    ],
    'file_upload' => [
        'placeholder' => 'لم يتم رفع أي ملفات',
    ],
    'key_value' => [
        'actions' => [
            'move_down' => ['label' => 'نقل لأسفل'],
            'move_up' => ['label' => 'نقل لأعلى'],
        ],
        'reordering' => [
            'row' => 'صف',
            'position' => ':label، الموضع :position من :count',
            'moved' => 'تم نقل :label إلى الموضع :position من :count',
        ],
    ],
    'repeater' => [
        'reordering' => [
            'item' => 'عنصر',
            'position' => ':label، الموضع :position من :count',
            'moved' => 'تم نقل :label إلى الموضع :position من :count',
        ],
    ],
    'select' => [
        'error_message' => 'تعذر تحميل الخيارات. حاول مرة أخرى.',
        'required_description' => 'اختر أحد الخيارات.',
    ],
];
