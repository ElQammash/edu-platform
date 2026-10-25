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
        Schema::create('quiz_attempts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('quiz_id')->constrained()->cascadeOnDelete();
            $table->foreignId('student_id')->constrained('users')->cascadeOnDelete();
            // dateTime, not timestamp: MySQL/MariaDB may add ON UPDATE CURRENT_TIMESTAMP
            // to the first NOT NULL timestamp column (see the enrollments fix).
            $table->dateTime('started_at');
            // Set by the server from the time limit (and the due date); null if untimed.
            $table->dateTime('expires_at')->nullable();
            $table->dateTime('submitted_at')->nullable();
            // Set once every answer has points (essays may wait for the teacher).
            $table->dateTime('graded_at')->nullable()->index();
            $table->decimal('score', 8, 2)->nullable();
            // Total points of the quiz when the attempt started.
            $table->decimal('max_score', 8, 2);
            // Order shown to this student (shuffled or not), so a refresh keeps it.
            $table->json('question_order');
            // {question_id: [option ids]}.
            $table->json('option_order');
            $table->timestamps();

            $table->index(['quiz_id', 'student_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('quiz_attempts');
    }
};
