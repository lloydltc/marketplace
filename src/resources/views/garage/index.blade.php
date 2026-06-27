<x-layouts.app>
    <x-slot:title>My Garage</x-slot:title>

    <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 py-10">
        <header class="mb-6">
            <h1 class="text-h1 text-ink">My garage</h1>
            <p class="text-body-sm text-[rgb(var(--text-muted))] mt-1">Save your vehicles to shop guaranteed-fit parts in one tap.</p>
        </header>

        @if (session('status'))
            <div class="mb-5 bg-[rgb(var(--success)/0.12)] border border-[rgb(var(--success)/0.3)] text-[rgb(var(--success))] text-body-sm rounded-lg px-4 py-3">{{ session('status') }}</div>
        @endif

        {{-- Add a vehicle (reuses the cascading fitment selector, posting to the garage) --}}
        <div class="mb-8">
            <x-fitment-selector :action="route('garage.store')" submit-label="Save to garage">
                <x-slot:extraFields>
                    <input type="text" name="nickname" placeholder="Nickname (optional)" maxlength="60"
                           class="col-span-2 lg:col-span-3 border border-line rounded-lg px-3 py-2 text-body-sm bg-surface focus:outline-none focus:ring-2 focus:ring-[rgb(var(--brand)/0.4)]">
                </x-slot:extraFields>
            </x-fitment-selector>
        </div>

        <h2 class="text-h3 text-ink mb-3">Saved vehicles</h2>
        @if ($vehicles->isEmpty())
            <x-card padding="lg" class="text-center text-body-sm text-[rgb(var(--text-muted))] py-12">
                No saved vehicles yet. Add one above.
            </x-card>
        @else
            <div class="space-y-3">
                @foreach ($vehicles as $vehicle)
                    <x-card padding="md" class="flex items-center justify-between gap-3">
                        <div class="min-w-0">
                            <span class="text-body font-semibold text-ink">🚗 {{ $vehicle->label() }}</span>
                            @if ($vehicle->is_default)<x-badge variant="neutral" class="ml-2">Default</x-badge>@endif
                            <div class="text-caption text-[rgb(var(--text-muted))]">
                                {{ $vehicle->make?->name }} {{ $vehicle->vehicleModel?->name }}{{ $vehicle->variant ? ' · ' . $vehicle->variant->name : '' }}{{ $vehicle->year ? ' · ' . $vehicle->year : '' }}
                            </div>
                        </div>
                        <div class="flex items-center gap-2 shrink-0">
                            <form method="POST" action="{{ route('garage.activate', $vehicle) }}">
                                @csrf
                                <x-button type="submit" variant="primary" size="sm">Shop parts</x-button>
                            </form>
                            <form method="POST" action="{{ route('garage.destroy', $vehicle) }}" onsubmit="return confirm('Remove this vehicle?')">
                                @csrf @method('DELETE')
                                <button type="submit" class="text-body-sm text-[rgb(var(--danger))] hover:underline">Remove</button>
                            </form>
                        </div>
                    </x-card>
                @endforeach
            </div>
        @endif
    </div>
</x-layouts.app>
