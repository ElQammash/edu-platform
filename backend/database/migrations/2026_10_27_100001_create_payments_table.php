<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // One online checkout for one course. Dates are dateTime, not timestamp:
        // avoids MySQL's implicit ON UPDATE on the first NOT NULL timestamp.
        Schema::create('payments', function (Blueprint $table) {
            $table->id();
            // Our id for the payment, sent to the gateway (Paymob's special_reference).
            $table->uuid('reference')->unique();
            $table->foreignId('student_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('course_id')->constrained()->restrictOnDelete();
            // Locked when checkout starts; a later price change doesn't affect it.
            $table->decimal('amount', 10, 2);
            $table->decimal('refunded_amount', 10, 2)->default(0);
            $table->string('currency', 3)->default('EGP');
            $table->string('gateway', 20);
            $table->string('status', 20)->index();
            $table->string('gateway_order_id')->nullable()->index();
            $table->string('gateway_transaction_id')->nullable()->unique();
            // e.g. "card •••• 2346" or "wallet".
            $table->string('payment_method')->nullable();
            $table->text('checkout_url')->nullable();
            $table->dateTime('expires_at')->nullable();
            $table->dateTime('paid_at')->nullable();
            $table->dateTime('failed_at')->nullable();
            $table->dateTime('refunded_at')->nullable();
            $table->timestamps();

            $table->index(['student_id', 'course_id', 'status']);
        });

        // Every gateway message about a payment (callback, redirect, inquiry),
        // once per gateway transaction: the audit trail, and what makes a
        // repeated callback harmless.
        Schema::create('payment_events', function (Blueprint $table) {
            $table->id();
            $table->foreignId('payment_id')->constrained()->cascadeOnDelete();
            $table->string('gateway_transaction_id');
            // payment, refund, void, pending, failed, rejected (a message we refused).
            $table->string('type', 20);
            $table->decimal('amount', 10, 2)->nullable();
            // callback, redirect, inquiry.
            $table->string('source', 20);
            $table->json('payload');
            $table->dateTime('created_at');

            $table->unique(['payment_id', 'gateway_transaction_id', 'type']);
        });

        Schema::table('enrollments', function (Blueprint $table) {
            $table->foreignId('payment_id')->nullable()->after('payment_status')->constrained()->nullOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('enrollments', function (Blueprint $table) {
            $table->dropConstrainedForeignId('payment_id');
        });

        Schema::dropIfExists('payment_events');
        Schema::dropIfExists('payments');
    }
};
