<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // tier column already exists on vendors from Phase 3 initial schema
        if (!Schema::hasColumn('vendors', 'tier')) {
            Schema::table('vendors', function (Blueprint $table) {
                $table->string('tier', 20)->default('unverified')->after('status');
            });
        }
    }

    public function down(): void
    {
        // intentionally left blank — column was created in the initial schema
    }
};
