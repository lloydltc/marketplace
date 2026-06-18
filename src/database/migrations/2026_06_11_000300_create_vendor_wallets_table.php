<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Phase 13: one wallet per vendor. cached_balance is a MATERIALISED view of
     * the ledger — never authoritative. It is recomputed from entries on every
     * post and proven correct by reconciliation (BUSINESS_MODEL.md §4).
     */
    public function up(): void
    {
        Schema::create('vendor_wallets', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('vendor_id')->unique();
            $table->string('currency', 3)->default('ZWL');
            $table->decimal('cached_balance', 16, 2)->default(0);
            $table->timestamp('reconciled_at')->nullable();
            $table->timestamps();

            $table->foreign('vendor_id')->references('id')->on('vendors')->cascadeOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('vendor_wallets');
    }
};
