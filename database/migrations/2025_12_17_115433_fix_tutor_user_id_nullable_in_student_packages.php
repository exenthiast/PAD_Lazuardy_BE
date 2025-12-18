<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Use raw SQL to modify the column to be nullable
        DB::statement('ALTER TABLE student_packages MODIFY COLUMN tutor_user_id BIGINT UNSIGNED NULL');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Revert back to NOT NULL (only if all records have tutor_user_id)
        DB::statement('ALTER TABLE student_packages MODIFY COLUMN tutor_user_id BIGINT UNSIGNED NOT NULL');
    }
};
