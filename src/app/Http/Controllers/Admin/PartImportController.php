<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Modules\Parts\Services\PartImporter;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * PM9: CSV bulk import for canonical parts. Always validates; "Validate" runs a
 * dry-run (creates nothing) and reports per-row errors; "Import" commits.
 */
class PartImportController extends Controller
{
    public function create(): View
    {
        return view('admin.parts.import', ['report' => null]);
    }

    public function process(Request $request, PartImporter $importer): View
    {
        $request->validate([
            'csv'    => ['required', 'file', 'mimes:csv,txt', 'max:5120'],
            'action' => ['required', 'in:validate,import'],
        ]);

        $rows = $this->parseCsv($request->file('csv')->getRealPath());
        $dryRun = $request->input('action') === 'validate';

        $report = $importer->import($rows, $dryRun);

        if (! $dryRun) {
            AuditLog::record($request->user(), 'catalog.part.import', null, [
                'created' => $report['created'], 'errors' => count($report['errors']),
            ]);
        }

        return view('admin.parts.import', ['report' => $report]);
    }

    /** @return list<array<string, string>> */
    private function parseCsv(string $path): array
    {
        $handle = fopen($path, 'r');
        if ($handle === false) {
            return [];
        }

        $header = null;
        $rows = [];
        while (($cols = fgetcsv($handle)) !== false) {
            if ($cols === [null] || $cols === false) {
                continue; // blank line
            }
            if ($header === null) {
                $header = array_map(fn ($h) => strtolower(trim((string) $h)), $cols);
                continue;
            }
            $row = [];
            foreach ($header as $i => $key) {
                $row[$key] = $cols[$i] ?? '';
            }
            $rows[] = $row;
        }
        fclose($handle);

        return $rows;
    }
}
