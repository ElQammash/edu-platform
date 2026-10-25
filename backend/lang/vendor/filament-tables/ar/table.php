<?php

// Keys missing from Filament's bundled Arabic; Laravel merges these into it.
return [
    'column_manager' => [
        'position' => 'الموضع :position من :count.',
        'position_in_group' => 'الموضع :position من :count في :group.',
        'reordered' => 'تم نقل :column إلى الموضع :position من :count.',
        'reordered_deferred' => 'تم نقل :column إلى الموضع :position من :count. طبّق الأعمدة لحفظ التغييرات.',
        'reordered_in_group' => 'تم نقل :column إلى الموضع :position من :count في :group.',
        'reordered_in_group_deferred' => 'تم نقل :column إلى الموضع :position من :count في :group. طبّق الأعمدة لحفظ التغييرات.',
        'actions' => [
            'move_up' => ['label' => 'نقل :column لأعلى'],
            'move_down' => ['label' => 'نقل :column لأسفل'],
        ],
    ],
    'columns' => [
        'editable' => [
            'saving' => 'جارٍ حفظ التغيير...',
            'saved' => 'تم حفظ التغيير.',
            'failure' => 'تعذر تأكيد الحفظ. أعد تحميل الصفحة قبل المحاولة مرة أخرى.',
            'unavailable' => 'لم يعد بالإمكان تعديل هذا العنصر.',
        ],
    ],
    'actions' => [
        'move_record_up' => ['label' => 'نقل :label لأعلى'],
        'move_record_down' => ['label' => 'نقل :label لأسفل'],
    ],
    'reordering' => [
        'position' => ':label، الموضع :position من :count.',
        'moved' => 'تم نقل :label إلى الموضع :position من :count.',
    ],
];
