<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * VB1: per-dimension verification decisions for a vendor (the documents/bank
     * rows are evidence; this records the admin decision per dimension, with
     * expiry for re-verification). Plus the computed badge tier + manual grant
     * on the vendor for cheap rendering and ranking.
     */
    public function up(): void
    {
        Schema::create('vendor_verifications', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('vendor_id');
            $table->string('dimension');                 // company_reg | tax | location | identity | banking
            $table->string('status')->default('pending'); // pending | approved | rejected
            $table->string('evidence_ref')->nullable();   // e.g. VendorDocument id
            $table->text('notes')->nullable();
            $table->uuid('verified_by')->nullable();
            $table->timestamp('verified_at')->nullable();
            $table->timestamp('expires_at')->nullable();
            $table->timestamps();

            $table->foreign('vendor_id')->references('id')->on('vendors')->cascadeOnDelete();
            $table->foreign('verified_by')->references('id')->on('users')->nullOnDelete();
            $table->unique(['vendor_id', 'dimension']);
            $table->index(['vendor_id', 'status']);
        });

        Schema::table('vendors', function (Blueprint $table) {
            $table->string('verification_tier')->nullable()->after('tier'); // computed primary badge tier
            $table->string('manual_tier')->nullable()->after('verification_tier'); // admin-granted (e.g. manufacturer_authorized)
            // Cached reputation score (0–100); the detailed breakdown lives in
            // vendor_reputation (VB3). Kept here for fast tier eval + ranking.
            $table->unsignedTinyInteger('reputation_score')->default(0)->after('manual_tier');
        });
    }

    public function down(): void
    {
        Schema::table('vendors', function (Blueprint $table) {
            $table->dropColumn(['verification_tier', 'manual_tier', 'reputation_score']);
        });
        Schema::dropIfExists('vendor_verifications');
    }
};
