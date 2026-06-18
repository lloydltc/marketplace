<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Phase 16: Concierge ("we find it, verify it, deliver it" — BUSINESS_MODEL.md
     * §6 Tier 2). Deliberately a single self-contained workflow an ops person runs
     * end-to-end. Payment is collected via Pesepay (gateway fields inline); when
     * the part is sourced from an on-platform vendor, the vendor is settled via
     * the wallet like an FBS order.
     */
    public function up(): void
    {
        Schema::create('concierge_requests', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('buyer_user_id');

            $table->uuid('make_id')->nullable();
            $table->uuid('model_id')->nullable();
            $table->integer('year')->nullable();
            $table->text('part_description');
            $table->string('location');
            $table->text('notes')->nullable();

            // new → sourcing → quoted → paid → fulfilling → delivered → closed (or cancelled)
            $table->string('status', 20)->default('new');

            // Set by the admin when quoting.
            $table->decimal('part_value', 14, 2)->nullable();
            $table->decimal('service_fee', 14, 2)->nullable();
            $table->decimal('delivery_fee', 14, 2)->default(0);
            $table->decimal('total', 14, 2)->nullable();
            $table->string('currency', 3)->default('ZWL');

            // If the part is sourced on-platform, this vendor is settled via wallet.
            $table->uuid('sourced_vendor_id')->nullable();
            $table->timestamp('settled_at')->nullable();

            // Inline gateway payment.
            $table->string('merchant_reference')->nullable()->unique();
            $table->string('gateway_ref')->nullable()->unique();
            $table->string('payment_status', 20)->default('unpaid');
            $table->text('redirect_url')->nullable();
            $table->string('webhook_payload_hash')->nullable();
            $table->timestamp('paid_at')->nullable();

            $table->timestamps();

            $table->foreign('buyer_user_id')->references('id')->on('users')->cascadeOnDelete();
            $table->foreign('make_id')->references('id')->on('vehicle_makes')->nullOnDelete();
            $table->foreign('model_id')->references('id')->on('vehicle_models')->nullOnDelete();
            $table->foreign('sourced_vendor_id')->references('id')->on('vendors')->nullOnDelete();

            $table->index('status');
            $table->index('buyer_user_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('concierge_requests');
    }
};
