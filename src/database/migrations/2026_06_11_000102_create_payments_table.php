<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Gateway payment attempts. One order may have several (retry after fail),
     * so order_id is NOT unique — but gateway_ref and merchant_reference are.
     * webhook_payload_hash + a terminal status guard make webhook processing
     * idempotent (a replayed webhook moves no money twice).
     */
    public function up(): void
    {
        Schema::create('payments', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('order_id');
            $table->string('gateway')->default('pesepay');
            $table->string('merchant_reference')->unique();
            $table->string('gateway_ref')->nullable()->unique();
            $table->string('method')->nullable();
            $table->decimal('amount', 14, 2);
            $table->string('currency', 3)->default('ZWL');
            $table->string('status', 20)->default('pending');
            $table->text('redirect_url')->nullable();
            $table->text('poll_url')->nullable();
            $table->string('webhook_payload_hash')->nullable();
            $table->timestamp('paid_at')->nullable();
            $table->timestamps();

            $table->foreign('order_id')->references('id')->on('orders')->cascadeOnDelete();

            $table->index('order_id');
            $table->index('status');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('payments');
    }
};
