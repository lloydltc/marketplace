<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Vendor quotes on a part request. Accepting one converts the request into
     * a normal order from that vendor.
     */
    public function up(): void
    {
        Schema::create('quotes', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('part_request_id');
            $table->uuid('vendor_id');
            $table->uuid('submitted_by')->nullable();

            $table->decimal('price', 14, 2);
            $table->string('condition', 30)->default('used');
            $table->string('delivery_estimate')->nullable();
            $table->text('notes')->nullable();

            // active → accepted | rejected | expired
            $table->string('status', 20)->default('active');
            $table->timestamp('expires_at')->nullable();
            $table->timestamps();

            $table->foreign('part_request_id')->references('id')->on('part_requests')->cascadeOnDelete();
            $table->foreign('vendor_id')->references('id')->on('vendors')->cascadeOnDelete();
            $table->foreign('submitted_by')->references('id')->on('users')->nullOnDelete();

            $table->index(['part_request_id', 'status']);
            // A vendor quotes a given request at most once.
            $table->unique(['part_request_id', 'vendor_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('quotes');
    }
};
