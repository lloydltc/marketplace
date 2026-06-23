<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * D5: vehicle listing lifecycle. Vehicles are lead-gen and naturally
     * time-bound, so they get published_at / expires_at and can be renewed. The
     * `expired` status is just a string value of the existing status column.
     */
    public function up(): void
    {
        Schema::table('vehicles', function (Blueprint $table) {
            $table->timestamp('published_at')->nullable()->after('status');
            $table->timestamp('expires_at')->nullable()->after('published_at');
            $table->timestamp('renewed_at')->nullable()->after('expires_at');
            $table->unsignedInteger('expiry_count')->default(0)->after('renewed_at');

            $table->index('expires_at');
        });
    }

    public function down(): void
    {
        Schema::table('vehicles', function (Blueprint $table) {
            $table->dropIndex(['expires_at']);
            $table->dropColumn(['published_at', 'expires_at', 'renewed_at', 'expiry_count']);
        });
    }
};
