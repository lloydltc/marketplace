<x-layouts.app>
    <x-slot:title>Agent Dashboard</x-slot:title>

    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-10">

        <div class="mb-8">
            <h1 class="text-2xl font-semibold text-neutral-900">Agent Dashboard</h1>
            <p class="text-sm text-neutral-500 mt-1">Manage your listings and agent profile.</p>
        </div>

        <div class="grid grid-cols-1 sm:grid-cols-3 gap-4 mb-8">
            @foreach ([['Active Listings', '—'], ['Total Leads', '—'], ['Commission Earned', '—']] as [$label, $value])
            <div class="bg-white border border-neutral-200 rounded-xl p-5 shadow-sm">
                <p class="text-sm font-medium text-neutral-500">{{ $label }}</p>
                <p class="mt-2 text-3xl font-semibold text-neutral-900">{{ $value }}</p>
            </div>
            @endforeach
        </div>

        <div class="bg-white border border-neutral-200 rounded-xl shadow-sm">
            <div class="flex flex-col items-center justify-center py-16 text-center">
                <svg class="w-12 h-12 text-neutral-300 mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5"
                          d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/>
                </svg>
                <h3 class="text-base font-semibold text-neutral-700">Agent profile — Phase 3</h3>
                <p class="mt-1 text-sm text-neutral-500 max-w-xs">Listing management and agent profile editing will be available in the next phase.</p>
            </div>
        </div>

    </div>
</x-layouts.app>
