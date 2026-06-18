<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->enum('role', [
                'super_admin',
                'admin',
                'vendor_admin',
                'vendor_worker',
                'agent',
                'private_seller',
                'customer',
            ])->default('customer')->after('email');

            $table->boolean('force_password_change')->default(false)->after('remember_token');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['role', 'force_password_change']);
        });
    }
};
