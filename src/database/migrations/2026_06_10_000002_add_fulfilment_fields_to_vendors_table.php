<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * 7R.3: vendors declare a default fulfilment track and carry COD eligibility.
     * cod_eligible is derived from wallet standing (Phase 13); defaults to false
     * until the wallet system can vouch for the vendor.
     */
    public function up(): void
    {
        Schema::table('vendors', function (Blueprint $table) {
            $table->enum('default_fulfilment', ['fbs', 'vendor', 'both'])
                ->default('vendor')
                ->after('commission_rate');

            $table->boolean('cod_eligible')
                ->default(false)
                ->after('default_fulfilment');
        });
    }

    public function down(): void
    {
        Schema::table('vendors', function (Blueprint $table) {
            $table->dropColumn(['default_fulfilment', 'cod_eligible']);
        });
    }
};
