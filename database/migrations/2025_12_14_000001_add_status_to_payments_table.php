<?php

use App\Enums\PaymentStatusEnum;
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
        Schema::table('payments', function (Blueprint $table) {
            // Get list of payment status values
            $payment_status = PaymentStatusEnum::list();
            
            // Add status column if it doesn't exist
            if (!Schema::hasColumn('payments', 'status')) {
                $table->enum('status', $payment_status)->nullable()->after('payment_method');
            }
            
            // Add amount column if it doesn't exist (rename from price_amount)
            if (!Schema::hasColumn('payments', 'amount') && Schema::hasColumn('payments', 'price_amount')) {
                $table->renameColumn('price_amount', 'amount');
            } elseif (!Schema::hasColumn('payments', 'amount')) {
                $table->integer('amount')->nullable()->after('order_id');
            }
            
            // Add paid_at column if it doesn't exist
            if (!Schema::hasColumn('payments', 'paid_at') && Schema::hasColumn('payments', 'date')) {
                $table->renameColumn('date', 'paid_at');
            } elseif (!Schema::hasColumn('payments', 'paid_at')) {
                $table->date('paid_at')->nullable()->after('proof_image_url');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('payments', function (Blueprint $table) {
            // Remove status column
            if (Schema::hasColumn('payments', 'status')) {
                $table->dropColumn('status');
            }
            
            // Rename amount back to price_amount
            if (Schema::hasColumn('payments', 'amount')) {
                $table->renameColumn('amount', 'price_amount');
            }
            
            // Rename paid_at back to date
            if (Schema::hasColumn('payments', 'paid_at')) {
                $table->renameColumn('paid_at', 'date');
            }
        });
    }
};
