<?php

namespace Database\Seeders;

use App\Modules\History\Models\HistoryDataSource;
use Illuminate\Database\Seeder;

/**
 * HR1: sync the configured history data sources into the DB (idempotent), so
 * admins can manage status/config and the assembler can iterate them.
 */
class HistoryDataSourceSeeder extends Seeder
{
    public function run(): void
    {
        foreach (config('history.sources', []) as $key => $def) {
            HistoryDataSource::updateOrCreate(
                ['key' => $key],
                [
                    'name'    => $def['name'],
                    'type'    => $def['type'],
                    'adapter' => $def['adapter'] ?? null,
                    'status'  => $def['status'],
                ],
            );
        }
    }
}
