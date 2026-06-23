<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Products are priced in USD (mandatory) with a seller-set USD→ZWL exchange
     * rate; the ZWL price the engine settles in is derived (price_usd × rate) and
     * stored in the existing price_zwl column. Sellers no longer enter ZWL directly.
     */
    public function up(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->decimal('exchange_rate', 14, 4)->nullable()->after('price_usd');
        });
    }

    public function down(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->dropColumn('exchange_rate');
        });
    }
};
