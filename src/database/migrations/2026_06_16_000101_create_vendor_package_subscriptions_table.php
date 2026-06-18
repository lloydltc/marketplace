<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Phase 17: a vendor's active package entitlement. Feature/bump credits are
     * decremented as listings are promoted; the subscription lapses at expires_at.
     */
    public function up(): void
    {
        Schema::create('vendor_package_subscriptions', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('vendor_id');
            $table->uuid('package_id')->nullable();
            $table->integer('listing_credits_remaining')->default(0);
            $table->integer('feature_credits_remaining')->default(0);
            $table->integer('bump_credits_remaining')->default(0);
            $table->string('status', 20)->default('active'); // active | expired
            $table->timestamp('expires_at')->nullable();
            $table->timestamps();

            $table->foreign('vendor_id')->references('id')->on('vendors')->cascadeOnDelete();
            $table->foreign('package_id')->references('id')->on('promotion_packages')->nullOnDelete();

            $table->index(['vendor_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('vendor_package_subscriptions');
    }
};
