<x-layouts.app>
    <x-slot:title>Service Kits</x-slot:title>

    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
      <div class="lg:flex lg:gap-8">
        <x-vendor-sidebar />
        <div class="flex-1 min-w-0 mt-6 lg:mt-0">

        @if (session('status'))
            <div class="mb-5 bg-[rgb(var(--success)/0.12)] border border-[rgb(var(--success)/0.3)] text-[rgb(var(--success))] text-body-sm rounded-lg px-4 py-3">{{ session('status') }}</div>
        @endif

        <div class="flex items-center justify-between mb-6">
            <div>
                <h1 class="text-h2 text-ink">Service kits</h1>
                <p class="text-body-sm text-[rgb(var(--text-muted))] mt-1">Bundle your offerings into one-click kits.</p>
            </div>
            <x-button :href="route('vendor.bundles.create')" variant="primary" size="md">+ New kit</x-button>
        </div>

        @if ($bundles->isEmpty())
            <x-card padding="lg" class="text-center text-body-sm text-[rgb(var(--text-muted))] py-16">
                No service kits yet.
            </x-card>
        @else
            <x-card padding="none">
                <ul class="divide-y divide-[rgb(var(--border))]">
                    @foreach ($bundles as $bundle)
                        <li class="flex items-center justify-between gap-3 px-5 py-4">
                            <div class="min-w-0">
                                <a href="{{ route('bundles.show', $bundle->slug) }}" class="text-body font-semibold text-ink hover:text-brand">{{ $bundle->name }}</a>
                                <div class="text-caption text-[rgb(var(--text-muted))]">{{ $bundle->items_count }} {{ Str::plural('item', $bundle->items_count) }} · USD {{ number_format($bundle->effectivePrice(), 2) }}</div>
                            </div>
                            <form method="POST" action="{{ route('vendor.bundles.destroy', $bundle) }}" onsubmit="return confirm('Delete this kit?')">
                                @csrf @method('DELETE')
                                <button type="submit" class="text-body-sm text-[rgb(var(--danger))] hover:underline">Delete</button>
                            </form>
                        </li>
                    @endforeach
                </ul>
            </x-card>
            <div class="mt-6">{{ $bundles->links() }}</div>
        @endif

        </div>
      </div>
    </div>
</x-layouts.app>
