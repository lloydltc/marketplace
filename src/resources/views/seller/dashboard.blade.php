<x-layouts.app>
    <x-slot:title>Seller Dashboard</x-slot:title>

    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
      <div class="lg:flex lg:gap-8">
        <x-seller-sidebar />
        <div class="flex-1 min-w-0 mt-6 lg:mt-0">

        {{-- Status banner --}}
        @if ($user->isPendingApproval())
            <div class="mb-6 bg-yellow-50 border border-yellow-200 rounded-xl px-5 py-4">
                <p class="text-sm font-semibold text-yellow-800">Account pending verification</p>
                <p class="text-sm text-yellow-700 mt-0.5">
                    You can list vehicles now — they'll show an <strong>Unverified seller</strong> badge to buyers until our team verifies your account.
                </p>
            </div>
        @endif

        <div class="mb-8">
            <h1 class="text-2xl font-semibold text-neutral-900">Seller Dashboard</h1>
            <p class="text-sm text-neutral-500 mt-1">List and manage your vehicles for sale.</p>
        </div>

        {{-- H9: renew prompts for expiring/expired listings --}}
        <x-renew-prompt :vehicles="$attentionVehicles" renew-route="seller.vehicles.renew" />

        {{-- Real stats --}}
        <div class="grid grid-cols-1 sm:grid-cols-3 gap-4 mb-8">
            <div class="bg-white border border-neutral-200 rounded-xl p-5 shadow-sm">
                <p class="text-sm font-medium text-neutral-500">Active listings</p>
                <p class="mt-2 text-3xl font-semibold text-neutral-900 tabular-nums">{{ $activeCount }}</p>
            </div>
            <div class="bg-white border border-neutral-200 rounded-xl p-5 shadow-sm">
                <p class="text-sm font-medium text-neutral-500">Awaiting approval</p>
                <p class="mt-2 text-3xl font-semibold text-neutral-900 tabular-nums">{{ $pendingCount }}</p>
            </div>
            <div class="bg-white border border-neutral-200 rounded-xl p-5 shadow-sm">
                <p class="text-sm font-medium text-neutral-500">Listing slots</p>
                <p class="mt-2 text-3xl font-semibold text-neutral-900 tabular-nums">
                    {{ $vehicleCount }}{{ $vehicleLimit !== null ? ' / ' . $vehicleLimit : '' }}
                </p>
                @if ($remainingSlots !== null)
                    <p class="mt-1 text-sm text-neutral-400">{{ $remainingSlots }} remaining</p>
                @endif
            </div>
        </div>

        {{-- My listings --}}
        <div class="bg-white border border-neutral-200 rounded-xl shadow-sm">
            <div class="flex items-center justify-between px-5 py-4 border-b border-neutral-100">
                <h2 class="text-base font-semibold text-neutral-900">My vehicle listings</h2>
                <a href="{{ route('seller.vehicles.index') }}" class="text-sm text-[#3DB8E8] hover:underline">View all →</a>
            </div>

            @if ($recentVehicles->isEmpty())
                <div class="flex flex-col items-center justify-center py-16 text-center">
                    <svg class="w-12 h-12 text-neutral-300 mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M8 17H5a2 2 0 01-2-2v-4l2-5h10l2 5v4a2 2 0 01-2 2h-3m-4 0h4m-4 0v-4h4v4"/>
                    </svg>
                    <h3 class="text-base font-semibold text-neutral-700">No listings yet</h3>
                    <p class="mt-1 text-sm text-neutral-500 max-w-xs">List your first vehicle to reach buyers across SalmaDrive.</p>
                    <a href="{{ route('seller.vehicles.create') }}" class="mt-4 bg-[#F0A820] hover:bg-[#F0A820]/90 text-[#1A1A24] font-semibold px-4 py-2 rounded-lg text-sm transition-colors">List a vehicle</a>
                </div>
            @else
                <table class="w-full text-sm">
                    <tbody class="divide-y divide-neutral-100">
                        @foreach ($recentVehicles as $vehicle)
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
            @endif
        </div>
        </div>
      </div>
    </div>
</x-layouts.app>
