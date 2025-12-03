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
        Schema::table('tutors', function (Blueprint $table) {
            $table->string('keahlian')->nullable()->after('user_id');
            $table->enum('market_siswa', ['sd', 'smp', 'sma', 'umum'])->nullable()->after('keahlian');
            $table->text('pengalaman')->nullable()->after('market_siswa');
            $table->string('skil_bahasa')->nullable()->after('pengalaman');
            $table->string('organisasi')->nullable()->after('skil_bahasa');
            $table->string('cv_path')->nullable()->after('organisasi');
            $table->string('ktp_path')->nullable()->after('cv_path');
            $table->string('ijazah_path')->nullable()->after('ktp_path');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('tutors', function (Blueprint $table) {
            $table->dropColumn([
                'keahlian',
                'market_siswa',
                'pengalaman',
                'skil_bahasa',
                'organisasi',
                'cv_path',
                'ktp_path',
                'ijazah_path',
            ]);
        });
    }
};
