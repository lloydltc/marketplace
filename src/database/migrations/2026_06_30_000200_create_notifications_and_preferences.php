<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * AC1: the shared notification spine — Laravel's database channel table for the
     * in-app inbox, plus per-user channel preferences keyed by notification type.
     * Reused by every later phase (alerts, reports, inspection, trade-in).
     */
    public function up(): void
    {
        // Standard Laravel "database" channel table (UUID notifiable to match users).
        Schema::create('notifications', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('type');
            $table->string('notifiable_type');
            $table->uuid('notifiable_id');
            $table->json('data');
            $table->timestamp('read_at')->nullable();
            $table->timestamps();

            $table->index(['notifiable_type', 'notifiable_id']);
        });

        // Per-user, per-type, per-channel toggle. Absence ⇒ fall back to config default.
        Schema::create('notification_preferences', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('user_id');
            $table->string('type');        // alert.new_match | alert.price_drop | ...
            $table->string('channel');     // in_app | email | push
            $table->boolean('enabled')->default(true);
            $table->timestamps();

            $table->foreign('user_id')->references('id')->on('users')->cascadeOnDelete();
            $table->unique(['user_id', 'type', 'channel']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('notification_preferences');
        Schema::dropIfExists('notifications');
    }
};
