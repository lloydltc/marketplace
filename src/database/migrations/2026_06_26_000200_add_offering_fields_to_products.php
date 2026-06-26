<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * PM2: the existing `products` row IS the vendor offering. Link it to a
     * canonical part and add offering metadata. All nullable/additive — existing
     * products and the cart/checkout/order flow are unaffected.
     */
    public function up(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->uuid('part_id')->nullable()->after('vendor_id');
            $table->string('condition')->nullable()->after('quantity');           // new | used | refurb
            $table->unsignedInteger('low_stock_threshold')->nullable()->after('condition');

            $table->foreign('part_id')->references('id')->on('parts')->nullOnDelete();
            $table->index('part_id');
        });

        Schema::table('vendors', function (Blueprint $table) {
            $table->string('vendor_kind')->nullable()->after('tier'); // dealer | parts_store | manufacturer | importer
        });
    }

    public function down(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->dropForeign(['part_id']);
            $table->dropColumn(['part_id', 'condition', 'low_stock_threshold']);
        });
        Schema::table('vendors', function (Blueprint $table) {
            $table->dropColumn('vendor_kind');
        });
    }
};
