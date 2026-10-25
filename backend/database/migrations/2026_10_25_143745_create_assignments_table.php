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
        Schema::create('assignments', function (Blueprint $table) {
            $table->id();
            // One assignment per lesson; the lesson grade combines it with the quiz (phase 4).
            $table->foreignId('lesson_id')->unique()->constrained()->cascadeOnDelete();
            $table->string('title');
            // Markdown.
            $table->longText('instructions')->nullable();
            // Optional. Submissions after it are accepted but flagged as late.
            $table->timestamp('due_at')->nullable();
            $table->unsignedSmallInteger('max_score')->default(100);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('assignments');
    }
};
