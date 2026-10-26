<?php

return [
    'navigation' => 'الأسئلة والأجوبة',
    'label' => 'سؤال',
    'plural_label' => 'الأسئلة',

    'errors' => [
        'edit_window' => 'يمكن تعديل المشاركة خلال :minutes دقيقة فقط من نشرها.',
    ],

    'fields' => [
        'question' => 'السؤال',
        'student' => 'الطالب',
        'lesson' => 'الدرس',
        'course' => 'الكورس',
        'replies' => 'الردود',
        'status' => 'الحالة',
        'asked' => 'وقت السؤال',
        'reply' => 'ردك',
    ],

    'status' => [
        'unanswered' => 'بدون رد',
        'answered' => 'تم الرد',
        'resolved' => 'تم الحل',
    ],

    'actions' => [
        'thread' => 'فتح',
        'reply' => 'رد',
        'send' => 'إرسال الرد',
        'resolve' => 'تحديد كمحلول',
        'reopen' => 'إعادة فتح',
        'delete' => 'حذف',
        'accept' => 'اعتماد كإجابة',
    ],

    'thread' => [
        'teacher' => 'المدرس',
        'accepted' => 'الإجابة المعتمدة',
        'deleted' => 'تم حذف هذه المشاركة.',
        'no_replies' => 'لا توجد ردود بعد.',
    ],

    'replied' => 'تم إرسال الرد',
    'resolved' => 'تم تحديد السؤال كمحلول',
    'reopened' => 'تمت إعادة فتح السؤال',
    'deleted' => 'تم حذف المشاركة',
    'empty' => 'لا توجد أسئلة هنا',

    'widget' => [
        'heading' => 'أسئلة بدون رد',
        'empty' => 'تم الرد على كل الأسئلة.',
    ],

    'notifications' => [
        'question' => 'سؤال جديد من :name في ":lesson"',
        'reply' => 'رد :name في ":lesson"',
        'accepted' => 'تم اعتماد ردك في ":lesson" كإجابة',
    ],

    'mail' => [
        'greeting' => 'أهلاً :name،',
        'view_answer' => 'عرض الإجابة',
        'reply_subject' => 'تم الرد على سؤالك في ":lesson"',
        'reply_line' => 'رد :teacher على سؤالك في ":lesson" (:course):',
        'accepted_subject' => 'سؤالك في ":lesson" له إجابة معتمدة',
        'accepted_line' => 'اعتمد المدرس هذا الرد كإجابة لسؤالك في ":lesson" (:course):',
        'digest_subject' => '{1} سؤال واحد في انتظار ردك|[2,10] :count أسئلة في انتظار ردك|[11,*] :count سؤالاً في انتظار ردك',
        'digest_line' => '{1} يوجد سؤال واحد من الطلاب في كورساتك بدون رد:|[2,*] يوجد :count من أسئلة الطلاب في كورساتك بدون رد:',
        'digest_item' => ':student في ":lesson" (:course)، :ago: «:excerpt»',
        'digest_more' => '…و:count أسئلة أخرى.',
        'digest_action' => 'الرد من لوحة المدرس',
    ],
];
