<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * 7R.2: database-backed, admin-editable, cached settings store.
     * The single source of truth for every fee, threshold, and limit
     * (BUSINESS_MODEL.md golden rule: no monetary value is ever hardcoded).
     */
    public function up(): void
    {
        Schema::create('platform_settings', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('key')->unique();
            $table->text('value')->nullable();
            // How SettingsService should cast the stored string value.
            $table->enum('type', ['string', 'integer', 'decimal', 'boolean', 'json'])
                ->default('string');
            $table->string('group')->default('general')->index();
            $table->text('description')->nullable();
            $table->uuid('updated_by')->nullable();
            $table->timestamps();

            $table->foreign('updated_by')->references('id')->on('users')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('platform_settings');
    }
};
