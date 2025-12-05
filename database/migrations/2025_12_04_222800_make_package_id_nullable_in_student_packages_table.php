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
            // Drop existing foreign key constraint
            $table->dropForeign(['package_id']);
            
            // Make package_id nullable
            $table->foreignId('package_id')->nullable()->change();
            
            // Re-add foreign key constraint
            $table->foreign('package_id')->references('id')->on('packages')->onDelete('set null');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('student_packages', function (Blueprint $table) {
            // Drop foreign key
            $table->dropForeign(['package_id']);
            
            // Make package_id not nullable again
            $table->foreignId('package_id')->nullable(false)->change();
            
            // Re-add foreign key constraint
            $table->foreign('package_id')->references('id')->on('packages');
        });
    }
};
