<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('vendor_users', function (Blueprint $table) {
            $table->uuid('vendor_id');
            $table->uuid('user_id');
            $table->enum('vendor_role', ['admin', 'worker']);
            $table->timestamp('invited_at')->nullable();
            $table->timestamp('joined_at')->nullable();

            $table->primary(['vendor_id', 'user_id']);

            $table->foreign('vendor_id')
                ->references('id')->on('vendors')
                ->cascadeOnDelete();

            $table->foreign('user_id')
                ->references('id')->on('users')
                ->cascadeOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('vendor_users');
    }
};
