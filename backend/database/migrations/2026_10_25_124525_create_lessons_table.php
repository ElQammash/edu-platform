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
        Schema::create('lessons', function (Blueprint $table) {
            $table->id();
            $table->foreignId('section_id')->constrained()->cascadeOnDelete();
            $table->string('title');
            $table->string('type')->default('video');
            // Body for text lessons, notes for video/live lessons.
            $table->longText('content')->nullable();
            // External link only (Bunny Stream, YouTube unlisted, Vimeo); never a file on our server.
            $table->string('video_url')->nullable();
            // Optional; the player reports the real duration when it is missing.
            $table->unsignedInteger('duration_seconds')->nullable();
            // Live lessons (phase 5).
            $table->timestamp('live_at')->nullable();
            $table->string('meeting_url')->nullable();
            $table->unsignedInteger('order')->default(0);
            $table->boolean('is_free_preview')->default(false);
            $table->timestamps();

            $table->index(['section_id', 'order']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('lessons');
    }
};
