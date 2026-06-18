<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Weekly payout batches to verified vendor bank accounts. Generation snapshots
     * the payable amount; an admin approves; the PAYOUT ledger entry is posted on
     * approval so the wallet balance reflects money leaving.
     */
    public function up(): void
    {
        Schema::create('payouts', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('vendor_id');
            $table->decimal('amount', 16, 2);
            $table->string('currency', 3)->default('ZWL');
            $table->date('period_start');
            $table->date('period_end');
            $table->uuid('bank_account_id')->nullable();
            $table->string('status', 20)->default('pending'); // pending|approved|paid|rejected
            $table->string('reference')->nullable();
            $table->uuid('approved_by')->nullable();
            $table->timestamp('approved_at')->nullable();
            $table->timestamps();

            $table->foreign('vendor_id')->references('id')->on('vendors')->cascadeOnDelete();
            $table->foreign('bank_account_id')->references('id')->on('vendor_bank_accounts')->nullOnDelete();
            $table->foreign('approved_by')->references('id')->on('users')->nullOnDelete();

            $table->index(['vendor_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('payouts');
    }
};
