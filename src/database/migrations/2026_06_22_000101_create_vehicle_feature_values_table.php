<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * D4: a vehicle's value for a given feature definition. Stored as a string and
     * cast on read per the definition's type. Indexed by (definition, value) so
     * D3 facet filters are efficient.
     */
    public function up(): void
    {
        Schema::create('vehicle_feature_values', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('vehicle_id');
            $table->uuid('feature_definition_id');
            $table->string('value');
            $table->timestamps();

            $table->foreign('vehicle_id')->references('id')->on('vehicles')->cascadeOnDelete();
            $table->foreign('feature_definition_id')->references('id')->on('feature_definitions')->cascadeOnDelete();

            $table->unique(['vehicle_id', 'feature_definition_id']);
            $table->index(['feature_definition_id', 'value']); // facet filtering
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('vehicle_feature_values');
    }
};
