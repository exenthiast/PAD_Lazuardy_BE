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
        Schema::create('session_reports', function (Blueprint $table) {
            $table->id();
            $table->foreignId('student_package_id')->constrained('student_packages')->onDelete('cascade');
            $table->foreignId('taken_schedule_id')->constrained('taken_schedules')->onDelete('cascade');
            $table->integer('session_number'); // Pertemuan ke-berapa
            $table->string('material')->nullable(); // Materi yang dipelajari
            $table->string('score')->nullable(); // Nilai tugas
            $table->text('review')->nullable(); // Review dari tutor
            $table->string('document_path')->nullable(); // Path ke dokumen/foto
            $table->date('session_date'); // Tanggal pertemuan
            $table->timestamps();
            
            // Index untuk pencarian
            $table->index(['student_package_id', 'session_number']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('session_reports');
    }
};
