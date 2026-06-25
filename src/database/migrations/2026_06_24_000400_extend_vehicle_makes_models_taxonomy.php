<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * PM0: additive columns on the existing make/model taxonomy. Nullable/defaulted
     * so existing rows and the vehicle-listing flow are unaffected.
     */
    public function up(): void
    {
        Schema::table('vehicle_makes', function (Blueprint $table) {
            $table->string('logo')->nullable()->after('slug');
            $table->string('vehicle_type')->nullable()->after('logo'); // optional categorisation
            $table->boolean('is_active')->default(true)->after('sort_order');
        });

        Schema::table('vehicle_models', function (Blueprint $table) {
            $table->boolean('is_active')->default(true)->after('slug');
        });
    }

    public function down(): void
    {
        Schema::table('vehicle_makes', function (Blueprint $table) {
            $table->dropColumn(['logo', 'vehicle_type', 'is_active']);
        });
        Schema::table('vehicle_models', function (Blueprint $table) {
            $table->dropColumn('is_active');
        });
    }
};
