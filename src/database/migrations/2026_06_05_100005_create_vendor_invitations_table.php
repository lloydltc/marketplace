<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('vendor_invitations', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('vendor_id');
            $table->uuid('invited_by');
            $table->string('email');
            $table->string('temp_password');
            $table->string('token')->unique();
            $table->timestamp('expires_at');
            $table->timestamp('accepted_at')->nullable();
            $table->timestamps();

            $table->foreign('vendor_id')
                ->references('id')->on('vendors')
                ->cascadeOnDelete();

            $table->foreign('invited_by')
                ->references('id')->on('users');

            $table->index(['vendor_id', 'email']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('vendor_invitations');
    }
};
