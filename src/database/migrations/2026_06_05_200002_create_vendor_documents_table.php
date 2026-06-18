<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('vendor_documents', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('vendor_id');
            $table->enum('document_type', [
                'business_registration',
                'tax_id',
                'bank_proof',
                'id_copy',
                'address_proof',
            ]);
            $table->string('file_path');
            $table->string('original_filename')->nullable();
            $table->enum('status', ['pending', 'approved', 'rejected'])->default('pending');
            $table->text('rejection_reason')->nullable();
            $table->timestamps();

            $table->foreign('vendor_id')
                ->references('id')->on('vendors')
                ->cascadeOnDelete();

            $table->index(['vendor_id', 'document_type']);
            $table->index(['vendor_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('vendor_documents');
    }
};
