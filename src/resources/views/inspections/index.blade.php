<x-layouts.app>
    <x-slot:title>My Inspections</x-slot:title>
    <div class="max-w-3xl mx-auto px-4 sm:px-6 lg:px-8 py-10">
        <h1 class="text-h1 text-ink mb-6">My inspections</h1>
        @if ($inspections->isEmpty())
            <x-empty title="No inspections yet" message="Book a vetted inspection from any vehicle listing." />
        @else
            <div class="bg-surface border border-line rounded-xl shadow-e1 divide-y divide-line">
                @foreach ($inspections as $i)
                    <a href="{{ route('inspections.show', $i) }}" class="flex items-center justify-between px-5 py-4 hover:bg-surface-2 transition-colors">
                        <div>
                            <p class="text-body-sm font-semibold text-ink">{{ $i->vehicleLabel() }}</p>
                            <p class="text-caption text-muted mt-0.5">{{ $i->inspector?->name }} · {{ ucfirst(str_replace('_', ' ', $i->status)) }}</p>
                        </div>
                        <span class="text-body-sm text-[rgb(var(--info))]">View →</span>
                    </a>
                @endforeach
            </div>
            <div class="mt-6">{{ $inspections->links() }}</div>
        @endif
    </div>
</x-layouts.app>
