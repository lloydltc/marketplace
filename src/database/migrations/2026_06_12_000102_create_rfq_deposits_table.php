<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Refundable commitment deposit for high-value RFQs (BUSINESS_MODEL.md §6).
     * Buyer-side, immutable record of the deposit lifecycle: paid via Pesepay,
     * credited against the converted order, or refunded/forfeited on abandonment.
     */
    public function up(): void
    {
        Schema::create('rfq_deposits', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('part_request_id');
            $table->uuid('buyer_user_id');
            $table->decimal('amount', 14, 2);
            $table->string('currency', 3)->default('ZWL');
            $table->string('merchant_reference')->unique();
            $table->string('gateway_ref')->nullable()->unique();
            // pending → paid → credited | refunded | forfeited
            $table->string('status', 20)->default('pending');
            $table->text('redirect_url')->nullable();
            $table->string('webhook_payload_hash')->nullable();
            $table->timestamp('paid_at')->nullable();
            $table->timestamps();

            $table->foreign('part_request_id')->references('id')->on('part_requests')->cascadeOnDelete();
            $table->foreign('buyer_user_id')->references('id')->on('users')->cascadeOnDelete();

            $table->index('part_request_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('rfq_deposits');
    }
};
