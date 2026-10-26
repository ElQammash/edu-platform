<?php

return [
    'navigation' => 'Payments',
    'currency' => 'EGP',
    'label' => 'payment',
    'plural_label' => 'payments',

    'errors' => [
        'students_only' => 'Only students can buy courses.',
        'free' => 'This course is free: enroll directly.',
        'already_enrolled' => 'You are already enrolled in this course.',
        'gateway_unavailable' => 'The payment service is not responding. Please try again in a few minutes.',
    ],

    'fields' => [
        'reference' => 'Reference',
        'student' => 'Student',
        'course' => 'Course',
        'amount' => 'Amount',
        'refunded_amount' => 'Refunded',
        'net' => 'Net',
        'status' => 'Status',
        'method' => 'Method',
        'gateway' => 'Gateway',
        'gateway_order_id' => 'Gateway order',
        'gateway_transaction_id' => 'Gateway transaction',
        'created_at' => 'Started',
        'paid_at' => 'Paid',
        'refunded_at' => 'Refunded at',
        'events' => 'Gateway messages',
        'event_type' => 'Type',
        'event_source' => 'From',
        'event_payload' => 'Payload',
        'from' => 'From',
        'until' => 'Until',
    ],

    'event_types' => [
        'payment' => 'Payment',
        'pending' => 'Pending',
        'failed' => 'Failed',
        'refund' => 'Refund',
        'void' => 'Void',
        'rejected' => 'Rejected (mismatch)',
        'failed_refund' => 'Refund failed',
        'failed_void' => 'Void failed',
    ],

    'widget' => [
        'this_month' => 'Revenue this month',
        'last_month' => 'Revenue last month',
        'paid_enrollments' => 'Paid enrollments this month',
        'net_hint' => 'After refunds',
        'pending' => ':count pending',
    ],

    'mail' => [
        'greeting' => 'Hello :name,',
        'paid_subject' => 'Payment received: :course',
        'paid_line' => 'We received your payment of :amount for ":course". You are now enrolled.',
        'reference' => 'Reference: :reference',
        'start' => 'Start the course',
        'refunded_subject' => 'Refund for :course',
        'refunded_line' => ':amount was refunded for ":course". Your access to the course has ended.',
        'partially_refunded_line' => ':amount was refunded for ":course". You keep your access to the course.',
        'view_payments' => 'My payments',
    ],

    'fake' => [
        'title' => 'Test checkout',
        'notice' => 'Local test payment: no real money is involved. This page never works in production.',
        'pay' => 'Pay :amount',
        'fail' => 'Simulate a declined card',
        'sending' => 'Sending…',
    ],
];
