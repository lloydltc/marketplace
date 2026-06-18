<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Phase 17: dealer package catalog (BUSINESS_MODEL.md §8). A monthly bundle a
     * vendor buys for X listing credits + Y feature/bump credits.
     */
    public function up(): void
    {
        Schema::create('promotion_packages', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('name');
            $table->decimal('price', 14, 2);
            $table->string('currency', 3)->default('ZWL');
            $table->integer('listing_credits')->default(0);
            $table->integer('feature_credits')->default(0);
            $table->integer('bump_credits')->default(0);
            $table->integer('duration_days')->default(30);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('promotion_packages');
    }
};
