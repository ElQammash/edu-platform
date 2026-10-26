<?php

namespace App\Notifications;

use App\Models\Payment;

/**
 * The plain data payment notifications store and email.
 */
final class PaymentData
{
    /**
     * @return array{reference: string, amount: float, currency: string, course_id: int, course_slug: string, course_title: string, course_url: string, payments_url: string}
     */
    public static function of(Payment $payment): array
    {
        $frontend = rtrim((string) config('app.frontend_url'), '/');

        return [
            'reference' => $payment->reference,
            'amount' => (float) $payment->amount,
            'currency' => $payment->currency,
            'course_id' => $payment->course->id,
            'course_slug' => $payment->course->slug,
            'course_title' => $payment->course->title,
            'course_url' => $frontend.'/courses/'.$payment->course->slug,
            'payments_url' => $frontend.'/my-payments',
        ];
    }

    /**
     * "750.00 EGP" / "750.00 ج.م" in the current (recipient's) language.
     */
    public static function money(float $amount): string
    {
        return number_format($amount, 2).' '.__('payments.currency');
    }
}
