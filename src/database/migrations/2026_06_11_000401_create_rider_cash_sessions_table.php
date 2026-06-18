<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Phase 14: end-of-day rider cash-in. COD cash collected on FBS deliveries is
     * reconciled here against what was expected BEFORE those orders settle — this
     * is the gate that structurally guarantees commission capture on FBS-COD.
     */
    public function up(): void
    {
        Schema::create('rider_cash_sessions', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('rider_id');
            $table->date('session_date');
            $table->decimal('expected_total', 14, 2)->default(0);
            $table->decimal('collected_total', 14, 2)->nullable();
            $table->string('status', 20)->default('open'); // open|reconciled|discrepancy
            $table->uuid('reconciled_by')->nullable();
            $table->timestamp('reconciled_at')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->foreign('rider_id')->references('id')->on('users')->cascadeOnDelete();
            $table->foreign('reconciled_by')->references('id')->on('users')->nullOnDelete();

            $table->unique(['rider_id', 'session_date']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('rider_cash_sessions');
    }
};
