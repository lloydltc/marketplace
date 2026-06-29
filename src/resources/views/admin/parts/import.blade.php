<x-layouts.app>
    <x-slot:title>Bulk Import Parts</x-slot:title>

    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
      <div class="lg:flex lg:gap-8">
        <x-admin-sidebar />
        <div class="flex-1 min-w-0 mt-6 lg:mt-0 max-w-2xl">
            <a href="{{ route('admin.parts.index') }}" class="text-body-sm text-muted hover:text-ink">← Parts catalog</a>
            <h1 class="text-h1 text-ink mt-2 mb-2">Bulk import parts</h1>
            <p class="text-body-sm text-muted mb-6">Upload a CSV. Always <strong>Validate</strong> first (dry-run) — it reports errors and creates nothing. Then <strong>Import</strong> to commit.</p>

            <div class="bg-surface border border-line rounded-xl shadow-e1 p-6">
                <div class="text-caption text-muted mb-4">
                    Columns: <code class="font-mono">name</code> (required), brand, category, primary_oem,
                    oem_numbers (<code>|</code> separated), warranty_months, is_universal, fitments
                    (<code>Make/Model/YearStart/YearEnd</code>, multiple separated by <code>;</code>), description.
                </div>

                @error('csv')<p class="text-caption text-[rgb(var(--danger))] mb-3">{{ $message }}</p>@enderror

                <form method="POST" action="{{ route('admin.parts.import.process') }}" enctype="multipart/form-data" class="space-y-4">
                    @csrf
                    <input type="file" name="csv" accept=".csv,text/csv" required
                           class="block w-full text-body-sm text-ink file:mr-4 file:rounded-lg file:border-0 file:bg-surface-2 file:px-4 file:py-2 file:text-body-sm file:font-medium">
                    <div class="flex gap-3">
                        <x-button type="submit" name="action" value="validate" variant="outline">Validate (dry-run)</x-button>
                        <x-button type="submit" name="action" value="import" variant="primary">Import</x-button>
                    </div>
                </form>
            </div>

            @isset($report)
                @if ($report)
                    <div class="bg-surface border border-line rounded-xl shadow-e1 p-6 mt-6">
                        <h2 class="text-h4 text-ink mb-3">
                            {{ $report['dry_run'] ? 'Validation result' : 'Import result' }}
                        </h2>
                        <div class="flex gap-6 mb-4 text-body-sm">
                            <div><span class="text-h3 font-bold text-[rgb(var(--success))] tabular-nums">{{ $report['dry_run'] ? $report['valid'] : $report['created'] }}</span>
                                <span class="text-muted">{{ $report['dry_run'] ? 'valid rows' : 'created' }}</span></div>
                            <div><span class="text-h3 font-bold {{ count($report['errors']) ? 'text-[rgb(var(--danger))]' : 'text-muted' }} tabular-nums">{{ count($report['errors']) }}</span>
                                <span class="text-muted">errors</span></div>
                        </div>

                        @if ($report['errors'] !== [])
                            <div class="border border-[rgb(var(--danger)/0.3)] rounded-lg overflow-hidden">
                                <table class="w-full text-body-sm">
                                    <thead><tr class="text-left text-overline uppercase text-muted bg-surface-2"><th class="px-4 py-2">Row</th><th class="px-4 py-2">Problem</th></tr></thead>
                                    <tbody class="divide-y divide-[rgb(var(--border))]">
                                        @foreach ($report['errors'] as $err)
                                            <tr><td class="px-4 py-2 tabular-nums">{{ $err['row'] }}</td><td class="px-4 py-2 text-[rgb(var(--danger))]">{{ $err['message'] }}</td></tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                        @elseif ($report['dry_run'])
                            <p class="text-body-sm text-[rgb(var(--success))]">All rows valid — ready to import.</p>
                        @endif
                    </div>
                @endif
            @endisset
        </div>
      </div>
    </div>
</x-layouts.app>
