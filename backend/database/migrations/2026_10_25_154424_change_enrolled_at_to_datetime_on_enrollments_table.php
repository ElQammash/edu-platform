<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * MySQL/MariaDB (with explicit_defaults_for_timestamp off, e.g. XAMPP) give
 * the first NOT NULL TIMESTAMP column ON UPDATE CURRENT_TIMESTAMP, so every
 * progress update was resetting enrolled_at. DATETIME has no such behaviour.
 */
return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('enrollments', function (Blueprint $table) {
            $table->dateTime('enrolled_at')->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('enrollments', function (Blueprint $table) {
            $table->timestamp('enrolled_at')->change();
        });
    }
};
