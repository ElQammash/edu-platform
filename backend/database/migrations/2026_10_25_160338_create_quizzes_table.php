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
        Schema::create('quizzes', function (Blueprint $table) {
            $table->id();
            // One quiz per lesson; the lesson grade combines it with the assignment.
            $table->foreignId('lesson_id')->unique()->constrained()->cascadeOnDelete();
            $table->string('title');
            // Markdown, shown before starting.
            $table->text('description')->nullable();
            // Null means untimed.
            $table->unsignedSmallInteger('time_limit_minutes')->nullable();
            // Percentage needed to pass (0-100).
            $table->unsignedTinyInteger('pass_score')->default(50);
            $table->unsignedTinyInteger('max_attempts')->default(1);
            // Optional. Closes the quiz; once passed, a missing attempt counts as 0.
            // Nullable timestamps do not get MySQL's ON UPDATE, but dateTime keeps it explicit.
            $table->dateTime('due_at')->nullable();
            // Null = default: after the due date if there is one, otherwise right after grading.
            $table->string('show_answers')->nullable();
            // Shuffle question and option order per attempt.
            $table->boolean('shuffle')->default(false);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('quizzes');
    }
};
