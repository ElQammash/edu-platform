<?php

namespace App\Http\Resources\V1;

use App\Models\Payment;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * A payment as its student sees it. Gateway ids and payloads stay in the
 * admin panel.
 *
 * @mixin Payment
 */
class PaymentResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'reference' => $this->reference,
            'status' => $this->status,
            'amount' => (float) $this->amount,
            'refunded_amount' => (float) $this->refunded_amount,
            'currency' => $this->currency,
            'payment_method' => $this->payment_method,
            'course' => [
                'id' => $this->course->id,
                'slug' => $this->course->slug,
                'title' => $this->course->title,
            ],
            // Only while the student can still go back to the checkout.
            'checkout_url' => $this->isReusable() ? $this->checkout_url : null,
            'created_at' => $this->created_at?->toIso8601String(),
            'paid_at' => $this->paid_at?->toIso8601String(),
            'refunded_at' => $this->refunded_at?->toIso8601String(),
        ];
    }
}
