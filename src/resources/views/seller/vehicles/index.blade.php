<x-layouts.app>
    <x-slot:title>My Vehicle Listings</x-slot:title>

    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-10">

        <div class="flex items-center justify-between mb-6">
            <div>
                <h1 class="text-2xl font-semibold text-neutral-900">My Vehicle Listings</h1>
                <p class="text-sm text-neutral-500 mt-1">List and manage vehicles you're selling privately.</p>
            </div>
            <a href="{{ route('seller.vehicles.create') }}"
               class="bg-[#F0A820] hover:bg-[#F0A820]/90 text-[#1A1A24] font-semibold px-4 py-2 rounded-lg text-sm transition-colors">
                + Add Vehicle
            </a>
        </div>

        @if (session('status'))
            <div class="mb-5 bg-green-50 border border-green-200 text-green-700 text-sm rounded-lg px-4 py-3">
                {{ session('status') }}
            </div>
        @endif

        @include('partials.listing-limit-banner', ['remaining' => $remainingSlots, 'limit' => $vehicleLimit, 'type' => 'vehicle'])

        <form method="GET" class="flex flex-wrap gap-3 mb-6">
            <select name="status"
                    class="border border-neutral-200 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-[#F0A820]/40">
                <option value="">All statuses</option>
                <option value="pending"  @selected(request('status') === 'pending')>Pending</option>
                <option value="active"   @selected(request('status') === 'active')>Active</option>
                <option value="inactive" @selected(request('status') === 'inactive')>Inactive</option>
                <option value="rejected" @selected(request('status') === 'rejected')>Rejected</option>
            </select>
            <button type="submit"
                    class="bg-[#F0A820] hover:bg-[#F0A820]/90 text-[#1A1A24] font-semibold px-4 py-2 rounded-lg text-sm transition-colors">
                Filter
            </button>
            <a href="{{ route('seller.vehicles.index') }}"
               class="text-sm text-neutral-500 hover:text-neutral-700 px-2 py-2 self-center">Clear</a>
        </form>

        @if ($vehicles->isEmpty())
            <div class="bg-white border border-neutral-200 rounded-xl shadow-sm py-16 text-center">
                <p class="text-neutral-500 text-sm">You haven't listed any vehicles yet.</p>
                <a href="{{ route('seller.vehicles.create') }}"
                   class="mt-3 inline-block text-sm text-[#3DB8E8] hover:underline">List your first vehicle</a>
            </div>
        @else
            <div class="bg-white border border-neutral-200 rounded-xl shadow-sm overflow-hidden">
                <table class="w-full text-sm">
                    <thead>
                        <tr class="bg-neutral-50 border-b border-neutral-200">
                            <th class="text-left font-medium text-neutral-500 px-4 py-3">Vehicle</th>
                            <th class="text-center font-medium text-neutral-500 px-4 py-3 hidden sm:table-cell">Condition</th>
                            <th class="text-right font-medium text-neutral-500 px-4 py-3 hidden sm:table-cell">Price (ZWL)</th>
                            <th class="text-center font-medium text-neutral-500 px-4 py-3">Status</th>
                            <th class="text-right font-medium text-neutral-500 px-4 py-3">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-neutral-100">
                        @foreach ($vehicles as $vehicle)
                            <tr class="hover:bg-neutral-50 transition-colors">
                                <td class="px-4 py-3">
                                    <div class="font-medium text-neutral-900">{{ $vehicle->displayTitle() }}</div>
                                    @if ($vehicle->vin)
                                        <div class="text-xs text-neutral-400 font-mono mt-0.5">VIN: {{ $vehicle->vin }}</div>
                                    @endif
                                </td>
                                <td class="px-4 py-3 text-center hidden sm:table-cell">
                                    <span class="text-xs capitalize text-neutral-600">{{ $vehicle->condition }}</span>
                                </td>
                                <td class="px-4 py-3 text-right tabular-nums hidden sm:table-cell">
                                    {{ number_format($vehicle->price_zwl, 2) }}
                                </td>
                                <td class="px-4 py-3 text-center">
                                    @php
                                        $badge = match($vehicle->status) {
                                            'active'   => 'bg-green-100 text-green-700',
                                            'pending'  => 'bg-amber-100 text-amber-700',
                                            'rejected' => 'bg-red-100 text-red-700',
                                            default    => 'bg-neutral-100 text-neutral-600',
                                        };
                                    @endphp
                                    <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium {{ $badge }}">
                                        {{ ucfirst($vehicle->status) }}
                                    </span>
                                </td>
                                <td class="px-4 py-3 text-right space-x-3">
                                    <a href="{{ route('seller.vehicles.show', $vehicle) }}"
                                       class="text-sm text-neutral-500 hover:text-neutral-700">View</a>
                                    @if ($vehicle->canBeEdited())
                                        <a href="{{ route('seller.vehicles.edit', $vehicle) }}"
                                           class="text-sm text-[#3DB8E8] hover:underline">Edit</a>
                                    @endif
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            <div class="mt-5">
                {{ $vehicles->withQueryString()->links() }}
            </div>
        @endif

    </div>
</x-layouts.app>
