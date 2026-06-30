<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * HR1: vehicle history reports. A report is assembled from pluggable data
     * sources; each section records its source, confidence, provenance and when it
     * was retrieved — so nothing is ever presented without honest attribution.
     */
    public function up(): void
    {
        Schema::create('history_data_sources', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('key')->unique();          // import | platform | service | odometer | registration | ...
            $table->string('name');
            $table->string('type');                   // section type this source feeds
            $table->string('adapter')->nullable();    // adapter class
            $table->string('status')->default('unavailable'); // live | manual | unavailable
            $table->json('config')->nullable();
            $table->timestamps();
        });

        Schema::create('history_reports', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('vehicle_id')->nullable();   // when keyed to a live listing
            $table->string('vin')->nullable();
            $table->string('plate')->nullable();
            $table->uuid('requested_by')->nullable(); // buyer (null = guest preview)
            $table->string('status')->default('draft'); // draft | ready | purchased | refunded
            $table->unsignedInteger('price_minor')->default(0); // USD cents at purchase time
            $table->string('currency', 3)->default('USD');
            $table->string('payment_reference')->nullable();
            $table->timestamp('purchased_at')->nullable();
            $table->timestamp('refunded_at')->nullable();
            $table->timestamps();

            $table->foreign('vehicle_id')->references('id')->on('vehicles')->nullOnDelete();
            $table->foreign('requested_by')->references('id')->on('users')->nullOnDelete();
            $table->index(['vehicle_id', 'status']);
            $table->index('requested_by');
        });

        Schema::create('history_report_sections', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('report_id');
            $table->string('source');                 // data source key
            $table->string('type');                   // import | ownership | service | odometer | ...
            $table->string('availability')->default('available'); // available | manual | unavailable
            $table->json('data')->nullable();
            $table->string('confidence')->default('medium'); // high | medium | low
            $table->string('provenance')->nullable(); // e.g. "Seller-declared", "SalmaDrive records"
            $table->timestamp('retrieved_at')->nullable();
            $table->unsignedSmallInteger('sort_order')->default(0);
            $table->timestamps();

            $table->foreign('report_id')->references('id')->on('history_reports')->cascadeOnDelete();
            $table->index('report_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('history_report_sections');
        Schema::dropIfExists('history_reports');
        Schema::dropIfExists('history_data_sources');
    }
};
