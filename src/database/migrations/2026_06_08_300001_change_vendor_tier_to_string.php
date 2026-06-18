<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // Drop the old Phase 3 enum CHECK constraint, then widen to varchar for new tier values
        DB::statement("ALTER TABLE vendors DROP CONSTRAINT IF EXISTS vendors_tier_check");
        DB::statement("ALTER TABLE vendors ALTER COLUMN tier TYPE varchar(20) USING 'unverified'");
        DB::statement("ALTER TABLE vendors ALTER COLUMN tier SET DEFAULT 'unverified'");
        DB::statement("UPDATE vendors SET tier = 'unverified' WHERE tier NOT IN ('unverified', 'premium')");
    }

    public function down(): void
    {
        DB::statement("ALTER TABLE vendors DROP CONSTRAINT IF EXISTS vendors_tier_check");
        DB::statement("ALTER TABLE vendors ALTER COLUMN tier TYPE varchar(255)");
        DB::statement("ALTER TABLE vendors ALTER COLUMN tier SET DEFAULT 'bronze'");
        DB::statement("ALTER TABLE vendors ADD CONSTRAINT vendors_tier_check CHECK (tier IN ('bronze','silver','gold','platinum'))");
        DB::statement("UPDATE vendors SET tier = 'bronze' WHERE tier NOT IN ('bronze','silver','gold','platinum')");
    }
};
