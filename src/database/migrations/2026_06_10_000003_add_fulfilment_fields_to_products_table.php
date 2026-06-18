<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * 7R.4: products declare which fulfilment track(s) they support and whether
     * COD is allowed. fulfilment_type is indexed to support the FBS placement
     * boost in search (Phase 8).
     */
    public function up(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->enum('fulfilment_type', ['fbs', 'vendor', 'both'])
                ->default('vendor')
                ->after('status');

            $table->boolean('cod_allowed')
                ->default(false)
                ->after('fulfilment_type');

            $table->index('fulfilment_type');
        });
    }

    public function down(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->dropIndex(['fulfilment_type']);
            $table->dropColumn(['fulfilment_type', 'cod_allowed']);
        });
    }
};
