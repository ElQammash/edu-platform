<?php

return [
    'course_type' => [
        'recorded' => 'مسجل',
        'live' => 'لايف',
        'hybrid' => 'مسجل + لايف',
    ],
    'course_status' => [
        'draft' => 'مسودة',
        'published' => 'منشور',
    ],
    'lesson_type' => [
        'video' => 'فيديو',
        'live' => 'حصة لايف',
        'text' => 'نص',
    ],
    'language' => [
        'ar' => 'العربية',
        'en' => 'الإنجليزية',
    ],
    'question_type' => [
        'mcq' => 'اختيار من متعدد',
        'true_false' => 'صح / خطأ',
        'essay' => 'سؤال مقالي',
    ],
    'show_answers' => [
        'after_grading' => 'بعد التصحيح على طول',
        'after_due_date' => 'بعد آخر ميعاد للاختبار',
        'never' => 'أبداً (الدرجة بس)',
    ],
    'payment_status' => [
        'free' => 'مجاني',
        'manual' => 'مدفوع (يدوي)',
        'paid' => 'مدفوع أونلاين',
    ],

    'payment_state' => [
        'pending' => 'قيد الانتظار',
        'paid' => 'مدفوع',
        'failed' => 'فشل',
        'expired' => 'منتهي',
        'partially_refunded' => 'مسترد جزئياً',
        'refunded' => 'مسترد',
    ],
];
