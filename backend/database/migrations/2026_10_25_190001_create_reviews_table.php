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
        Schema::create('reviews', function (Blueprint $table) {
            $table->id();
            $table->foreignId('course_id')->constrained()->cascadeOnDelete();
            $table->foreignId('student_id')->constrained('users')->cascadeOnDelete();
            $table->unsignedTinyInteger('rating');
            $table->text('comment')->nullable();
            // The teacher's one public reply. dateTime, not timestamp: avoids MySQL's implicit ON UPDATE.
            $table->text('teacher_reply')->nullable();
            $table->dateTime('teacher_replied_at')->nullable();
            // Hidden by an admin: not listed and not counted in the average.
            $table->dateTime('hidden_at')->nullable();
            $table->foreignId('hidden_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->unique(['course_id', 'student_id']);
            $table->index(['course_id', 'hidden_at']);
        });

        // Cached from the visible reviews (see RatingService), so the catalog
        // can show and sort by rating without aggregating.
        Schema::table('courses', function (Blueprint $table) {
            $table->decimal('rating_avg', 3, 2)->default(0)->after('status')->index();
            $table->unsignedInteger('reviews_count')->default(0)->after('rating_avg');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('courses', function (Blueprint $table) {
            $table->dropIndex(['rating_avg']);
            $table->dropColumn(['rating_avg', 'reviews_count']);
        });

        Schema::dropIfExists('reviews');
    }
};
