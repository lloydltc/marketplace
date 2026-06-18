<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * 7R.5: vehicle listings are lead-gen only (no checkout/commission). They are
     * monetised via paid promotion (BUSINESS_MODEL.md §8). These fields hold the
     * promotion state; the purchase flow arrives in Phase 17.
     *
     * seller_verified_badge leverages Phase 3 document verification.
     */
    public function up(): void
    {
        Schema::table('vehicles', function (Blueprint $table) {
            $table->timestamp('featured_until')->nullable()->after('status');
            $table->timestamp('bumped_at')->nullable()->after('featured_until');
            $table->uuid('listing_package_id')->nullable()->after('bumped_at');
            $table->boolean('seller_verified_badge')->default(false)->after('listing_package_id');

            // Supports "featured first, then bumped" ranking in Phase 8.
            $table->index(['featured_until', 'bumped_at']);
        });
    }

    public function down(): void
    {
        Schema::table('vehicles', function (Blueprint $table) {
            $table->dropIndex(['featured_until', 'bumped_at']);
            $table->dropColumn(['featured_until', 'bumped_at', 'listing_package_id', 'seller_verified_badge']);
        });
    }
};
