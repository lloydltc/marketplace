<x-layouts.app>
    <x-slot:title>History Reports</x-slot:title>

    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
      <div class="lg:flex lg:gap-8">
        <x-admin-sidebar />
        <div class="flex-1 min-w-0 mt-6 lg:mt-0">

        @if (session('status'))
            <div class="mb-5 bg-[rgb(var(--success)/0.12)] border border-[rgb(var(--success)/0.3)] text-[rgb(var(--success))] text-body-sm rounded-lg px-4 py-3">{{ session('status') }}</div>
        @endif

        <div class="mb-6">
            <h1 class="text-h1 text-ink">History reports</h1>
            <p class="text-body-sm text-muted mt-1">Manage data sources and purchased reports. Per-report price is set in Settings (history.report_price_usd).</p>
        </div>

        {{-- Data sources --}}
        <div class="bg-surface border border-line rounded-xl shadow-e1 overflow-hidden mb-8">
            <div class="px-5 py-4 border-b border-line"><h2 class="text-h4 text-ink">Data sources</h2></div>
            <table class="w-full text-body-sm">
                <thead>
                    <tr class="text-left text-overline uppercase text-muted border-b border-line">
                        <th class="px-5 py-2 font-medium">Source</th>
                        <th class="px-5 py-2 font-medium">Type</th>
                        <th class="px-5 py-2 font-medium">Status</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-line">
                    @foreach ($sources as $source)
                        <tr>
                            <td class="px-5 py-3 text-ink">{{ $source->name }}</td>
                            <td class="px-5 py-3 text-muted">{{ $source->type }}</td>
                            <td class="px-5 py-3">
                                <form method="POST" action="{{ route('admin.history.sources.update', $source) }}" class="flex items-center gap-2">
                                    @csrf
                                    <select name="status" class="border border-line rounded-lg px-2 py-1 text-caption bg-surface">
                                        @foreach (['live', 'manual', 'unavailable'] as $s)
                                            <option value="{{ $s }}" @selected($source->status === $s)>{{ ucfirst($s) }}</option>
                                        @endforeach
                                    </select>
                                    <button type="submit" class="text-caption font-medium text-[rgb(var(--info))] hover:underline">Save</button>
                                </form>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>

        {{-- Purchased reports --}}
        <div class="bg-surface border border-line rounded-xl shadow-e1 overflow-hidden">
            <div class="px-5 py-4 border-b border-line"><h2 class="text-h4 text-ink">Purchased reports</h2></div>
            @if ($reports->isEmpty())
                <div class="px-5 py-10 text-center text-body-sm text-muted">No reports purchased yet.</div>
            @else
                <table class="w-full text-body-sm">
                    <thead>
                        <tr class="text-left text-overline uppercase text-muted border-b border-line">
                            <th class="px-5 py-2 font-medium">Vehicle</th>
                            <th class="px-5 py-2 font-medium">Buyer</th>
                            <th class="px-5 py-2 font-medium">Status</th>
                            <th class="px-5 py-2 font-medium text-right">Action</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-line">
                        @foreach ($reports as $report)
                            <tr>
                                <td class="px-5 py-3 text-ink">{{ $report->vehicle?->displayTitle() ?? $report->vin }}</td>
                                <td class="px-5 py-3 text-muted">{{ $report->requester?->name ?? '—' }}</td>
                                <td class="px-5 py-3"><span class="capitalize {{ $report->status === 'refunded' ? 'text-[rgb(var(--danger))]' : 'text-[rgb(var(--success))]' }}">{{ $report->status }}</span></td>
                                <td class="px-5 py-3 text-right">
                                    @if ($report->isPurchased())
                                        <form method="POST" action="{{ route('admin.history.refund', $report) }}" onsubmit="return confirm('Refund this report?')">
                                            @csrf
                                            <button type="submit" class="text-caption font-medium text-[rgb(var(--danger))] hover:underline">Refund</button>
                                        </form>
                                    @endif
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            @endif
        </div>
        <div class="mt-6">{{ $reports->links() }}</div>
        </div>
      </div>
    </div>
</x-layouts.app>
