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
        // Update all existing tutors with NULL status to "verify"
        DB::table('tutors')
            ->whereNull('status')
            ->update(['status' => 'verify']);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Optional: Rollback changes (set back to NULL)
        // Uncomment if needed
        // DB::table('tutors')
        //     ->where('status', 'verify')
        //     ->update(['status' => null]);
    }
};
