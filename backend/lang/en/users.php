<?php

return [
    'label' => 'User',
    'plural_label' => 'Users',
    'sections' => [
        'account' => 'Account',
        'teacher_profile' => 'Teacher profile',
        'student_profile' => 'Student profile',
    ],
    'fields' => [
        'name' => 'Name',
        'email' => 'Email address',
        'password' => 'Password',
        'role' => 'Role',
        'specialty' => 'Specialty',
        'is_approved' => 'Approved',
        'bio' => 'Bio',
        'avatar' => 'Avatar',
        'grade_level' => 'Grade level',
        'parent_phone' => 'Parent phone',
        'created_at' => 'Joined at',
    ],
    'hints' => [
        'password' => 'Leave empty to keep the current password.',
    ],
];
