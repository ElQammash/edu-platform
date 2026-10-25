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
        // One row per reminder sent, so each student gets each reminder exactly
        // once per scheduled time, even if the scheduler runs twice. A
        // rescheduled lesson has a new live_at, so its reminders go out again.
        Schema::create('live_lesson_reminders', function (Blueprint $table) {
            $table->id();
            $table->foreignId('lesson_id')->constrained()->cascadeOnDelete();
            $table->foreignId('student_id')->constrained('users')->cascadeOnDelete();
            // "24h" or "1h".
            $table->string('kind', 5);
            $table->dateTime('live_at');
            $table->dateTime('sent_at');

            $table->unique(['lesson_id', 'student_id', 'kind', 'live_at'], 'live_lesson_reminders_unique');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('live_lesson_reminders');
    }
};
