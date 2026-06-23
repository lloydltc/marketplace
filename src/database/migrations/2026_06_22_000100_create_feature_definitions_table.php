<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * D4: admin-managed, dynamic vehicle feature/spec definitions. Structured
     * (typed) rather than free-form so D3 filters can consume the filterable ones
     * and buyers can compare them consistently. No feature is hardcoded in app code.
     */
    public function up(): void
    {
        Schema::create('feature_definitions', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('name');                          // "Parking Sensors"
            $table->string('key')->unique();                 // "parking_sensors"
            $table->string('type', 20);                      // boolean | number | enum | text
            $table->string('unit')->nullable();              // "doors", "seats", "L"
            $table->jsonb('options')->nullable();            // enum choices
            $table->boolean('is_filterable')->default(false);
            $table->string('group')->nullable();             // display grouping, e.g. "Safety"
            $table->integer('sort_order')->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->index(['is_active', 'is_filterable']);
            $table->index(['group', 'sort_order']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('feature_definitions');
    }
};
