<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * TI1/TI2: trade-in submissions (with a comparable-listing valuation range) +
     * dealer bids. Manual-first — an admin can shepherd via the ops queue.
     */
    public function up(): void
    {
        Schema::create('trade_ins', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('user_id');
            $table->uuid('make_id')->nullable();
            $table->uuid('model_id')->nullable();
            $table->uuid('generation_id')->nullable();
            $table->unsignedSmallInteger('year');
            $table->unsignedInteger('mileage');
            $table->string('condition');                 // excellent | good | fair | poor
            $table->text('notes')->nullable();
            $table->unsignedBigInteger('estimate_low_minor')->nullable();
            $table->unsignedBigInteger('estimate_high_minor')->nullable();
            $table->unsignedSmallInteger('comparables_count')->default(0);
            $table->string('currency', 3)->default('USD');
            $table->string('status', 20)->default('new'); // new | valued | bidding | accepted | closed | cancelled
            $table->uuid('accepted_offer_id')->nullable();
            $table->timestamps();

            $table->foreign('user_id')->references('id')->on('users')->cascadeOnDelete();
            $table->foreign('make_id')->references('id')->on('vehicle_makes')->nullOnDelete();
            $table->foreign('model_id')->references('id')->on('vehicle_models')->nullOnDelete();
            $table->index(['user_id', 'status']);
            $table->index('status');
        });

        Schema::create('trade_in_photos', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('trade_in_id');
            $table->string('disk')->default('public');
            $table->string('path');
            $table->timestamps();

            $table->foreign('trade_in_id')->references('id')->on('trade_ins')->cascadeOnDelete();
        });

        Schema::create('trade_in_offers', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('trade_in_id');
            $table->uuid('vendor_id');
            $table->unsignedBigInteger('amount_minor');
            $table->string('currency', 3)->default('USD');
            $table->text('notes')->nullable();
            $table->string('status', 20)->default('offered'); // offered | accepted | declined | expired | withdrawn
            $table->timestamp('expires_at')->nullable();
            $table->timestamps();

            $table->foreign('trade_in_id')->references('id')->on('trade_ins')->cascadeOnDelete();
            $table->foreign('vendor_id')->references('id')->on('vendors')->cascadeOnDelete();
            $table->unique(['trade_in_id', 'vendor_id']);
            $table->index(['vendor_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('trade_in_offers');
        Schema::dropIfExists('trade_in_photos');
        Schema::dropIfExists('trade_ins');
    }
};
