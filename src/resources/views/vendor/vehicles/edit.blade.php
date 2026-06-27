<x-layouts.app>
    <x-slot:title>Edit {{ $vehicle->displayTitle() }}</x-slot:title>

    <div class="max-w-3xl mx-auto px-4 sm:px-6 lg:px-8 py-10">

        <x-breadcrumbs class="mb-6" :items="[
            ['label' => 'Vehicles', 'url' => route('vendor.vehicles.index')],
            ['label' => $vehicle->displayTitle(), 'url' => route('vendor.vehicles.show', $vehicle)],
            ['label' => 'Edit'],
        ]" />

        <h1 class="text-h1 text-ink mb-6">Edit vehicle listing</h1>

        @if ($errors->any())
            <div class="mb-5 bg-[rgb(var(--danger)/0.12)] border border-[rgb(var(--danger)/0.3)] text-[rgb(var(--danger))] text-body-sm rounded-lg px-4 py-3" role="alert">
                <ul class="list-disc list-inside space-y-1">
                    @foreach ($errors->all() as $error)<li>{{ $error }}</li>@endforeach
                </ul>
            </div>
        @endif

        @if ($vehicle->isRejected())
            <div class="mb-5 bg-[rgb(var(--warning)/0.12)] border border-[rgb(var(--warning)/0.3)] text-[rgb(var(--warning))] text-body-sm rounded-lg px-4 py-3">
                This listing was rejected. Saving changes will resubmit it for admin review.
            </div>
        @endif

        @php
            $keys = $errors->keys();
            $hasIn = fn ($arr) => (bool) array_intersect($arr, $keys);
            $start = $hasIn(['make_id', 'model_id', 'year', 'condition', 'color']) ? 0
                : ($hasIn(['body_type', 'transmission', 'fuel_type', 'engine_cc', 'mileage', 'vin']) ? 1
                : ($errors->any() ? 2 : 0));
        @endphp

        <form method="POST" action="{{ route('vendor.vehicles.update', $vehicle) }}">
            @csrf
            @method('PUT')
            <x-stepper :steps="['Details', 'Specs', 'Pricing']" :start="$start">
                @include('partials.vehicle-form-fields')

                <x-slot:actions>
                    <x-button type="submit" name="action" value="publish">Save &amp; publish</x-button>
                    <x-button type="submit" name="action" value="draft" variant="outline">Save as draft</x-button>
                    <a href="{{ route('vendor.vehicles.show', $vehicle) }}" class="self-center text-body-sm text-muted hover:text-[rgb(var(--text))]">Cancel</a>
                </x-slot:actions>
            </x-stepper>
        </form>

        @include('partials.image-manager', [
            'images'       => $images,
            'uploadRoute'  => route('vendor.vehicles.images.store', $vehicle),
            'deleteRoute'  => 'vendor.vehicles.images.destroy',
            'deleteParams' => ['vehicle' => $vehicle],
            'imageLimit'   => $imageLimit,
            'hasViewType'  => true,
        ])

        <div class="mt-8 border-t border-line pt-8">
            <h3 class="text-body-sm font-semibold text-ink mb-3">Danger zone</h3>
            <form method="POST" action="{{ route('vendor.vehicles.destroy', $vehicle) }}"
                  onsubmit="return confirm('Delete this vehicle listing? This cannot be undone.')">
                @csrf
                @method('DELETE')
                <x-button type="submit" variant="danger">Delete listing</x-button>
            </form>
        </div>
    </div>
</x-layouts.app>
