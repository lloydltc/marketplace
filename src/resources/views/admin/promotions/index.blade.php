<x-layouts.app>
    <x-slot:title>Promotion Packages</x-slot:title>

    <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 py-10">
        <div class="flex items-center justify-between mb-6">
            <h1 class="text-2xl font-semibold text-neutral-900">Promotion Packages</h1>
            <div class="text-right">
                <div class="text-xs text-neutral-400 uppercase tracking-wide">Promotion revenue</div>
                <div class="text-lg font-bold text-neutral-900 tabular-nums">ZWL {{ number_format($revenue, 2) }}</div>
            </div>
        </div>

        @if (session('status'))
            <div class="mb-5 bg-green-50 border border-green-200 text-green-700 text-sm rounded-lg px-4 py-3">{{ session('status') }}</div>
        @endif

        <form method="POST" action="{{ route('admin.promotions.store') }}" class="bg-white border border-neutral-200 rounded-xl shadow-sm p-5 mb-6 grid grid-cols-2 sm:grid-cols-6 gap-3 items-end">
            @csrf
            <div class="col-span-2"><label class="block text-xs text-neutral-500 mb-1">Name</label><input name="name" required class="block w-full border border-neutral-300 rounded-lg px-3 py-2 text-sm"></div>
            <div><label class="block text-xs text-neutral-500 mb-1">Price</label><input name="price" type="number" step="0.01" min="0" required class="block w-full border border-neutral-300 rounded-lg px-3 py-2 text-sm"></div>
            <div><label class="block text-xs text-neutral-500 mb-1">Listings</label><input name="listing_credits" type="number" min="0" value="0" required class="block w-full border border-neutral-300 rounded-lg px-3 py-2 text-sm"></div>
            <div><label class="block text-xs text-neutral-500 mb-1">Features</label><input name="feature_credits" type="number" min="0" value="0" required class="block w-full border border-neutral-300 rounded-lg px-3 py-2 text-sm"></div>
            <div><label class="block text-xs text-neutral-500 mb-1">Bumps</label><input name="bump_credits" type="number" min="0" value="0" required class="block w-full border border-neutral-300 rounded-lg px-3 py-2 text-sm"></div>
            <div><label class="block text-xs text-neutral-500 mb-1">Days</label><input name="duration_days" type="number" min="1" value="30" required class="block w-full border border-neutral-300 rounded-lg px-3 py-2 text-sm"></div>
            <button type="submit" class="col-span-2 sm:col-span-1 bg-[#1A1A24] hover:bg-[#080810] text-white font-semibold py-2 rounded-lg text-sm">Add</button>
        </form>

        <div class="bg-white border border-neutral-200 rounded-xl shadow-sm divide-y divide-neutral-100">
            @forelse ($packages as $package)
                <div class="flex items-center justify-between px-5 py-3 text-sm">
                    <div>
                        <span class="font-medium text-neutral-800">{{ $package->name }}</span>
                        <span class="text-neutral-400 ml-2 tabular-nums">ZWL {{ number_format($package->price, 2) }}</span>
                        <span class="text-xs text-neutral-400 ml-2">{{ $package->listing_credits }}L / {{ $package->feature_credits }}F / {{ $package->bump_credits }}B · {{ $package->duration_days }}d</span>
                    </div>
                    <form method="POST" action="{{ route('admin.promotions.toggle', $package) }}">
                        @csrf
                        <button type="submit" class="text-xs px-2 py-0.5 rounded-full {{ $package->is_active ? 'bg-green-50 text-green-700' : 'bg-neutral-100 text-neutral-500' }}">
                            {{ $package->is_active ? 'Active' : 'Inactive' }}
                        </button>
                    </form>
                </div>
            @empty
                <div class="px-5 py-10 text-center text-sm text-neutral-400">No packages yet.</div>
            @endforelse
        </div>
    </div>
</x-layouts.app>
