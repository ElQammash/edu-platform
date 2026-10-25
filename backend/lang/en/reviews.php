<?php

return [
    'navigation' => 'Reviews',
    'label' => 'review',
    'plural_label' => 'reviews',

    'errors' => [
        'students_only' => 'Only students can review courses.',
        'not_enrolled' => 'Enroll in the course to review it.',
        'too_early' => 'You can review the course once you have completed :percent% of it.',
    ],

    'fields' => [
        'student' => 'Student',
        'course' => 'Course',
        'rating' => 'Rating',
        'comment' => 'Comment',
        'reply' => 'Your reply',
        'posted' => 'Posted',
        'status' => 'Status',
    ],

    'status' => [
        'visible' => 'Visible',
        'hidden' => 'Hidden',
        'unreplied' => 'Not replied',
    ],

    'actions' => [
        'reply' => 'Reply',
        'edit_reply' => 'Edit reply',
        'hide' => 'Hide',
        'unhide' => 'Show again',
        'hide_confirm' => 'The review will disappear from the course page and stop counting in its rating.',
    ],

    'replied' => 'Reply saved',
    'hidden' => 'Review hidden',
    'unhidden' => 'Review visible again',
    'empty' => 'No reviews yet',
];
