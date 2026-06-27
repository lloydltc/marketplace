<x-layouts.app>
    <x-slot:title>New Service Kit</x-slot:title>

    <div class="max-w-3xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
        <a href="{{ route('vendor.bundles.index') }}" class="text-body-sm text-[rgb(var(--text-muted))] hover:text-ink">← Service kits</a>
        <h1 class="text-h2 text-ink mt-3 mb-6">New service kit</h1>

        @if ($errors->any())
            <div class="mb-5 bg-[rgb(var(--danger)/0.1)] border border-[rgb(var(--danger)/0.3)] text-[rgb(var(--danger))] text-body-sm rounded-lg px-4 py-3">
                {{ $errors->first() }}
            </div>
        @endif

        <form method="POST" action="{{ route('vendor.bundles.store') }}" class="space-y-5">
            @csrf
            <x-card padding="lg" class="space-y-4">
                <div>
                    <label class="block text-body-sm font-medium text-ink mb-1">Kit name</label>
                    <input type="text" name="name" value="{{ old('name') }}" required maxlength="150"
                           class="w-full border border-line rounded-lg px-3 py-2 text-body-sm bg-surface focus:outline-none focus:ring-2 focus:ring-[rgb(var(--brand)/0.4)]">
                </div>
                <div>
                    <label class="block text-body-sm font-medium text-ink mb-1">Description (optional)</label>
                    <textarea name="description" rows="3" maxlength="2000"
                              class="w-full border border-line rounded-lg px-3 py-2 text-body-sm bg-surface focus:outline-none focus:ring-2 focus:ring-[rgb(var(--brand)/0.4)]">{{ old('description') }}</textarea>
                </div>
                <div>
                    <label class="block text-body-sm font-medium text-ink mb-1">Set price USD (optional — blank = sum of items)</label>
                    <input type="number" name="price_usd" value="{{ old('price_usd') }}" step="0.01" min="0"
                           class="w-40 border border-line rounded-lg px-3 py-2 text-body-sm bg-surface focus:outline-none focus:ring-2 focus:ring-[rgb(var(--brand)/0.4)]">
                </div>
            </x-card>

            <x-card padding="lg">
                <h2 class="text-h4 text-ink mb-1">Choose components</h2>
                <p class="text-caption text-[rgb(var(--text-muted))] mb-4">Set a quantity (0 = exclude).</p>
                @if ($offerings->isEmpty())
                    <p class="text-body-sm text-[rgb(var(--text-muted))]">You have no active offerings to bundle yet.</p>
                @else
                    <ul class="divide-y divide-[rgb(var(--border))]">
                        @foreach ($offerings as $offering)
                            <li class="flex items-center justify-between gap-3 py-3">
                                <div class="min-w-0">
                                    <span class="text-body-sm font-medium text-ink">{{ $offering->title }}</span>
                                    <div class="text-caption text-[rgb(var(--text-muted))]">USD {{ number_format((float) $offering->price_usd, 2) }} · {{ $offering->quantity }} in stock</div>
                                </div>
                                <input type="number" name="items[{{ $offering->id }}]" value="0" min="0" max="999"
                                       class="w-20 border border-line rounded-lg px-3 py-2 text-body-sm bg-surface text-center">
                            </li>
                        @endforeach
                    </ul>
                @endif
            </x-card>

            <div class="flex justify-end gap-3">
                <a href="{{ route('vendor.bundles.index') }}" class="text-body-sm text-[rgb(var(--text-muted))] hover:text-ink px-4 py-2">Cancel</a>
                <x-button type="submit" variant="primary" size="md">Create kit</x-button>
            </div>
        </form>
    </div>
</x-layouts.app>
