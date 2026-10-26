<?php

return [
    'navigation' => 'المدفوعات',
    'currency' => 'ج.م',
    'label' => 'عملية دفع',
    'plural_label' => 'المدفوعات',

    'errors' => [
        'students_only' => 'شراء الكورسات متاح للطلاب فقط.',
        'free' => 'هذا الكورس مجاني: اشترك مباشرة.',
        'already_enrolled' => 'أنت مشترك بالفعل في هذا الكورس.',
        'gateway_unavailable' => 'خدمة الدفع لا تستجيب حالياً. حاول مرة أخرى بعد دقائق.',
    ],

    'fields' => [
        'reference' => 'المرجع',
        'student' => 'الطالب',
        'course' => 'الكورس',
        'amount' => 'المبلغ',
        'refunded_amount' => 'المسترد',
        'net' => 'الصافي',
        'status' => 'الحالة',
        'method' => 'وسيلة الدفع',
        'gateway' => 'بوابة الدفع',
        'gateway_order_id' => 'رقم الطلب في البوابة',
        'gateway_transaction_id' => 'رقم العملية في البوابة',
        'created_at' => 'البدء',
        'paid_at' => 'الدفع',
        'refunded_at' => 'وقت الاسترداد',
        'events' => 'رسائل بوابة الدفع',
        'event_type' => 'النوع',
        'event_source' => 'المصدر',
        'event_payload' => 'البيانات',
        'from' => 'من',
        'until' => 'إلى',
    ],

    'event_types' => [
        'payment' => 'دفع',
        'pending' => 'قيد الانتظار',
        'failed' => 'فشل',
        'refund' => 'استرداد',
        'void' => 'إلغاء',
        'rejected' => 'مرفوضة (عدم تطابق)',
        'failed_refund' => 'فشل الاسترداد',
        'failed_void' => 'فشل الإلغاء',
    ],

    'widget' => [
        'this_month' => 'إيرادات هذا الشهر',
        'last_month' => 'إيرادات الشهر الماضي',
        'paid_enrollments' => 'اشتراكات مدفوعة هذا الشهر',
        'net_hint' => 'بعد خصم المسترد',
        'pending' => ':count قيد الانتظار',
    ],

    'mail' => [
        'greeting' => 'أهلاً :name،',
        'paid_subject' => 'تم استلام الدفع: :course',
        'paid_line' => 'استلمنا دفعتك بقيمة :amount لكورس ":course"، وتم اشتراكك فيه.',
        'reference' => 'المرجع: :reference',
        'start' => 'ابدأ الكورس',
        'refunded_subject' => 'استرداد مبلغ كورس :course',
        'refunded_line' => 'تم استرداد :amount لكورس ":course"، وانتهى وصولك للكورس.',
        'partially_refunded_line' => 'تم استرداد :amount لكورس ":course"، وما زال الكورس متاحاً لك.',
        'view_payments' => 'مدفوعاتي',
    ],

    'fake' => [
        'title' => 'صفحة دفع تجريبية',
        'notice' => 'دفع تجريبي محلي: لا توجد أموال حقيقية. هذه الصفحة لا تعمل أبداً في بيئة الإنتاج.',
        'pay' => 'ادفع :amount',
        'fail' => 'محاكاة رفض البطاقة',
        'sending' => 'جارٍ الإرسال…',
    ],
];
