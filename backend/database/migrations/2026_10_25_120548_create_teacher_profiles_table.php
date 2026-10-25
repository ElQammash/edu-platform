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
        Schema::create('teacher_profiles', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->unique()->constrained()->cascadeOnDelete();
            $table->text('bio')->nullable();
            $table->string('specialty')->nullable();
            $table->string('avatar')->nullable();
            // Cached average of course reviews, refreshed when a review changes.
            $table->decimal('rating', 3, 2)->default(0);
            // Always true for admin-created teachers today; lets self-registered
            // teachers wait for approval once the platform opens up.
            $table->boolean('is_approved')->default(false)->index();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('teacher_profiles');
    }
};
