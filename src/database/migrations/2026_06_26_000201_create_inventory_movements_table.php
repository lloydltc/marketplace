<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * PM2: an auditable stock trail per offering (product). Every change to
     * products.quantity goes through InventoryService, which writes one row here
     * with the resulting balance — so stock is always reconstructable.
     */
    public function up(): void
    {
        Schema::create('inventory_movements', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('product_id');
            $table->string('type');                 // restock | sale | reserve | release | adjustment
            $table->integer('qty');                 // signed: +restock/release, -sale/reserve
            $table->integer('balance_after');       // resulting quantity (never negative)
            $table->string('reference')->nullable(); // e.g. order id, note
            $table->uuid('created_by')->nullable();
            $table->timestamp('created_at')->nullable();

            $table->foreign('product_id')->references('id')->on('products')->cascadeOnDelete();
            $table->foreign('created_by')->references('id')->on('users')->nullOnDelete();
            $table->index(['product_id', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('inventory_movements');
    }
};
