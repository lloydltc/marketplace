<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * PM6: service-kit bundles — a curated set of offerings sold together (e.g.
     * oil + oil filter + air filter). Components reference concrete offerings
     * (products) so stock/price stay authoritative. "Add kit to cart" expands the
     * bundle into its component cart lines, so the existing cart/checkout/
     * commission/wallet spine handles it UNCHANGED.
     */
    public function up(): void
    {
        Schema::create('part_bundles', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('vendor_id')->nullable();
            $table->string('name');
            $table->string('slug')->unique();
            $table->text('description')->nullable();
            $table->decimal('price_usd', 12, 2)->nullable();  // optional set price (else summed)
            $table->boolean('is_service_kit')->default(true);
            $table->string('status')->default('active');       // active | inactive
            $table->timestamps();
            $table->softDeletes();

            $table->foreign('vendor_id')->references('id')->on('vendors')->nullOnDelete();
            $table->index('status');
        });

        Schema::create('part_bundle_items', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('bundle_id');
            $table->uuid('product_id');                         // the offering
            $table->unsignedInteger('qty')->default(1);
            $table->timestamps();

            $table->foreign('bundle_id')->references('id')->on('part_bundles')->cascadeOnDelete();
            $table->foreign('product_id')->references('id')->on('products')->cascadeOnDelete();
            $table->unique(['bundle_id', 'product_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('part_bundle_items');
        Schema::dropIfExists('part_bundles');
    }
};
