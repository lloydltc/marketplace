<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Vendor wallet top-ups via Pesepay. On a successful gateway result a TOP_UP
     * ledger entry is posted (which can lift the vendor back above the floor).
     */
    public function up(): void
    {
        Schema::create('wallet_top_ups', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('vendor_id');
            $table->string('merchant_reference')->unique();
            $table->string('gateway_ref')->nullable()->unique();
            $table->decimal('amount', 16, 2);
            $table->string('currency', 3)->default('ZWL');
            $table->string('status', 20)->default('pending'); // pending|paid|failed
            $table->text('redirect_url')->nullable();
            $table->string('webhook_payload_hash')->nullable();
            $table->timestamp('paid_at')->nullable();
            $table->timestamps();

            $table->foreign('vendor_id')->references('id')->on('vendors')->cascadeOnDelete();
            $table->index('vendor_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('wallet_top_ups');
    }
};
