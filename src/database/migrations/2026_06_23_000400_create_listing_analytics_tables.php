<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * H5: per-listing analytics. Raw events are deduped at insert (one per
     * visitor/type/listing/day) and bot-filtered; a daily job rolls them into
     * listing_daily_stats and prunes the raw table. Counts can't be gamed by
     * refreshes or crawlers — that integrity is the car-side product value.
     */
    public function up(): void
    {
        Schema::create('listing_events', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('subject_type');
            $table->uuid('subject_id');
            $table->uuid('seller_user_id')->nullable(); // denormalised owner for scoping
            $table->uuid('vendor_id')->nullable();
            $table->string('type', 30); // detail_view | phone_reveal | call_click | whatsapp_click | enquiry
            $table->string('visitor_hash', 64);
            $table->date('occurred_on');
            $table->timestamp('created_at')->nullable();

            // Dedupe: one event per visitor, per type, per listing, per day.
            $table->unique(['subject_id', 'type', 'visitor_hash', 'occurred_on'], 'listing_events_dedupe');
            $table->index(['seller_user_id', 'occurred_on']);
            $table->index(['vendor_id', 'occurred_on']);
        });

        Schema::create('listing_daily_stats', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('subject_type');
            $table->uuid('subject_id');
            $table->uuid('seller_user_id')->nullable();
            $table->uuid('vendor_id')->nullable();
            $table->date('stat_date');
            $table->string('type', 30);
            $table->unsignedInteger('count')->default(0);
            $table->timestamps();

            $table->unique(['subject_id', 'type', 'stat_date'], 'listing_daily_stats_unique');
            $table->index(['seller_user_id', 'stat_date']);
            $table->index(['vendor_id', 'stat_date']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('listing_daily_stats');
        Schema::dropIfExists('listing_events');
    }
};
