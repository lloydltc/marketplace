<?php

namespace App\Modules\Parts\Services;

use App\Modules\Categories\Models\Category;
use App\Modules\Parts\Models\Part;
use App\Modules\Vehicles\Models\VehicleMake;
use App\Modules\Vehicles\Models\VehicleModel;
use Illuminate\Support\Facades\DB;

/**
 * PM9: bulk CSV import of canonical parts (+ OEM numbers + fitment). Always runs
 * a validation pass first; dry-run reports per-row errors and creates nothing.
 * A real import commits in a single transaction. No AI.
 *
 * Expected header columns (case-insensitive):
 *   name (required), brand, category, primary_oem, oem_numbers (| separated),
 *   warranty_months, is_universal (0/1), fitments, description
 * fitments format: "Make/Model/YearStart/YearEnd", multiple separated by ";".
 */
class PartImporter
{
    private const REQUIRED = ['name'];

    /**
     * @param  list<array<string, string>>  $rows  parsed CSV rows (assoc by header)
     * @return array{created: int, valid: int, errors: list<array{row: int, message: string}>, dry_run: bool}
     */
    public function import(array $rows, bool $dryRun = true): array
    {
        $errors = [];
        $valid = 0;

        // Validate every row first (so dry-run gives a complete report).
        $prepared = [];
        foreach ($rows as $i => $raw) {
            $line = $i + 2; // +1 for 0-index, +1 for header row
            $row = $this->normalise($raw);

            $rowErrors = $this->validateRow($row);
            if ($rowErrors !== []) {
                foreach ($rowErrors as $msg) {
                    $errors[] = ['row' => $line, 'message' => $msg];
                }
                continue;
            }

            $prepared[] = $row;
            $valid++;
        }

        if ($dryRun) {
            return ['created' => 0, 'valid' => $valid, 'errors' => $errors, 'dry_run' => true];
        }

        $created = 0;
        DB::transaction(function () use ($prepared, &$created) {
            foreach ($prepared as $row) {
                $this->createPart($row);
                $created++;
            }
        });

        return ['created' => $created, 'valid' => $valid, 'errors' => $errors, 'dry_run' => false];
    }

    /** @return array<string, string> */
    private function normalise(array $raw): array
    {
        $out = [];
        foreach ($raw as $k => $v) {
            $out[strtolower(trim((string) $k))] = trim((string) $v);
        }

        return $out;
    }

    /** @return list<string> */
    private function validateRow(array $row): array
    {
        $errors = [];

        foreach (self::REQUIRED as $field) {
            if (($row[$field] ?? '') === '') {
                $errors[] = "Missing required field: {$field}.";
            }
        }

        if (($row['category'] ?? '') !== '' && ! $this->resolveCategory($row['category'])) {
            $errors[] = "Unknown category: {$row['category']}.";
        }

        if (($row['warranty_months'] ?? '') !== '' && ! ctype_digit($row['warranty_months'])) {
            $errors[] = 'warranty_months must be a whole number.';
        }

        foreach ($this->parseFitments($row['fitments'] ?? '') as $f) {
            if ($f['error']) {
                $errors[] = $f['error'];
            }
        }

        return $errors;
    }

    private function createPart(array $row): Part
    {
        $part = Part::create([
            'name'            => $row['name'],
            'brand'           => ($row['brand'] ?? '') ?: null,
            'category_id'     => ($row['category'] ?? '') !== '' ? $this->resolveCategory($row['category'])?->id : null,
            'primary_oem'     => ($row['primary_oem'] ?? '') ?: null,
            'description'     => ($row['description'] ?? '') ?: null,
            'warranty_months' => ($row['warranty_months'] ?? '') !== '' ? (int) $row['warranty_months'] : null,
            'is_universal'    => in_array(strtolower($row['is_universal'] ?? ''), ['1', 'true', 'yes'], true),
        ]);

        foreach (array_filter(array_map('trim', explode('|', $row['oem_numbers'] ?? ''))) as $number) {
            $part->oemNumbers()->firstOrCreate(['number' => $number, 'type' => 'oem']);
        }

        foreach ($this->parseFitments($row['fitments'] ?? '') as $f) {
            if (! $f['error'] && $f['make'] && $f['model']) {
                $part->fitments()->create([
                    'make_id'    => $f['make']->id,
                    'model_id'   => $f['model']->id,
                    'year_start' => $f['year_start'],
                    'year_end'   => $f['year_end'],
                ]);
            }
        }

        return $part;
    }

    private function resolveCategory(string $value): ?Category
    {
        return Category::where('slug', $value)->orWhere('name', $value)->first();
    }

    /**
     * @return list<array{make: ?VehicleMake, model: ?VehicleModel, year_start: ?int, year_end: ?int, error: ?string}>
     */
    private function parseFitments(string $value): array
    {
        if (trim($value) === '') {
            return [];
        }

        $out = [];
        foreach (array_filter(array_map('trim', explode(';', $value))) as $chunk) {
            $parts = array_map('trim', explode('/', $chunk));
            [$makeName, $modelName] = [$parts[0] ?? '', $parts[1] ?? ''];
            $yearStart = isset($parts[2]) && $parts[2] !== '' ? (int) $parts[2] : null;
            $yearEnd   = isset($parts[3]) && $parts[3] !== '' ? (int) $parts[3] : null;

            if ($makeName === '' || $modelName === '') {
                $out[] = ['make' => null, 'model' => null, 'year_start' => null, 'year_end' => null,
                    'error' => "Fitment \"{$chunk}\" must be Make/Model[/YearStart/YearEnd]."];
                continue;
            }

            $make = VehicleMake::where('name', $makeName)->first();
            $model = $make ? VehicleModel::where('make_id', $make->id)->where('name', $modelName)->first() : null;

            if (! $make || ! $model) {
                $out[] = ['make' => null, 'model' => null, 'year_start' => null, 'year_end' => null,
                    'error' => "Unknown vehicle in fitment: {$makeName} {$modelName}."];
                continue;
            }

            $out[] = ['make' => $make, 'model' => $model, 'year_start' => $yearStart, 'year_end' => $yearEnd, 'error' => null];
        }

        return $out;
    }
}
