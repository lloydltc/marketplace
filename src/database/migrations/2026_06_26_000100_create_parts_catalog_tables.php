<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * PM1: the canonical parts catalog. A `part` is the real-world part authored
     * once (specs, OEM numbers, fitment later, guides, warranty). Vendor sellable
     * listings (offerings) link to it from the existing `products` table (PM2).
     * Categories reuse the existing hierarchical `categories` table.
     */
    public function up(): void
    {
        Schema::create('parts', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('slug')->unique();
            $table->string('name');
            $table->string('brand')->nullable();
            $table->uuid('category_id')->nullable();
            $table->string('primary_oem')->nullable();
            $table->text('description')->nullable();
            $table->unsignedSmallInteger('warranty_months')->nullable();
            $table->text('warranty_terms')->nullable();
            $table->boolean('is_universal')->default(false);
            $table->string('status')->default('active');   // active | inactive
            $table->timestamps();
            $table->softDeletes();

            $table->foreign('category_id')->references('id')->on('categories')->nullOnDelete();
            $table->index('category_id');
            $table->index('status');
            $table->index('primary_oem');
        });

        Schema::create('part_oem_numbers', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('part_id');
            $table->string('number');
            $table->string('type')->default('oem');        // oem | aftermarket | cross_ref
            $table->string('brand')->nullable();
            $table->timestamps();

            $table->foreign('part_id')->references('id')->on('parts')->cascadeOnDelete();
            $table->index('part_id');
            $table->index('number');
            $table->unique(['part_id', 'number', 'type']);
        });

        Schema::create('part_alternatives', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('part_id');
            $table->uuid('alternative_part_id');
            $table->string('relation')->default('substitute'); // oem_equivalent | substitute | upgrade
            $table->timestamps();

            $table->foreign('part_id')->references('id')->on('parts')->cascadeOnDelete();
            $table->foreign('alternative_part_id')->references('id')->on('parts')->cascadeOnDelete();
            $table->unique(['part_id', 'alternative_part_id']);
        });

        Schema::create('part_guides', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('part_id');
            $table->string('title');
            $table->string('type')->default('doc');        // doc | video
            $table->string('url')->nullable();
            $table->text('content')->nullable();
            $table->integer('sort_order')->default(0);
            $table->timestamps();

            $table->foreign('part_id')->references('id')->on('parts')->cascadeOnDelete();
            $table->index('part_id');
        });

        Schema::create('part_media', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('part_id');
            $table->string('disk')->default('public');
            $table->string('path');
            $table->boolean('is_primary')->default(false);
            $table->integer('sort_order')->default(0);
            $table->timestamps();

            $table->foreign('part_id')->references('id')->on('parts')->cascadeOnDelete();
            $table->index(['part_id', 'sort_order']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('part_media');
        Schema::dropIfExists('part_guides');
        Schema::dropIfExists('part_alternatives');
        Schema::dropIfExists('part_oem_numbers');
        Schema::dropIfExists('parts');
    }
};
