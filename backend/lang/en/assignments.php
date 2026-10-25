<?php

return [
    'section' => 'Assignment (optional)',
    'section_hint' => 'Give the lesson an assignment by entering a title. Students can submit text and/or one PDF or image.',
    'label' => 'Assignment',
    'plural_label' => 'Assignments',
    'fields' => [
        'title' => 'Assignment title',
        'instructions' => 'Instructions',
        'due_at' => 'Due date',
        'max_score' => 'Max score',
    ],
    'hints' => [
        'due_at' => 'Optional. Students can still submit after it; those submissions are marked late.',
    ],
];
