<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Phase 17: every promotion purchase — featured/bump/badge per-listing, or a
     * dealer package. The immutable revenue record for reporting (Phase 24), plus
     * the inline Pesepay payment fields. Credit-funded promotions are recorded
     * with amount 0 and status "completed" for a complete audit trail.
     */
    public function up(): void
    {
        Schema::create('promotion_purchases', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('vendor_id');
            $table->uuid('vehicle_id')->nullable();
            $table->uuid('package_id')->nullable();
            $table->enum('type', ['featured', 'bump', 'badge', 'package']);
            $table->decimal('amount', 14, 2)->default(0);
            $table->string('currency', 3)->default('ZWL');
            // pending → completed | failed
            $table->string('status', 20)->default('pending');
            $table->string('funded_by', 10)->default('gateway'); // gateway | credit

            $table->string('merchant_reference')->nullable()->unique();
            $table->string('gateway_ref')->nullable()->unique();
            $table->text('redirect_url')->nullable();
            $table->string('webhook_payload_hash')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->timestamps();

            $table->foreign('vendor_id')->references('id')->on('vendors')->cascadeOnDelete();
            $table->foreign('vehicle_id')->references('id')->on('vehicles')->nullOnDelete();
            $table->foreign('package_id')->references('id')->on('promotion_packages')->nullOnDelete();

            $table->index(['vendor_id', 'type']);
            $table->index(['status', 'completed_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('promotion_purchases');
    }
};
