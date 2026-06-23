<x-layouts.app>
    <x-slot:title>Add Vehicle</x-slot:title>

    <div class="max-w-3xl mx-auto px-4 sm:px-6 lg:px-8 py-10">

        <div class="flex items-center gap-3 mb-6">
            <a href="{{ route('vendor.vehicles.index') }}"
               class="text-sm text-neutral-500 hover:text-neutral-700">← Vehicles</a>
            <span class="text-neutral-300">/</span>
            <span class="text-sm text-neutral-700">Add Vehicle</span>
        </div>

        @if (! $type)
            @include('partials.vehicle-type-chooser')
        @else
            <h1 class="text-2xl font-semibold text-neutral-900 mb-2">
                {{ config("vehicle_types.types.{$type}.icon") }} Add a {{ config("vehicle_types.types.{$type}.label") }} Listing
            </h1>
            <p class="text-sm text-neutral-500 mb-6">
                <a href="{{ route('vendor.vehicles.create') }}" class="text-[#3DB8E8] hover:underline">Change type</a>
            </p>

            @if ($errors->any())
                <div class="mb-5 bg-red-50 border border-red-200 text-red-700 text-sm rounded-lg px-4 py-3">
                    <ul class="list-disc list-inside space-y-1">
                        @foreach ($errors->all() as $error)<li>{{ $error }}</li>@endforeach
                    </ul>
                </div>
            @endif

            <form method="POST" action="{{ route('vendor.vehicles.store') }}" class="space-y-5" enctype="multipart/form-data">
                @csrf
                @include('partials.vehicle-form-fields')
                @include('partials.image-upload-create', ['max' => $imageLimit ?? null])

                @include('partials.listing-editor-actions', ['cancelUrl' => route('vendor.vehicles.index')])
            </form>
        @endif
    </div>
</x-layouts.app>
