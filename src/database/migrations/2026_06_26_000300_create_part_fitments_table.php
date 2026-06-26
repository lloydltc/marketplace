<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * PM3: canonical fitment — authored once on the part. make_id/model_id anchor
     * the rule; generation/variant/engine/transmission are nullable (NULL = applies
     * to all values of that dimension); year_start/year_end bound the range (NULL =
     * unbounded). A part with parts.is_universal=true needs no rows.
     */
    public function up(): void
    {
        Schema::create('part_fitments', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('part_id');
            $table->uuid('make_id');
            $table->uuid('model_id');
            $table->uuid('generation_id')->nullable();
            $table->uuid('variant_id')->nullable();
            $table->uuid('engine_id')->nullable();
            $table->uuid('transmission_id')->nullable();
            $table->unsignedSmallInteger('year_start')->nullable();
            $table->unsignedSmallInteger('year_end')->nullable();
            $table->string('notes')->nullable();
            $table->timestamps();

            $table->foreign('part_id')->references('id')->on('parts')->cascadeOnDelete();
            $table->foreign('make_id')->references('id')->on('vehicle_makes')->cascadeOnDelete();
            $table->foreign('model_id')->references('id')->on('vehicle_models')->cascadeOnDelete();
            $table->foreign('generation_id')->references('id')->on('vehicle_generations')->nullOnDelete();
            $table->foreign('variant_id')->references('id')->on('vehicle_variants')->nullOnDelete();
            $table->foreign('engine_id')->references('id')->on('vehicle_engines')->nullOnDelete();
            $table->foreign('transmission_id')->references('id')->on('vehicle_transmissions')->nullOnDelete();

            $table->index(['make_id', 'model_id', 'year_start', 'year_end']);
            $table->index('part_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('part_fitments');
    }
};
