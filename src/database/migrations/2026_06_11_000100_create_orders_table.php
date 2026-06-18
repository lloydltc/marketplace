<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Phase 11: one order per vendor group (created from the checkout summary).
     * Carries the IMMUTABLE commission snapshot — historical orders must keep
     * their original commission even after platform/vendor rates change.
     * Phase 12 layers the full status state machine, history, and invoices on top.
     */
    public function up(): void
    {
        Schema::create('orders', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('order_number')->unique();

            // Buyer (nullable user_id supports guest checkout).
            $table->uuid('buyer_user_id')->nullable();
            $table->string('buyer_name');
            $table->string('buyer_email');
            $table->string('buyer_phone');
            $table->string('buyer_address');
            $table->string('buyer_city');

            $table->uuid('vendor_id');
            $table->enum('fulfilment_track', ['fbs', 'vendor']);
            $table->enum('payment_method', ['prepaid', 'cod']);
            $table->string('status', 30)->default('pending_payment');

            $table->string('currency', 3)->default('ZWL');
            $table->decimal('subtotal', 14, 2);
            $table->decimal('delivery_fee', 14, 2)->default(0);
            $table->decimal('total', 14, 2);

            // Immutable commission snapshot (BUSINESS_MODEL.md §5).
            $table->decimal('commission_rate_applied', 5, 2)->default(0);
            $table->decimal('commission_amount', 14, 2)->default(0);
            $table->decimal('net_to_vendor', 14, 2)->default(0);

            $table->timestamp('paid_at')->nullable();
            $table->timestamps();

            $table->foreign('buyer_user_id')->references('id')->on('users')->nullOnDelete();
            $table->foreign('vendor_id')->references('id')->on('vendors')->restrictOnDelete();

            $table->index('vendor_id');
            $table->index('buyer_user_id');
            $table->index(['status', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('orders');
    }
};
