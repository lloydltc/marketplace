<x-layouts.app>
    <x-slot:title>Vehicle Features</x-slot:title>

    <div class="max-w-5xl mx-auto px-4 sm:px-6 lg:px-8 py-10">
        <div class="flex items-center justify-between mb-6">
            <div>
                <h1 class="text-2xl font-semibold text-neutral-900">Vehicle Features</h1>
                <p class="text-sm text-neutral-500 mt-1">Define the specs sellers can set and buyers can filter by.</p>
            </div>
            <a href="{{ route('admin.vehicle-features.create') }}"
               class="bg-[#F0A820] hover:bg-[#F0A820]/90 text-[#1A1A24] font-semibold px-4 py-2 rounded-lg text-sm transition-colors">+ Add feature</a>
        </div>

        @if (session('status'))
            <div class="mb-4 bg-green-50 border border-green-200 text-green-700 text-sm rounded-lg px-4 py-2">{{ session('status') }}</div>
        @endif

        <div class="bg-white border border-neutral-200 rounded-xl shadow-sm divide-y divide-neutral-100">
            @forelse ($features as $feature)
                <div class="flex items-center justify-between gap-4 px-5 py-3 {{ $feature->is_active ? '' : 'opacity-60' }}">
                    <div class="min-w-0">
                        <p class="text-sm font-medium text-neutral-900">
                            {{ $feature->name }}
                            <span class="text-xs text-neutral-400">· {{ $feature->group ?: 'Features' }}</span>
                        </p>
                        <p class="text-xs text-neutral-500">
                            <span class="capitalize">{{ $feature->type }}</span>@if($feature->unit) ({{ $feature->unit }})@endif
                            @if($feature->type === 'enum' && $feature->options) · {{ implode(', ', $feature->options) }}@endif
                            @if($feature->is_filterable) · <span class="text-[#2EBD7A]">filterable</span>@endif
                            @unless($feature->is_active) · <span class="text-red-500">retired</span>@endunless
                        </p>
                    </div>
                    <div class="flex items-center gap-2 shrink-0">
                        <a href="{{ route('admin.vehicle-features.edit', $feature) }}" class="text-xs text-neutral-600 hover:text-neutral-900 border border-neutral-200 rounded-lg px-2.5 py-1">Edit</a>
                        <form method="POST" action="{{ route('admin.vehicle-features.toggle', $feature) }}">
                            @csrf
                            <button type="submit" class="text-xs {{ $feature->is_active ? 'text-red-600 border-red-200' : 'text-[#2EBD7A] border-green-200' }} border rounded-lg px-2.5 py-1">
                                {{ $feature->is_active ? 'Retire' : 'Reactivate' }}
                            </button>
                        </form>
                    </div>
                </div>
            @empty
                <div class="px-5 py-10 text-center text-sm text-neutral-500">No features defined yet. Add the first one.</div>
            @endforelse
        </div>
    </div>
</x-layouts.app>
