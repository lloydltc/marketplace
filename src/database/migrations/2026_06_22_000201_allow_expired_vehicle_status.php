<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * D5: allow the 'expired' lifecycle status. The status column is a Postgres
     * check constraint (Laravel enum), so widen it to include 'expired'.
     */
    public function up(): void
    {
        DB::statement('ALTER TABLE vehicles DROP CONSTRAINT IF EXISTS vehicles_status_check');
        DB::statement("ALTER TABLE vehicles ADD CONSTRAINT vehicles_status_check CHECK (status::text = ANY (ARRAY['pending','active','inactive','rejected','expired']::text[]))");
    }

    public function down(): void
    {
        DB::statement('ALTER TABLE vehicles DROP CONSTRAINT IF EXISTS vehicles_status_check');
        DB::statement("ALTER TABLE vehicles ADD CONSTRAINT vehicles_status_check CHECK (status::text = ANY (ARRAY['pending','active','inactive','rejected']::text[]))");
    }
};
