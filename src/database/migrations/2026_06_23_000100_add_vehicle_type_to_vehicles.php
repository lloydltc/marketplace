<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * H0: listing type (vehicle/motorbike/boat/trailer). Type drives which
     * body-types, specs, and features apply. Additive — existing rows backfill to
     * 'vehicle' via the column default.
     */
    public function up(): void
    {
        Schema::table('vehicles', function (Blueprint $table) {
            $table->string('vehicle_type', 20)->default('vehicle')->after('user_id');
            $table->index(['vehicle_type', 'status']);
        });
    }

    public function down(): void
    {
        Schema::table('vehicles', function (Blueprint $table) {
            $table->dropIndex(['vehicle_type', 'status']);
            $table->dropColumn('vehicle_type');
        });
    }
};
