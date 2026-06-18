<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Phase 15: public "Request a Part" (BUSINESS_MODEL.md §6 Tier 1). A buyer
     * posts a structured request; vendors quote; accepting a quote converts the
     * request into a normal order (standard commission engine applies).
     */
    public function up(): void
    {
        Schema::create('part_requests', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('buyer_user_id');

            // Vehicle context (optional).
            $table->uuid('make_id')->nullable();
            $table->uuid('model_id')->nullable();
            $table->integer('year')->nullable();

            $table->text('part_description');
            $table->decimal('budget_min', 14, 2)->nullable();
            $table->decimal('budget_max', 14, 2)->nullable();
            $table->string('location');
            $table->decimal('estimated_value', 14, 2)->nullable();

            // open → quoted → converted → closed / expired
            $table->string('status', 20)->default('open');
            // approved | rejected (spam/abuse moderation)
            $table->string('moderation_status', 20)->default('approved');

            $table->uuid('accepted_quote_id')->nullable();
            $table->uuid('converted_order_id')->nullable();
            $table->timestamp('expires_at')->nullable();
            $table->timestamps();

            $table->foreign('buyer_user_id')->references('id')->on('users')->cascadeOnDelete();
            $table->foreign('make_id')->references('id')->on('vehicle_makes')->nullOnDelete();
            $table->foreign('model_id')->references('id')->on('vehicle_models')->nullOnDelete();
            $table->foreign('converted_order_id')->references('id')->on('orders')->nullOnDelete();

            $table->index(['status', 'moderation_status']);
            $table->index('buyer_user_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('part_requests');
    }
};
