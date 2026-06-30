<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * VB4: badge revocation on vendors + perceptual/content hashes on listing
     * images for deterministic duplicate/stolen-photo detection (no AI).
     */
    public function up(): void
    {
        Schema::table('vendors', function (Blueprint $table) {
            $table->timestamp('badge_revoked_at')->nullable()->after('reputation_score');
            $table->string('badge_revoked_reason')->nullable()->after('badge_revoked_at');
        });

        foreach (['vehicle_images', 'product_images'] as $imageTable) {
            if (Schema::hasTable($imageTable) && ! Schema::hasColumn($imageTable, 'image_hash')) {
                Schema::table($imageTable, function (Blueprint $table) {
                    $table->string('image_hash', 64)->nullable()->index();
                });
            }
        }
    }

    public function down(): void
    {
        Schema::table('vendors', function (Blueprint $table) {
            $table->dropColumn(['badge_revoked_at', 'badge_revoked_reason']);
        });
        foreach (['vehicle_images', 'product_images'] as $imageTable) {
            if (Schema::hasColumn($imageTable, 'image_hash')) {
                Schema::table($imageTable, function (Blueprint $table) {
                    $table->dropColumn('image_hash');
                });
            }
        }
    }
};
