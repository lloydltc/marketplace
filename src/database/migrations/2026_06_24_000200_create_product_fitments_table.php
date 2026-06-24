<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * H10: declares which vehicles a part fits, for parts ⇄ vehicle cross-sell.
     * A null make/model/year bound means "any" on that axis, so a fitment can be
     * as broad as "any Toyota" or as narrow as "2015–2018 Toyota Hilux".
     */
    public function up(): void
    {
        Schema::create('product_fitments', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('product_id');
            $table->uuid('make_id')->nullable();
            $table->uuid('model_id')->nullable();
            $table->unsignedSmallInteger('year_from')->nullable();
            $table->unsignedSmallInteger('year_to')->nullable();
            $table->timestamps();

            $table->foreign('product_id')->references('id')->on('products')->cascadeOnDelete();
            $table->foreign('make_id')->references('id')->on('vehicle_makes')->nullOnDelete();
            $table->foreign('model_id')->references('id')->on('vehicle_models')->nullOnDelete();

            $table->index('product_id');
            $table->index(['make_id', 'model_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('product_fitments');
    }
};
