<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * H11: buyer reports + rule-based auto-flags against listings (vehicles and
     * products), feeding the admin moderation queue. Polymorphic so one queue
     * covers every listing type.
     */
    public function up(): void
    {
        Schema::create('listing_reports', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('reportable_type');
            $table->uuid('reportable_id');
            $table->uuid('reporter_user_id')->nullable();   // null for guests / auto rules
            $table->string('reporter_ip', 45)->nullable();
            $table->string('source')->default('user');      // 'user' | 'auto'
            $table->string('reason');
            $table->text('note')->nullable();
            $table->string('status')->default('open');       // 'open' | 'actioned' | 'dismissed'
            $table->uuid('resolved_by')->nullable();
            $table->timestamp('resolved_at')->nullable();
            $table->text('resolution_note')->nullable();
            $table->timestamps();

            $table->foreign('reporter_user_id')->references('id')->on('users')->nullOnDelete();
            $table->foreign('resolved_by')->references('id')->on('users')->nullOnDelete();

            $table->index(['reportable_type', 'reportable_id']);
            $table->index('status');
            // Dedupe support for auto rules: one open auto-report per listing+reason.
            $table->index(['reportable_type', 'reportable_id', 'reason', 'source', 'status'], 'listing_reports_dedupe');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('listing_reports');
    }
};
