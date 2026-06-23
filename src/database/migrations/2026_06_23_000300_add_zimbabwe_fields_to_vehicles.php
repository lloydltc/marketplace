<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * H2: Zimbabwe-market listing fields seen on the incumbents — POA (hide price),
     * duty-paid status, recent-import flag (badge), dealer ref code, and steering
     * side (LHD/RHD, important in a RHD market with LHD imports).
     */
    public function up(): void
    {
        Schema::table('vehicles', function (Blueprint $table) {
            $table->boolean('show_price')->default(true)->after('price_usd');   // false = Price on Application
            $table->boolean('duty_paid')->nullable()->after('show_price');
            $table->boolean('is_recent_import')->default(false)->after('duty_paid');
            $table->string('ref_code', 40)->nullable()->after('is_recent_import');
            $table->string('steering', 3)->nullable()->after('ref_code');        // lhd | rhd

            $table->index('is_recent_import');
        });
    }

    public function down(): void
    {
        Schema::table('vehicles', function (Blueprint $table) {
            $table->dropIndex(['is_recent_import']);
            $table->dropColumn(['show_price', 'duty_paid', 'is_recent_import', 'ref_code', 'steering']);
        });
    }
};
