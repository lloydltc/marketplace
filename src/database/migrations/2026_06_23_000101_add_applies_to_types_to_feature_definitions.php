<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * H0/H2: scope feature definitions to listing types. NULL = applies to all
     * types; otherwise a JSON array of types (e.g. ["vehicle","motorbike"]).
     */
    public function up(): void
    {
        Schema::table('feature_definitions', function (Blueprint $table) {
            $table->jsonb('applies_to_types')->nullable()->after('options');
        });
    }

    public function down(): void
    {
        Schema::table('feature_definitions', function (Blueprint $table) {
            $table->dropColumn('applies_to_types');
        });
    }
};
