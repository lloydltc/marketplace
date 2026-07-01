<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * TI3/TI4: a vetted inspector panel + paid inspection bookings with a
     * standardized report. Manual-first (admin dispatch); an inspector portal is
     * available to a linked user.
     */
    public function up(): void
    {
        Schema::create('inspectors', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('user_id')->nullable();       // linked login for the inspector portal
            $table->string('name');
            $table->string('kind')->default('mechanic'); // company | mechanic | expert
            $table->string('coverage_area')->nullable();
            $table->string('phone')->nullable();
            $table->string('email')->nullable();
            $table->decimal('rating', 3, 2)->default(0);
            $table->unsignedInteger('review_count')->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->foreign('user_id')->references('id')->on('users')->nullOnDelete();
            $table->index('is_active');
        });

        Schema::create('inspections', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('buyer_id');
            $table->uuid('inspector_id')->nullable();
            $table->uuid('vehicle_id')->nullable();
            $table->string('vehicle_ref')->nullable();   // free-text when not a live listing
            $table->timestamp('scheduled_for')->nullable();
            $table->string('status', 20)->default('requested'); // requested | paid | in_progress | completed | cancelled
            $table->unsignedBigInteger('price_minor')->default(0);
            $table->string('currency', 3)->default('USD');
            $table->string('payment_reference')->nullable();
            $table->timestamp('paid_at')->nullable();
            $table->json('report')->nullable();          // {checklist:[{item,status,note}], photos:[]}
            $table->string('verdict')->nullable();       // pass | pass_with_advisories | fail
            $table->timestamp('report_submitted_at')->nullable();
            $table->unsignedTinyInteger('rating_given')->nullable();
            $table->timestamps();

            $table->foreign('buyer_id')->references('id')->on('users')->cascadeOnDelete();
            $table->foreign('inspector_id')->references('id')->on('inspectors')->nullOnDelete();
            $table->foreign('vehicle_id')->references('id')->on('vehicles')->nullOnDelete();
            $table->index(['buyer_id', 'status']);
            $table->index(['inspector_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('inspections');
        Schema::dropIfExists('inspectors');
    }
};
