<?php

return [
    'currency' => 'EGP',
    'course' => [
        'label' => 'Course',
        'plural_label' => 'Courses',
        'sections' => [
            'details' => 'Course details',
            'publishing' => 'Pricing and publishing',
        ],
        'fields' => [
            'title' => 'Title',
            'slug' => 'URL slug',
            'description' => 'Description',
            'thumbnail' => 'Thumbnail',
            'teacher' => 'Teacher',
            'price' => 'Price',
            'language' => 'Teaching language',
            'type' => 'Type',
            'status' => 'Status',
            'lessons_count' => 'Lessons',
            'students_count' => 'Students',
            'published_at' => 'Published at',
        ],
        'hints' => [
            'slug' => 'Generated from the title when left empty. Changing it changes the course URL.',
            'price' => 'In EGP. 0 means free: students enroll themselves. Paid courses are enrolled by the admin.',
        ],
    ],
    'section' => [
        'label' => 'Section',
        'plural_label' => 'Sections',
        'fields' => [
            'title' => 'Title',
        ],
    ],
    'lesson' => [
        'label' => 'Lesson',
        'plural_label' => 'Lessons',
        'fields' => [
            'title' => 'Title',
            'type' => 'Type',
            'video_url' => 'Video link',
            'duration' => 'Duration (minutes)',
            'content' => 'Content',
            'is_free_preview' => 'Free preview',
        ],
        'hints' => [
            'video_url' => 'Bunny Stream, YouTube (unlisted) or Vimeo link. Videos are never uploaded to our server.',
            'duration' => 'Optional. The player measures it when empty.',
            'content' => 'Markdown. The lesson text for text lessons, or notes shown under the video.',
            'is_free_preview' => 'Anyone can watch this lesson without enrolling.',
        ],
    ],
];
