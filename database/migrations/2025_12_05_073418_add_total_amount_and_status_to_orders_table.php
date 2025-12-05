<?php

use App\Enums\OrderStatusEnum;
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
        $orderStatus = OrderStatusEnum::list();

        Schema::table('orders', function (Blueprint $table) use ($orderStatus) {
            $table->integer('total_amount')->after('user_id');
            $table->enum('status', $orderStatus)->after('total_amount');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->dropColumn(['total_amount', 'status']);
        });
    }
};
