<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * H1: add the 'draft' listing status (saved-but-not-submitted). Widen the
     * Postgres check constraint to include it.
     */
    public function up(): void
    {
        DB::statement('ALTER TABLE vehicles DROP CONSTRAINT IF EXISTS vehicles_status_check');
        DB::statement("ALTER TABLE vehicles ADD CONSTRAINT vehicles_status_check CHECK (status::text = ANY (ARRAY['draft','pending','active','inactive','rejected','expired']::text[]))");
    }

    public function down(): void
    {
        DB::statement('ALTER TABLE vehicles DROP CONSTRAINT IF EXISTS vehicles_status_check');
        DB::statement("ALTER TABLE vehicles ADD CONSTRAINT vehicles_status_check CHECK (status::text = ANY (ARRAY['pending','active','inactive','rejected','expired']::text[]))");
    }
};
