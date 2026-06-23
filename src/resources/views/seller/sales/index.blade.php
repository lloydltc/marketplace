<x-layouts.app>
    <x-slot:title>Sales &amp; Enquiries</x-slot:title>

    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-10">

        <div class="flex items-center justify-between mb-8">
            <div>
                <h1 class="text-2xl font-semibold text-neutral-900">Sales &amp; Enquiries</h1>
                <p class="text-sm text-neutral-500 mt-1">Buyers contact you directly about your listings. Track interest here.</p>
            </div>
            <a href="{{ route('seller.vehicles.create') }}"
               class="bg-[#F0A820] hover:bg-[#F0A820]/90 text-[#1A1A24] font-semibold px-4 py-2 rounded-lg text-sm transition-colors">
                + List a vehicle
            </a>
        </div>

        {{-- Stats --}}
        <div class="grid grid-cols-1 sm:grid-cols-3 gap-4 mb-8">
            <div class="bg-white border border-neutral-200 rounded-xl p-5 shadow-sm">
                <p class="text-sm font-medium text-neutral-500">Active listings</p>
                <p class="mt-2 text-3xl font-semibold text-neutral-900 tabular-nums">{{ number_format($stats['active']) }}</p>
            </div>
            <div class="bg-white border border-neutral-200 rounded-xl p-5 shadow-sm">
                <p class="text-sm font-medium text-neutral-500">Awaiting approval</p>
                <p class="mt-2 text-3xl font-semibold text-neutral-900 tabular-nums">{{ number_format($stats['pending']) }}</p>
            </div>
            <div class="bg-white border border-neutral-200 rounded-xl p-5 shadow-sm">
                <p class="text-sm font-medium text-neutral-500">Total listings</p>
                <p class="mt-2 text-3xl font-semibold text-neutral-900 tabular-nums">{{ number_format($stats['total']) }}</p>
            </div>
        </div>

        {{-- Enquiries (lead-gen: buyers contact via listing details) --}}
        <div class="bg-white border border-neutral-200 rounded-xl shadow-sm mb-8">
            <div class="px-5 py-4 border-b border-neutral-100">
                <h2 class="text-base font-semibold text-neutral-900">Buyer enquiries</h2>
            </div>
            <div class="flex flex-col items-center justify-center py-14 text-center">
                <svg class="w-12 h-12 text-neutral-300 mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5"
                          d="M8 10h.01M12 10h.01M16 10h.01M21 12a8 8 0 01-8 8H7l-4 3V12a8 8 0 018-8h2a8 8 0 018 8z"/>
                </svg>
                <h3 class="text-base font-semibold text-neutral-700">No enquiries yet</h3>
                <p class="mt-1 text-sm text-neutral-500 max-w-sm">
                    Your vehicles are lead-gen listings — buyers reach you using the contact details on each listing.
                    Make sure your phone number and details are up to date so buyers can get in touch.
                </p>
            </div>
        </div>

        {{-- Listings buyers can enquire about --}}
        <div class="bg-white border border-neutral-200 rounded-xl shadow-sm">
            <div class="flex items-center justify-between px-5 py-4 border-b border-neutral-100">
                <h2 class="text-base font-semibold text-neutral-900">Your listings</h2>
                <a href="{{ route('seller.vehicles.index') }}" class="text-sm text-[#3DB8E8] hover:underline">Manage all →</a>
            </div>

            @if ($listings->isEmpty())
                <div class="flex flex-col items-center justify-center py-16 text-center">
                    <svg class="w-12 h-12 text-neutral-300 mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M8 17H5a2 2 0 01-2-2v-4l2-5h10l2 5v4a2 2 0 01-2 2h-3m-4 0h4m-4 0v-4h4v4"/>
                    </svg>
                    <h3 class="text-base font-semibold text-neutral-700">No listings yet</h3>
                    <p class="mt-1 text-sm text-neutral-500 max-w-xs">List your first vehicle to start receiving enquiries.</p>
                    <a href="{{ route('seller.vehicles.create') }}" class="mt-4 bg-[#F0A820] hover:bg-[#F0A820]/90 text-[#1A1A24] font-semibold px-4 py-2 rounded-lg text-sm transition-colors">List a vehicle</a>
                </div>
            @else
                <table class="w-full text-sm">
                    <tbody class="divide-y divide-neutral-100">
                        @foreach ($listings as $vehicle)
                            <tr class="hover:bg-neutral-50 transition-colors">
                                <td class="px-5 py-3">
                                    <a href="{{ route('seller.vehicles.show', $vehicle) }}" class="font-medium text-neutral-900 hover:text-[#F0A820]">{{ $vehicle->displayTitle() }}</a>
                                </td>
                                <td class="px-5 py-3 text-right tabular-nums text-neutral-700">{{ $vehicle->primaryPrice() }}</td>
                                <td class="px-5 py-3 text-center"><x-order-status :status="$vehicle->status" /></td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
                <div class="px-5 py-4">{{ $listings->links() }}</div>
            @endif
        </div>
    </div>
</x-layouts.app>
