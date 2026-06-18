<x-layouts.app>
    <x-slot:title>Rider Dashboard</x-slot:title>

    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-10">

        <div class="mb-8">
            <h1 class="text-2xl font-semibold text-neutral-900">Rider Dashboard</h1>
            <p class="text-sm text-neutral-500 mt-1">Your assigned deliveries and cash collection.</p>
        </div>

        <a href="{{ route('rider.deliveries.index') }}"
           class="block bg-white border border-neutral-200 rounded-xl shadow-sm hover:border-[#F0A820] transition-colors">
            <div class="flex flex-col items-center justify-center py-16 text-center">
                <svg class="w-12 h-12 text-[#F0A820] mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5"
                          d="M9 17a2 2 0 11-4 0 2 2 0 014 0zM19 17a2 2 0 11-4 0 2 2 0 014 0z"/>
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5"
                          d="M13 16V6a1 1 0 00-1-1H4a1 1 0 00-1 1v10a1 1 0 001 1h1m8-1a1 1 0 01-1 1H9m4-1V8a1 1 0 011-1h2.586a1 1 0 01.707.293l3.414 3.414a1 1 0 01.293.707V16a1 1 0 01-1 1h-1m-6-1a1 1 0 001 1h1"/>
                </svg>
                <h3 class="text-base font-semibold text-neutral-700">View my deliveries →</h3>
                <p class="mt-1 text-sm text-neutral-500 max-w-xs">
                    Assigned deliveries, status updates, proof of delivery and COD cash collection.
                </p>
            </div>
        </a>

    </div>
</x-layouts.app>
