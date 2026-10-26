<?php

namespace Database\Factories;

use App\Enums\PaymentState;
use App\Models\Course;
use App\Models\Payment;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Payment>
 */
class PaymentFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'reference' => (string) Str::uuid(),
            'student_id' => User::factory()->student(),
            'course_id' => Course::factory()->state(['price' => 500]),
            'amount' => 500,
            'currency' => 'EGP',
            'gateway' => 'paymob',
            'status' => PaymentState::Pending,
            'gateway_order_id' => (string) fake()->unique()->numberBetween(100000, 999999),
            'checkout_url' => 'https://checkout.test/pay',
            'expires_at' => now()->addMinutes(Payment::VALID_MINUTES),
        ];
    }

    public function paid(): static
    {
        return $this->state(fn (): array => [
            'status' => PaymentState::Paid,
            'gateway_transaction_id' => (string) fake()->unique()->numberBetween(1000000, 9999999),
            'payment_method' => 'card •••• 2346',
            'paid_at' => now(),
        ]);
    }
}
