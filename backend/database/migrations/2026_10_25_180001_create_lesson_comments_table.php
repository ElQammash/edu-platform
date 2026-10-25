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
        // A lesson's Q&A: questions (parent_id null) and their replies, one level deep.
        Schema::create('lesson_comments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('lesson_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('parent_id')->nullable()->constrained('lesson_comments')->cascadeOnDelete();
            $table->text('body');
            // Posted by the course teacher or an admin: shown with a "Teacher" badge,
            // and a staff reply is what makes a question answered.
            $table->boolean('by_staff')->default(false);
            // Questions only. dateTime, not timestamp: avoids MySQL's implicit ON UPDATE.
            $table->dateTime('resolved_at')->nullable();
            $table->foreignId('accepted_reply_id')->nullable()->constrained('lesson_comments')->nullOnDelete();
            $table->dateTime('edited_at')->nullable();
            $table->timestamps();
            // Deleted questions keep their replies (shown as "deleted").
            $table->softDeletes();

            $table->index(['lesson_id', 'parent_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('lesson_comments');
    }
};
