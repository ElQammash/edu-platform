<?php

namespace App\Notifications;

use App\Models\Payment;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueueAfterCommit;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/**
 * The receipt: the payment went through and the student is enrolled.
 * In-app and by email, in the student's language.
 */
class PaymentConfirmed extends Notification implements ShouldQueueAfterCommit
{
    use Queueable;

    public const TYPE = 'payment_confirmed';

    public function __construct(public readonly Payment $payment) {}

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
        return PaymentData::of($this->payment);
    }

    public function toMail(User $notifiable): MailMessage
    {
        $data = PaymentData::of($this->payment);

        return (new MailMessage)
            ->subject(__('payments.mail.paid_subject', ['course' => $data['course_title']]))
            ->greeting(__('payments.mail.greeting', ['name' => $notifiable->name]))
            ->line(__('payments.mail.paid_line', [
                'amount' => PaymentData::money((float) $this->payment->amount),
                'course' => $data['course_title'],
            ]))
            ->line(__('payments.mail.reference', ['reference' => $this->payment->reference]))
            ->action(__('payments.mail.start'), $data['course_url']);
    }
}
