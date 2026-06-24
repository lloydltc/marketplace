<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * H7: opt-in email alerts for saved searches. notify toggles the alert;
     * last_notified_at marks the high-water mark so we only ever email a buyer
     * about listings that appeared since the previous digest (dedupe).
     */
    public function up(): void
    {
        Schema::table('saved_searches', function (Blueprint $table) {
            $table->boolean('notify')->default(false)->after('query_params');
            $table->timestamp('last_notified_at')->nullable()->after('notify');
        });
    }

    public function down(): void
    {
        Schema::table('saved_searches', function (Blueprint $table) {
            $table->dropColumn(['notify', 'last_notified_at']);
        });
    }
};
