<?php

return [
    'not_a_student' => 'Only students can be enrolled in a course.',
    'enrolled' => 'You are now enrolled in this course.',
    'paid_course' => 'This is a paid course. Contact us to enroll.',
    'admin' => [
        'title' => 'Enrolled students',
        'label' => 'Enrollment',
        'enroll' => 'Enroll a student',
        'unenroll' => 'Unenroll',
        'fields' => [
            'student' => 'Student',
            'payment_status' => 'Payment',
            'progress' => 'Progress',
            'enrolled_by' => 'Enrolled by',
            'enrolled_at' => 'Enrolled at',
        ],
        'hints' => [
            'student' => 'Search by name or email. For paid courses, enroll the student after they pay (cash, Vodafone Cash...).',
        ],
    ],
];
