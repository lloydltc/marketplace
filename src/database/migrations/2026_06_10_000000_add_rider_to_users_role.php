<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Additive change (7R.1): widen the users.role CHECK constraint to allow 'rider'.
     * Laravel's enum() on PostgreSQL is a varchar + CHECK constraint named users_role_check.
     */
    public function up(): void
    {
        DB::statement('ALTER TABLE users DROP CONSTRAINT IF EXISTS users_role_check');
        DB::statement(
            "ALTER TABLE users ADD CONSTRAINT users_role_check CHECK (role IN (
                'super_admin','admin','vendor_admin','vendor_worker','agent','private_seller','customer','rider'
            ))"
        );
    }

    public function down(): void
    {
        DB::statement("UPDATE users SET role = 'customer' WHERE role = 'rider'");
        DB::statement('ALTER TABLE users DROP CONSTRAINT IF EXISTS users_role_check');
        DB::statement(
            "ALTER TABLE users ADD CONSTRAINT users_role_check CHECK (role IN (
                'super_admin','admin','vendor_admin','vendor_worker','agent','private_seller','customer'
            ))"
        );
    }
};
