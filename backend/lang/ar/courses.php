<?php

return [
    'currency' => 'ج.م',
    'course' => [
        'label' => 'كورس',
        'plural_label' => 'الكورسات',
        'sections' => [
            'details' => 'تفاصيل الكورس',
            'publishing' => 'السعر والنشر',
        ],
        'fields' => [
            'title' => 'العنوان',
            'slug' => 'رابط الكورس',
            'description' => 'الوصف',
            'thumbnail' => 'الصورة',
            'teacher' => 'المدرس',
            'price' => 'السعر',
            'language' => 'لغة الشرح',
            'type' => 'النوع',
            'status' => 'الحالة',
            'lessons_count' => 'الدروس',
            'students_count' => 'الطلاب',
            'published_at' => 'تاريخ النشر',
        ],
        'hints' => [
            'slug' => 'بيتعمل تلقائي من العنوان لو سبته فاضي. تغييره بيغير رابط الكورس.',
            'price' => 'بالجنيه المصري. 0 يعني مجاني والطالب يشترك بنفسه. الكورسات المدفوعة الأدمن بيسجل فيها الطلاب.',
        ],
    ],
    'section' => [
        'label' => 'قسم',
        'plural_label' => 'الأقسام',
        'fields' => [
            'title' => 'العنوان',
        ],
    ],
    'lesson' => [
        'label' => 'درس',
        'plural_label' => 'الدروس',
        'fields' => [
            'title' => 'العنوان',
            'type' => 'النوع',
            'video_url' => 'رابط الفيديو',
            'duration' => 'المدة (بالدقائق)',
            'content' => 'المحتوى',
            'is_free_preview' => 'معاينة مجانية',
        ],
        'hints' => [
            'video_url' => 'رابط من Bunny Stream أو YouTube (غير مدرج) أو Vimeo. الفيديوهات مش بتترفع على السيرفر بتاعنا.',
            'duration' => 'اختياري. المشغل بيحسبها لو فاضية.',
            'content' => 'Markdown. نص الدرس للدروس النصية، أو ملاحظات تحت الفيديو.',
            'is_free_preview' => 'أي حد يقدر يشوف الدرس ده من غير اشتراك.',
        ],
    ],
];
