<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * PM7: My Garage — a buyer's saved vehicles. Activating one drives the fitment
     * context everywhere ("shop parts for my Hilux"). Retention hook.
     */
    public function up(): void
    {
        Schema::create('user_garage_vehicles', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('user_id');
            $table->uuid('make_id');
            $table->uuid('model_id');
            $table->unsignedSmallInteger('year')->nullable();
            $table->uuid('variant_id')->nullable();
            $table->uuid('engine_id')->nullable();
            $table->uuid('transmission_id')->nullable();
            $table->string('nickname')->nullable();
            $table->boolean('is_default')->default(false);
            $table->timestamps();

            $table->foreign('user_id')->references('id')->on('users')->cascadeOnDelete();
            $table->foreign('make_id')->references('id')->on('vehicle_makes')->cascadeOnDelete();
            $table->foreign('model_id')->references('id')->on('vehicle_models')->cascadeOnDelete();
            $table->foreign('variant_id')->references('id')->on('vehicle_variants')->nullOnDelete();
            $table->foreign('engine_id')->references('id')->on('vehicle_engines')->nullOnDelete();
            $table->foreign('transmission_id')->references('id')->on('vehicle_transmissions')->nullOnDelete();

            $table->index('user_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('user_garage_vehicles');
    }
};
