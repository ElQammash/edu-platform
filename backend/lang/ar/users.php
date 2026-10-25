<?php

return [
    'label' => 'مستخدم',
    'plural_label' => 'المستخدمون',
    'sections' => [
        'account' => 'الحساب',
        'teacher_profile' => 'ملف المدرس',
        'student_profile' => 'ملف الطالب',
    ],
    'fields' => [
        'name' => 'الاسم',
        'email' => 'البريد الإلكتروني',
        'password' => 'كلمة المرور',
        'role' => 'الدور',
        'specialty' => 'التخصص',
        'is_approved' => 'معتمد',
        'bio' => 'نبذة',
        'avatar' => 'الصورة الشخصية',
        'grade_level' => 'المرحلة الدراسية',
        'parent_phone' => 'هاتف ولي الأمر',
        'created_at' => 'تاريخ الانضمام',
    ],
    'hints' => [
        'password' => 'اتركها فارغة للإبقاء على كلمة المرور الحالية.',
    ],
];
