<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Vehicles are lead-gen (no online checkout), so sellers may price in USD or
     * ZWL — not forced into ZWL. Make price_zwl nullable; validation enforces that
     * at least one currency is provided.
     */
    public function up(): void
    {
        Schema::table('vehicles', function (Blueprint $table) {
            $table->decimal('price_zwl', 12, 2)->nullable()->change();
        });
    }

    public function down(): void
    {
        Schema::table('vehicles', function (Blueprint $table) {
            $table->decimal('price_zwl', 12, 2)->nullable(false)->change();
        });
    }
};
