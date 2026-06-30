<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * VB3: a vendor's reputation snapshot (0–100) with the per-component breakdown.
     * The headline score is also cached on vendors.reputation_score (VB1) for fast
     * tier evaluation + ranking.
     */
    public function up(): void
    {
        Schema::create('vendor_reputation', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('vendor_id');
            $table->unsignedTinyInteger('score')->default(0);
            $table->json('components')->nullable();   // {rating, response, conversion, disputes, quality}
            $table->timestamp('computed_at')->nullable();
            $table->timestamps();

            $table->foreign('vendor_id')->references('id')->on('vendors')->cascadeOnDelete();
            $table->unique('vendor_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('vendor_reputation');
    }
};
