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
            $table->unsignedBigInteger('user_id'); // Student ID
            $table->unsignedBigInteger('tutor_id'); // Tutor ID
            $table->dateTime('schedule_time'); // Gabungan tanggal + jam
            $table->decimal('price', 10, 2); // Harga booking
            $table->enum('status', ['unpaid', 'paid', 'completed', 'cancelled', 'refunded'])
                  ->default('unpaid');
            $table->string('payment_url')->nullable(); // Link pembayaran
            $table->string('payment_token')->nullable(); // Token transaksi dari payment gateway
            $table->timestamps();

            // Foreign keys
            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
            $table->foreign('tutor_id')->references('id')->on('users')->onDelete('cascade');

            // Indexes
            $table->index(['user_id', 'status']);
            $table->index(['tutor_id', 'schedule_time']);
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
