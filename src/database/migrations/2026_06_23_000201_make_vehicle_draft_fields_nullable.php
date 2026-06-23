<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * H1: drafts persist partial input, so the spec columns must allow NULL.
     * Full validation on PUBLISH still guarantees published listings are complete.
     * Raw DROP NOT NULL avoids disturbing the existing enum check constraints
     * (which already permit NULL).
     */
    private array $columns = [
        'make_id', 'model_id', 'year', 'body_type',
        'transmission', 'fuel_type', 'mileage', 'color', 'condition',
    ];

    public function up(): void
    {
        foreach ($this->columns as $col) {
            DB::statement("ALTER TABLE vehicles ALTER COLUMN {$col} DROP NOT NULL");
        }
    }

    public function down(): void
    {
        // Not reversed: existing drafts may legitimately hold NULLs.
    }
};
