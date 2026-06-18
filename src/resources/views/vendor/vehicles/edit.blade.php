<x-layouts.app>
    <x-slot:title>Edit {{ $vehicle->displayTitle() }}</x-slot:title>

    <div class="max-w-3xl mx-auto px-4 sm:px-6 lg:px-8 py-10">

        <div class="flex items-center gap-3 mb-6">
            <a href="{{ route('vendor.vehicles.index') }}"
               class="text-sm text-neutral-500 hover:text-neutral-700">← Vehicles</a>
            <span class="text-neutral-300">/</span>
            <a href="{{ route('vendor.vehicles.show', $vehicle) }}"
               class="text-sm text-neutral-500 hover:text-neutral-700">{{ $vehicle->displayTitle() }}</a>
            <span class="text-neutral-300">/</span>
            <span class="text-sm text-neutral-700">Edit</span>
        </div>

        <h1 class="text-2xl font-semibold text-neutral-900 mb-6">Edit Vehicle Listing</h1>

        @if ($errors->any())
            <div class="mb-5 bg-red-50 border border-red-200 text-red-700 text-sm rounded-lg px-4 py-3">
                <ul class="list-disc list-inside space-y-1">
                    @foreach ($errors->all() as $error)<li>{{ $error }}</li>@endforeach
                </ul>
            </div>
        @endif

        @if ($vehicle->isRejected())
            <div class="mb-5 bg-amber-50 border border-amber-200 text-amber-700 text-sm rounded-lg px-4 py-3">
                This listing was rejected. Saving changes will resubmit it for admin review.
            </div>
        @endif

        <form method="POST" action="{{ route('vendor.vehicles.update', $vehicle) }}" class="space-y-5">
            @csrf
            @method('PUT')

            @include('partials.vehicle-form-fields')

            <div class="flex items-center gap-3 pt-2">
                <button type="submit"
                        class="bg-[#F0A820] hover:bg-[#F0A820]/90 text-[#1A1A24] font-semibold px-6 py-2.5 rounded-lg text-sm transition-colors">
                    Save Changes
                </button>
                <a href="{{ route('vendor.vehicles.show', $vehicle) }}"
                   class="text-sm text-neutral-500 hover:text-neutral-700">Cancel</a>
            </div>
        </form>

        @include('partials.image-manager', [
            'images'       => $images,
            'uploadRoute'  => route('vendor.vehicles.images.store', $vehicle),
            'deleteRoute'  => 'vendor.vehicles.images.destroy',
            'deleteParams' => ['vehicle' => $vehicle],
            'imageLimit'   => $imageLimit,
            'hasViewType'  => true,
        ])

        <div class="mt-8 border-t border-neutral-200 pt-8">
            <h3 class="text-sm font-semibold text-neutral-700 mb-3">Danger Zone</h3>
            <form method="POST" action="{{ route('vendor.vehicles.destroy', $vehicle) }}"
                  onsubmit="return confirm('Delete this vehicle listing? This cannot be undone.')">
                @csrf
                @method('DELETE')
                <button type="submit"
                        class="bg-red-50 hover:bg-red-100 text-red-600 font-semibold px-4 py-2 rounded-lg text-sm transition-colors border border-red-200">
                    Delete Listing
                </button>
            </form>
        </div>
    </div>
</x-layouts.app>
