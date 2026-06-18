<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Phase 12: lifecycle timestamps for the order state machine.
     * settled_at guards the settlement event so a completed order emits it
     * exactly once (consumed by the Phase 13 wallet).
     */
    public function up(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->timestamp('completed_at')->nullable()->after('paid_at');
            $table->timestamp('cancelled_at')->nullable()->after('completed_at');
            $table->timestamp('settled_at')->nullable()->after('cancelled_at');
            $table->text('cancellation_reason')->nullable()->after('settled_at');
        });
    }

    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->dropColumn(['completed_at', 'cancelled_at', 'settled_at', 'cancellation_reason']);
        });
    }
};
