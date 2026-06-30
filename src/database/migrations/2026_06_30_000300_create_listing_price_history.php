<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * AC2: records listing price changes so price-drop alerts have a deterministic
     * source. alerted_at marks rows already turned into alerts (dedupe across runs).
     */
    public function up(): void
    {
        Schema::create('listing_price_history', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('subject_type');
            $table->uuid('subject_id');
            $table->decimal('old_price', 14, 2)->nullable();
            $table->decimal('new_price', 14, 2);
            $table->string('currency', 3)->default('USD');
            $table->boolean('is_drop')->default(false);
            $table->timestamp('alerted_at')->nullable();
            $table->timestamp('created_at')->nullable();

            $table->index(['subject_type', 'subject_id']);
            $table->index(['is_drop', 'alerted_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('listing_price_history');
    }
};
