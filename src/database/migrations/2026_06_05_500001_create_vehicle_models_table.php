<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('vehicle_models', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('make_id');
            $table->string('name', 100);
            $table->string('slug', 110);
            $table->timestamps();

            $table->foreign('make_id')->references('id')->on('vehicle_makes')->cascadeOnDelete();
            $table->index('make_id');
            $table->unique(['make_id', 'slug']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('vehicle_models');
    }
};
