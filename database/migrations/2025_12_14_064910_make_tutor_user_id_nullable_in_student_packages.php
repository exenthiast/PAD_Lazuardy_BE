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
        Schema::table('student_packages', function (Blueprint $table) {
            // Drop foreign key first
            $table->dropForeign(['tutor_user_id']);
            
            // Modify column to be nullable
            $table->foreignId('tutor_user_id')->nullable()->change()->constrained('users', 'id')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('student_packages', function (Blueprint $table) {
            // Drop foreign key
            $table->dropForeign(['tutor_user_id']);
            
            // Make it not nullable again
            $table->foreignId('tutor_user_id')->change()->constrained('users', 'id')->onDelete('cascade');
        });
    }
};
