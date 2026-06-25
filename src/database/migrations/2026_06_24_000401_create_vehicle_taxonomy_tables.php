<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * PM0: the deeper taxonomy that parts fitment narrows on —
     * make → model → generation → variant, plus engine/transmission lookups.
     * All independent of (and additive to) the existing make/model + vehicle flow.
     */
    public function up(): void
    {
        Schema::create('vehicle_generations', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('model_id');
            $table->string('name');                          // e.g. "Revo"
            $table->unsignedSmallInteger('year_start')->nullable();
            $table->unsignedSmallInteger('year_end')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->foreign('model_id')->references('id')->on('vehicle_models')->cascadeOnDelete();
            $table->index(['model_id', 'year_start', 'year_end']);
        });

        Schema::create('vehicle_variants', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('model_id');
            $table->uuid('generation_id')->nullable();
            $table->string('name');                          // e.g. "2.8 GD-6"
            $table->string('body_type')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->foreign('model_id')->references('id')->on('vehicle_models')->cascadeOnDelete();
            $table->foreign('generation_id')->references('id')->on('vehicle_generations')->nullOnDelete();
            $table->index('model_id');
        });

        Schema::create('vehicle_engines', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('code');                          // e.g. "2.8 GD-6"
            $table->string('displacement')->nullable();      // e.g. "2.8L"
            $table->string('fuel_type')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->unique('code');
        });

        Schema::create('vehicle_transmissions', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('type');                          // manual / automatic / cvt / dct
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->unique('type');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('vehicle_variants');
        Schema::dropIfExists('vehicle_generations');
        Schema::dropIfExists('vehicle_engines');
        Schema::dropIfExists('vehicle_transmissions');
    }
};
