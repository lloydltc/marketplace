<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Drop sessions.user_id (bigint index — no FK constraint) before altering users.id
        Schema::table('sessions', function (Blueprint $table) {
            $table->dropIndex(['user_id']);
            $table->dropColumn('user_id');
        });

        // Convert users.id from bigserial → uuid
        DB::statement('ALTER TABLE users ALTER COLUMN id DROP DEFAULT');
        DB::statement('ALTER TABLE users ALTER COLUMN id TYPE uuid USING gen_random_uuid()');
        DB::statement('DROP SEQUENCE IF EXISTS users_id_seq');

        // Re-add sessions.user_id as uuid nullable
        Schema::table('sessions', function (Blueprint $table) {
            $table->uuid('user_id')->nullable()->index();
        });
    }

    public function down(): void
    {
        Schema::table('sessions', function (Blueprint $table) {
            $table->dropIndex(['user_id']);
            $table->dropColumn('user_id');
        });

        // Restore users.id to bigserial
        DB::statement('CREATE SEQUENCE IF NOT EXISTS users_id_seq');
        DB::statement('ALTER TABLE users ALTER COLUMN id TYPE bigint USING 0');
        DB::statement("ALTER TABLE users ALTER COLUMN id SET DEFAULT nextval('users_id_seq')");

        Schema::table('sessions', function (Blueprint $table) {
            $table->foreignId('user_id')->nullable()->index();
        });
    }
};
