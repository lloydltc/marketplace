<x-layouts.app>
    <x-slot:title>Inspectors</x-slot:title>
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
      <div class="lg:flex lg:gap-8">
        <x-admin-sidebar />
        <div class="flex-1 min-w-0 mt-6 lg:mt-0">

        @if (session('status'))
            <div class="mb-5 bg-[rgb(var(--success)/0.12)] border border-[rgb(var(--success)/0.3)] text-[rgb(var(--success))] text-body-sm rounded-lg px-4 py-3">{{ session('status') }}</div>
        @endif

        <h1 class="text-h1 text-ink mb-6">Inspector panel</h1>

        {{-- Add inspector --}}
        <x-card padding="lg" class="mb-8">
            <h2 class="text-h4 text-ink mb-3">Add an inspector</h2>
            <form method="POST" action="{{ route('admin.inspectors.store') }}" class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                @csrf
                <input type="text" name="name" placeholder="Name" required class="border border-line rounded-lg px-3 py-2 text-body-sm bg-surface">
                <select name="kind" class="border border-line rounded-lg px-3 py-2 text-body-sm bg-surface">
                    <option value="mechanic">Mechanic</option><option value="company">Company</option><option value="expert">Expert</option>
                </select>
                <input type="text" name="coverage_area" placeholder="Coverage area" class="border border-line rounded-lg px-3 py-2 text-body-sm bg-surface">
                <input type="text" name="phone" placeholder="Phone" class="border border-line rounded-lg px-3 py-2 text-body-sm bg-surface">
                <input type="email" name="email" placeholder="Contact email" class="border border-line rounded-lg px-3 py-2 text-body-sm bg-surface">
                <input type="email" name="link_email" placeholder="Portal login email (optional)" class="border border-line rounded-lg px-3 py-2 text-body-sm bg-surface">
                <div class="sm:col-span-2 flex justify-end"><x-button type="submit" variant="primary">Add inspector</x-button></div>
            </form>
        </x-card>

        {{-- Panel --}}
        <div class="bg-surface border border-line rounded-xl shadow-e1 overflow-hidden mb-8">
            <table class="w-full text-body-sm">
                <thead><tr class="text-left text-overline uppercase text-muted border-b border-line">
                    <th class="px-5 py-2 font-medium">Inspector</th><th class="px-5 py-2 font-medium">Kind</th>
                    <th class="px-5 py-2 font-medium text-right">Rating</th><th class="px-5 py-2 font-medium text-right">Jobs</th>
                    <th class="px-5 py-2 font-medium text-right">Active</th>
                </tr></thead>
                <tbody class="divide-y divide-line">
                    @forelse ($inspectors as $inspector)
                        <tr>
                            <td class="px-5 py-3 text-ink">{{ $inspector->name }}<span class="block text-caption text-muted">{{ $inspector->coverage_area }}</span></td>
                            <td class="px-5 py-3 text-muted capitalize">{{ $inspector->kind }}</td>
                            <td class="px-5 py-3 text-right tabular-nums">{{ $inspector->review_count ? $inspector->rating . ' (' . $inspector->review_count . ')' : '—' }}</td>
                            <td class="px-5 py-3 text-right tabular-nums">{{ $inspector->inspections_count }}</td>
                            <td class="px-5 py-3 text-right">
                                <form method="POST" action="{{ route('admin.inspectors.toggle', $inspector) }}">
                                    @csrf
                                    <button type="submit" class="text-caption font-medium {{ $inspector->is_active ? 'text-[rgb(var(--success))]' : 'text-muted' }}">{{ $inspector->is_active ? 'Active' : 'Inactive' }}</button>
                                </form>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="5" class="px-5 py-10 text-center text-muted">No inspectors yet.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        {{-- Inspections oversight --}}
        <div class="bg-surface border border-line rounded-xl shadow-e1 overflow-hidden">
            <div class="px-5 py-4 border-b border-line"><h2 class="text-h4 text-ink">Recent inspections</h2></div>
            <table class="w-full text-body-sm">
                <tbody class="divide-y divide-line">
                    @forelse ($inspections as $i)
                        <tr>
                            <td class="px-5 py-3 text-ink">{{ $i->vehicleLabel() }}</td>
                            <td class="px-5 py-3 text-muted">{{ $i->inspector?->name ?? 'Unassigned' }}</td>
                            <td class="px-5 py-3 text-muted">{{ $i->buyer?->name }}</td>
                            <td class="px-5 py-3 text-right capitalize text-muted">{{ str_replace('_', ' ', $i->status) }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="4" class="px-5 py-10 text-center text-muted">No inspections yet.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="mt-6">{{ $inspections->links() }}</div>
        </div>
      </div>
    </div>
</x-layouts.app>
