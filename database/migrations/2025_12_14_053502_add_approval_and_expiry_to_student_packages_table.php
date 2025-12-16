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
            $table->enum('status', ['pending', 'approved', 'rejected', 'expired'])->default('pending')->after('remaining_session');
            $table->date('start_date')->nullable()->after('status');
            $table->date('expired_at')->nullable()->after('start_date');
            $table->text('rejection_reason')->nullable()->after('expired_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('student_packages', function (Blueprint $table) {
            $table->dropColumn(['status', 'start_date', 'expired_at', 'rejection_reason']);
        });
    }
};
