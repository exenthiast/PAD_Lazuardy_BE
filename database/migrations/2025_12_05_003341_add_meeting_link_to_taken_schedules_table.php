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
        Schema::table('taken_schedules', function (Blueprint $table) {
            $table->string('meeting_link')->nullable()->after('time');
            $table->boolean('meeting_link_sent')->default(false)->after('meeting_link');
            $table->timestamp('meeting_link_sent_at')->nullable()->after('meeting_link_sent');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('taken_schedules', function (Blueprint $table) {
            $table->dropColumn(['meeting_link', 'meeting_link_sent', 'meeting_link_sent_at']);
        });
    }
};
