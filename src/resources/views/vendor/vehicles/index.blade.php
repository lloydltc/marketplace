<x-layouts.app>
    <x-slot:title>My vehicle listings</x-slot:title>

    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-10">

        <div class="flex items-center justify-between gap-4 mb-6">
            <div>
                <h1 class="text-h1 text-ink">Vehicle listings</h1>
                <p class="text-body-sm text-muted mt-1">Manage your dealership's vehicle listings.</p>
            </div>
            @can('create', App\Modules\Vehicles\Models\Vehicle::class)
                <x-button :href="route('vendor.vehicles.create')">+ Add vehicle</x-button>
            @endcan
        </div>

        @if (session('status'))
            <div class="mb-5 bg-[rgb(var(--success)/0.12)] border border-[rgb(var(--success)/0.3)] text-[rgb(var(--success))] text-body-sm rounded-lg px-4 py-3" role="status">
                {{ session('status') }}
            </div>
        @endif

        @include('partials.listing-limit-banner', ['remaining' => $remainingSlots, 'limit' => $vehicleLimit, 'type' => 'vehicle'])

        <form method="GET" class="flex flex-wrap items-end gap-3 mb-6">
            <x-select name="status" class="!w-auto min-w-[11rem]">
                <option value="">All statuses</option>
                <option value="pending" @selected(request('status') === 'pending')>Pending</option>
                <option value="active" @selected(request('status') === 'active')>Active</option>
                <option value="inactive" @selected(request('status') === 'inactive')>Inactive</option>
                <option value="rejected" @selected(request('status') === 'rejected')>Rejected</option>
            </x-select>
            <x-button type="submit" variant="outline">Filter</x-button>
            <a href="{{ route('vendor.vehicles.index') }}" class="self-center text-body-sm text-muted hover:text-[rgb(var(--text))]">Clear</a>
        </form>

        @if ($vehicles->isEmpty())
            <x-empty title="No vehicle listings yet" message="Add your first vehicle to start receiving enquiries.">
                @can('create', App\Modules\Vehicles\Models\Vehicle::class)
                    <x-button :href="route('vendor.vehicles.create')">Add a vehicle</x-button>
                @endcan
            </x-empty>
        @else
            <x-table>
                <x-slot:head>
                    <th>Vehicle</th>
                    <th class="!text-center hidden sm:table-cell">Condition</th>
                    <th class="!text-right hidden sm:table-cell">Price (ZWL)</th>
                    <th class="!text-center">Status</th>
                    <th class="!text-right">Actions</th>
                </x-slot:head>
                @foreach ($vehicles as $vehicle)
                    @php
                        $badge = match ($vehicle->status) {
                            'active'   => 'bg-[rgb(var(--success)/0.15)] text-[rgb(var(--success))]',
                            'pending'  => 'bg-[rgb(var(--warning)/0.15)] text-[rgb(var(--warning))]',
                            'rejected' => 'bg-[rgb(var(--danger)/0.15)] text-[rgb(var(--danger))]',
                            default    => 'bg-surface-2 text-muted',
                        };
                    @endphp
                    <tr>
                        <td>
                            <div class="font-medium text-ink">{{ $vehicle->displayTitle() }}</div>
                            @if ($vehicle->vin)<div class="text-caption text-muted font-mono mt-0.5">VIN: {{ $vehicle->vin }}</div>@endif
                        </td>
                        <td class="text-center capitalize text-[rgb(var(--text-muted))] hidden sm:table-cell">{{ $vehicle->condition }}</td>
                        <td class="text-right tabular-nums hidden sm:table-cell">{{ $vehicle->primaryPrice() }}</td>
                        <td class="text-center">
                            <span class="inline-flex items-center px-2 py-0.5 rounded-full text-caption font-medium {{ $badge }}">{{ ucfirst($vehicle->status) }}</span>
                        </td>
                        <td class="text-right space-x-3 whitespace-nowrap">
                            <a href="{{ route('vendor.vehicles.show', $vehicle) }}" class="text-body-sm text-muted hover:text-[rgb(var(--text))]">View</a>
                            @if ($vehicle->canBeEdited())
                                <a href="{{ route('vendor.vehicles.edit', $vehicle) }}" class="text-body-sm text-[rgb(var(--info))] hover:underline">Edit</a>
                            @endif
                        </td>
                    </tr>
                @endforeach
            </x-table>

            <x-pagination :paginator="$vehicles->withQueryString()" class="mt-5" />
        @endif
    </div>
</x-layouts.app>
