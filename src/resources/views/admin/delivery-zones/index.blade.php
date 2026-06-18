<x-layouts.app>
    <x-slot:title>Delivery Zones</x-slot:title>

    <div class="max-w-2xl mx-auto px-4 sm:px-6 lg:px-8 py-10">
        <h1 class="text-2xl font-semibold text-neutral-900 mb-6">Delivery Zones</h1>

        @if (session('status'))
            <div class="mb-5 bg-green-50 border border-green-200 text-green-700 text-sm rounded-lg px-4 py-3">{{ session('status') }}</div>
        @endif

        <form method="POST" action="{{ route('admin.delivery-zones.store') }}" class="bg-white border border-neutral-200 rounded-xl shadow-sm p-5 mb-6 flex items-end gap-3">
            @csrf
            <div class="flex-1">
                <label class="block text-sm font-medium text-neutral-700 mb-1">Zone name</label>
                <input name="name" required class="block w-full border border-neutral-300 rounded-lg px-3 py-2 text-sm">
            </div>
            <div class="w-32">
                <label class="block text-sm font-medium text-neutral-700 mb-1">Flat fee</label>
                <input name="flat_fee" type="number" step="0.01" min="0" required class="block w-full border border-neutral-300 rounded-lg px-3 py-2 text-sm">
            </div>
            <button type="submit" class="bg-[#F0A820] hover:bg-[#F0A820]/90 text-[#1A1A24] font-semibold px-4 py-2 rounded-lg text-sm">Add</button>
        </form>

        <div class="bg-white border border-neutral-200 rounded-xl shadow-sm divide-y divide-neutral-100">
            @forelse ($zones as $zone)
                <div class="flex items-center justify-between px-5 py-3">
                    <div>
                        <span class="text-neutral-800">{{ $zone->name }}</span>
                        <span class="text-sm text-neutral-400 ml-2 tabular-nums">ZWL {{ number_format($zone->flat_fee, 2) }}</span>
                    </div>
                    <form method="POST" action="{{ route('admin.delivery-zones.toggle', $zone) }}">
                        @csrf
                        <button type="submit" class="text-xs px-2 py-0.5 rounded-full {{ $zone->is_active ? 'bg-green-50 text-green-700' : 'bg-neutral-100 text-neutral-500' }}">
                            {{ $zone->is_active ? 'Active' : 'Inactive' }}
                        </button>
                    </form>
                </div>
            @empty
                <div class="px-5 py-10 text-center text-sm text-neutral-400">No zones yet.</div>
            @endforelse
        </div>
    </div>
</x-layouts.app>
