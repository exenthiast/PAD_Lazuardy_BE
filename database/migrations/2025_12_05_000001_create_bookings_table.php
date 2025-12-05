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
            $table->foreignId('user_id')->constrained('users', 'id')->onDelete('cascade');
            $table->foreignId('tutor_id')->constrained('users', 'id')->onDelete('cascade');
            $table->dateTime('schedule_time');
            $table->integer('price');
            $table->enum('status', ['unpaid', 'paid', 'completed', 'cancelled', 'refunded'])->default('unpaid');
            $table->string('payment_url')->nullable();
            $table->string('payment_token')->nullable();
            $table->timestamps();
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
