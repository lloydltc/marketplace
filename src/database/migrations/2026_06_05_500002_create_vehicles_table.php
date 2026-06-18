<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('vehicles', function (Blueprint $table) {
            $table->uuid('id')->primary();
            // Exactly one of vendor_id / user_id must be set (enforced by check constraint below)
            $table->uuid('vendor_id')->nullable();
            $table->uuid('user_id')->nullable();
            $table->uuid('make_id');
            $table->uuid('model_id');
            $table->integer('year');
            $table->string('body_type', 50);
            $table->enum('transmission', ['manual', 'automatic', 'cvt']);
            $table->enum('fuel_type', ['petrol', 'diesel', 'electric', 'hybrid']);
            $table->integer('engine_cc')->nullable();
            $table->integer('mileage')->default(0);
            $table->string('vin', 17)->nullable()->unique();
            $table->string('color', 50);
            $table->enum('condition', ['new', 'used', 'salvage', 'rebuilt'])->default('used');
            $table->enum('status', ['pending', 'active', 'inactive', 'rejected'])->default('pending');
            $table->decimal('price_zwl', 12, 2);
            $table->decimal('price_usd', 12, 2)->nullable();
            $table->text('description')->nullable();
            $table->decimal('rating', 3, 2)->default(0);
            $table->integer('review_count')->default(0);
            $table->timestamps();
            $table->softDeletes();

            $table->foreign('vendor_id')->references('id')->on('vendors')->nullOnDelete();
            $table->foreign('user_id')->references('id')->on('users')->nullOnDelete();
            $table->foreign('make_id')->references('id')->on('vehicle_makes')->restrictOnDelete();
            $table->foreign('model_id')->references('id')->on('vehicle_models')->restrictOnDelete();

            $table->index('vendor_id');
            $table->index('user_id');
            $table->index('make_id');
            $table->index(['status', 'created_at']);
            $table->index(['year', 'make_id', 'model_id']);
            $table->index(['condition', 'status']);
        });

        // Enforce that exactly one owner (vendor OR private seller) is always set
        DB::statement(
            'ALTER TABLE vehicles ADD CONSTRAINT vehicles_owner_check
             CHECK (
                 (vendor_id IS NOT NULL AND user_id IS NULL) OR
                 (vendor_id IS NULL AND user_id IS NOT NULL)
             )'
        );
    }

    public function down(): void
    {
        Schema::dropIfExists('vehicles');
    }
};
