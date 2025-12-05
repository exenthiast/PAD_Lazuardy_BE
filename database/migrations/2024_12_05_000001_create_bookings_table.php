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
        Schema::create('bookings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->onDelete('cascade');
            $table->foreignId('tutor_id')->constrained('users')->onDelete('cascade');
            $table->dateTime('schedule_time');
            $table->decimal('price', 10, 2);
            $table->enum('status', ['unpaid', 'paid', 'completed', 'cancelled', 'refunded'])->default('unpaid');
            $table->string('payment_url')->nullable();
            $table->string('payment_token')->nullable();
            $table->timestamps();

            // Indexes
            $table->index('user_id');
            $table->index('tutor_id');
            $table->index('status');
            $table->index('schedule_time');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('bookings');
    }
};
