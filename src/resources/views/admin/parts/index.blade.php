<x-layouts.app>
    <x-slot:title>Parts Catalog</x-slot:title>

    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
      <div class="lg:flex lg:gap-8">
        <x-admin-sidebar />
        <div class="flex-1 min-w-0 mt-6 lg:mt-0">

        @if (session('status'))
            <div class="mb-5 bg-[rgb(var(--success)/0.12)] border border-[rgb(var(--success)/0.3)] text-[rgb(var(--success))] text-body-sm rounded-lg px-4 py-3">{{ session('status') }}</div>
        @endif

        <div class="flex items-center justify-between gap-3 mb-6">
            <div>
                <h1 class="text-h1 text-ink">Parts catalog</h1>
                <p class="text-body-sm text-muted mt-1">{{ number_format($parts->total()) }} canonical {{ Str::plural('part', $parts->total()) }}.</p>
            </div>
            <div class="flex items-center gap-2">
                <x-button :href="route('admin.parts.import')" variant="outline" size="md">Bulk import</x-button>
                <x-button :href="route('admin.parts.create')" variant="primary" size="md">New part</x-button>
            </div>
        </div>

        <form method="GET" class="flex gap-2 mb-5">
            <input type="text" name="q" value="{{ $term }}" placeholder="Search name, brand, OEM…"
                   class="flex-1 sm:max-w-md border border-line rounded-lg px-3 py-2 text-body-sm bg-surface focus:outline-none focus:ring-2 focus:ring-[rgb(var(--brand)/0.4)]">
            <x-button type="submit" variant="outline" size="md">Search</x-button>
        </form>

        <div class="bg-surface border border-line rounded-xl shadow-e1 overflow-hidden">
            @if ($parts->isEmpty())
                <div class="px-5 py-12 text-center text-body-sm text-muted">No parts found.</div>
            @else
                <table class="w-full text-body-sm">
                    <thead>
                        <tr class="text-left text-overline uppercase text-muted border-b border-line">
                            <th class="px-5 py-2 font-medium">Part</th>
                            <th class="px-5 py-2 font-medium">Category</th>
                            <th class="px-5 py-2 font-medium text-right">Offers</th>
                            <th class="px-5 py-2 font-medium text-right">Fitments</th>
                            <th class="px-5 py-2 font-medium text-right">Status</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-[rgb(var(--border))]">
                        @foreach ($parts as $part)
                            <tr class="hover:bg-surface-2">
                                <td class="px-5 py-3">
                                    <a href="{{ route('admin.parts.edit', $part) }}" class="font-medium text-ink hover:text-brand">{{ $part->name }}</a>
                                    <div class="text-caption text-muted">{{ $part->brand }} @if($part->primary_oem)· {{ $part->primary_oem }}@endif</div>
                                </td>
                                <td class="px-5 py-3 text-muted">{{ $part->category?->name ?? '—' }}</td>
                                <td class="px-5 py-3 text-right tabular-nums">{{ $part->offerings_count }}</td>
                                <td class="px-5 py-3 text-right tabular-nums">{{ $part->is_universal ? 'Universal' : $part->fitments_count }}</td>
                                <td class="px-5 py-3 text-right">
                                    <span class="text-caption px-2 py-0.5 rounded-full {{ $part->isActive() ? 'bg-[rgb(var(--success)/0.15)] text-[rgb(var(--success))]' : 'bg-surface-2 text-muted' }}">{{ ucfirst($part->status) }}</span>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            @endif
        </div>

        <div class="mt-6">{{ $parts->links() }}</div>
        </div>
      </div>
    </div>
</x-layouts.app>
