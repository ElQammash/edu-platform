<?php

namespace App\Notifications;

use App\Models\Payment;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueueAfterCommit;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/**
 * Money went back to the student: a full refund ends the course access,
 * a partial one doesn't. In-app and by email.
 */
class PaymentRefunded extends Notification implements ShouldQueueAfterCommit
{
    use Queueable;

    public const TYPE = 'payment_refunded';

    public function __construct(
        public readonly Payment $payment,
        public readonly float $amount,
        public readonly bool $full,
    ) {}

    /**
     * @return list<string>
     */
    public function via(User $notifiable): array
    {
        return ['database', 'mail'];
    }

    public function databaseType(User $notifiable): string
    {
        return self::TYPE;
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(User $notifiable): array
    {
        return [
            ...PaymentData::of($this->payment),
            'refunded' => $this->amount,
            'full' => $this->full,
        ];
    }

    public function toMail(User $notifiable): MailMessage
    {
        $data = PaymentData::of($this->payment);

        return (new MailMessage)
            ->subject(__('payments.mail.refunded_subject', ['course' => $data['course_title']]))
            ->greeting(__('payments.mail.greeting', ['name' => $notifiable->name]))
            ->line(__($this->full ? 'payments.mail.refunded_line' : 'payments.mail.partially_refunded_line', [
                'amount' => PaymentData::money($this->amount),
                'course' => $data['course_title'],
            ]))
            ->line(__('payments.mail.reference', ['reference' => $this->payment->reference]))
            ->action(__('payments.mail.view_payments'), $data['payments_url']);
    }
}
