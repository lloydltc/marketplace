<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Phase 14: one delivery per Fulfilled-by-Salma order. Drives the rider
     * lifecycle, which in turn feeds the order state machine; cod_expected /
     * cod_collected close the COD cash loop.
     */
    public function up(): void
    {
        Schema::create('deliveries', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('order_id')->unique();
            $table->uuid('rider_id')->nullable();
            $table->uuid('zone_id')->nullable();
            $table->uuid('cash_session_id')->nullable();

            // pending → assigned → picked_up → out_for_delivery → delivered (or failed)
            $table->string('status', 20)->default('pending');

            $table->decimal('cod_expected', 14, 2)->default(0);
            $table->decimal('cod_collected', 14, 2)->nullable();

            $table->string('proof_photo_path')->nullable();
            $table->text('proof_note')->nullable();

            $table->timestamp('assigned_at')->nullable();
            $table->timestamp('picked_up_at')->nullable();
            $table->timestamp('delivered_at')->nullable();
            $table->timestamps();

            $table->foreign('order_id')->references('id')->on('orders')->cascadeOnDelete();
            $table->foreign('rider_id')->references('id')->on('users')->nullOnDelete();
            $table->foreign('zone_id')->references('id')->on('delivery_zones')->nullOnDelete();
            $table->foreign('cash_session_id')->references('id')->on('rider_cash_sessions')->nullOnDelete();

            $table->index(['rider_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('deliveries');
    }
};
