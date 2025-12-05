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
        Schema::create('student_attendances', function (Blueprint $table) {
            $table->id();
            $table->foreignId('taken_schedule_id')->constrained('taken_schedules')->onDelete('cascade');
            $table->foreignId('student_user_id')->constrained('users')->onDelete('cascade');
            $table->string('proof_document')->nullable(); // Path ke foto/dokumen bukti
            $table->text('notes')->nullable(); // Catatan tambahan dari student
            $table->timestamp('submitted_at')->nullable();
            $table->timestamps();
            
            // Index
            $table->index(['taken_schedule_id', 'student_user_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('student_attendances');
    }
};
