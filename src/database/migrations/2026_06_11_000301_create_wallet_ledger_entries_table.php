<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Append-only, double-entry ledger. Entries are NEVER updated or deleted —
     * corrections are new entries. idempotency_key (unique) makes money-moving
     * operations safe to retry: a duplicate settlement/webhook posts nothing new.
     */
    public function up(): void
    {
        Schema::create('wallet_ledger_entries', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('wallet_id');
            $table->enum('type', [
                'SALE_CREDIT',
                'COMMISSION_DEBIT',
                'DELIVERY_FEE_DEBIT',
                'TOP_UP',
                'PAYOUT',
                'REFUND_ADJUSTMENT',
                'MANUAL_ADJUSTMENT',
            ]);
            $table->enum('direction', ['credit', 'debit']);
            $table->decimal('amount', 16, 2); // always positive
            $table->string('currency', 3)->default('ZWL');

            // Polymorphic-ish source reference (order, payout, top-up, admin user).
            $table->string('source_type')->nullable();
            $table->uuid('source_id')->nullable();

            $table->string('idempotency_key')->nullable()->unique();
            $table->uuid('created_by')->nullable();
            $table->text('description')->nullable();
            $table->timestamp('created_at')->useCurrent();

            $table->foreign('wallet_id')->references('id')->on('vendor_wallets')->cascadeOnDelete();
            $table->foreign('created_by')->references('id')->on('users')->nullOnDelete();

            $table->index(['wallet_id', 'created_at']);
            $table->index(['source_type', 'source_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('wallet_ledger_entries');
    }
};
