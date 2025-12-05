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
            // Add bank information fields
            if (!Schema::hasColumn('tutors', 'bank_name')) {
                $table->string('bank_name')->nullable()->after('price');
            }
            
            if (!Schema::hasColumn('tutors', 'bank_account_number')) {
                $table->string('bank_account_number')->nullable()->after('bank_name');
            }
            
            if (!Schema::hasColumn('tutors', 'bank_account_name')) {
                $table->string('bank_account_name')->nullable()->after('bank_account_number');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('tutors', function (Blueprint $table) {
            if (Schema::hasColumn('tutors', 'bank_name')) {
                $table->dropColumn('bank_name');
            }
            
            if (Schema::hasColumn('tutors', 'bank_account_number')) {
                $table->dropColumn('bank_account_number');
            }
            
            if (Schema::hasColumn('tutors', 'bank_account_name')) {
                $table->dropColumn('bank_account_name');
            }
        });
    }
};
