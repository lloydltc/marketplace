<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * H8: paid "featured dealer" placement. While featured_until is in the future
     * the dealer surfaces in the featured-dealer carousel (config-driven count).
     */
    public function up(): void
    {
        Schema::table('vendors', function (Blueprint $table) {
            $table->timestamp('featured_until')->nullable()->after('tier');
        });
    }

    public function down(): void
    {
        Schema::table('vendors', function (Blueprint $table) {
            $table->dropColumn('featured_until');
        });
    }
};
