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
        Schema::table('students', function (Blueprint $table) {
            // Drop foreign key constraints first
            $table->dropForeign(['class_id']);
            $table->dropForeign(['curriculum_id']);
            
            // Make columns nullable
            $table->foreignId('class_id')->nullable()->change()->constrained('classes');
            $table->foreignId('curriculum_id')->nullable()->change()->constrained('curriculums');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('students', function (Blueprint $table) {
            // Revert back to not nullable
            $table->dropForeign(['class_id']);
            $table->dropForeign(['curriculum_id']);
            
            $table->foreignId('class_id')->nullable(false)->change()->constrained('classes');
            $table->foreignId('curriculum_id')->nullable(false)->change()->constrained('curriculums');
        });
    }
};
