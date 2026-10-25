<?php

return [
    'navigation' => 'Q&A',
    'label' => 'question',
    'plural_label' => 'questions',

    'errors' => [
        'edit_window' => 'Posts can only be edited within :minutes minutes of posting.',
    ],

    'fields' => [
        'question' => 'Question',
        'student' => 'Student',
        'lesson' => 'Lesson',
        'course' => 'Course',
        'replies' => 'Replies',
        'status' => 'Status',
        'asked' => 'Asked',
        'reply' => 'Your reply',
    ],

    'status' => [
        'unanswered' => 'Unanswered',
        'answered' => 'Answered',
        'resolved' => 'Resolved',
    ],

    'actions' => [
        'thread' => 'Open',
        'reply' => 'Reply',
        'send' => 'Send reply',
        'resolve' => 'Mark resolved',
        'reopen' => 'Reopen',
        'delete' => 'Delete',
        'accept' => 'Accept as answer',
    ],

    'thread' => [
        'teacher' => 'Teacher',
        'accepted' => 'Accepted answer',
        'deleted' => 'This post was deleted.',
        'no_replies' => 'No replies yet.',
    ],

    'replied' => 'Reply sent',
    'resolved' => 'Question marked resolved',
    'reopened' => 'Question reopened',
    'deleted' => 'Post deleted',
    'empty' => 'No questions here',

    'widget' => [
        'heading' => 'Unanswered questions',
        'empty' => 'All questions are answered.',
    ],

    'mail' => [
        'greeting' => 'Hello :name,',
        'view_answer' => 'View the answer',
        'reply_subject' => 'Your question on ":lesson" was answered',
        'reply_line' => ':teacher replied to your question on ":lesson" (:course):',
        'accepted_subject' => 'Your question on ":lesson" has an answer',
        'accepted_line' => 'The teacher picked this as the answer to your question on ":lesson" (:course):',
        'digest_subject' => '{1} 1 question is waiting for your answer|[2,*] :count questions are waiting for your answer',
        'digest_line' => '{1} 1 student question in your courses has no answer yet:|[2,*] :count student questions in your courses have no answer yet:',
        'digest_item' => ':student on ":lesson" (:course), :ago: “:excerpt”',
        'digest_more' => '…and :count more.',
        'digest_action' => 'Answer in the teacher panel',
    ],
];
