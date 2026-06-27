<x-layouts.app>
    <x-slot:title>List a vehicle</x-slot:title>

    <div class="max-w-3xl mx-auto px-4 sm:px-6 lg:px-8 py-10">

        <x-breadcrumbs class="mb-6" :items="[
            ['label' => 'My vehicles', 'url' => route('seller.vehicles.index')],
            ['label' => 'List a vehicle'],
        ]" />

        @if (! $type)
            @include('partials.vehicle-type-chooser')
        @else
            <h1 class="text-h1 text-ink mb-2">
                {{ config("vehicle_types.types.{$type}.icon") }} List a {{ config("vehicle_types.types.{$type}.label") }}
            </h1>
            <p class="text-body-sm text-muted mb-6">
                Your listing will be reviewed by our team before going live.
                <a href="{{ route('seller.vehicles.create') }}" class="text-[rgb(var(--info))] hover:underline">Change type</a>
            </p>

            @if ($errors->any())
                <div class="mb-5 bg-[rgb(var(--danger)/0.12)] border border-[rgb(var(--danger)/0.3)] text-[rgb(var(--danger))] text-body-sm rounded-lg px-4 py-3" role="alert">
                    <ul class="list-disc list-inside space-y-1">
                        @foreach ($errors->all() as $error)<li>{{ $error }}</li>@endforeach
                    </ul>
                </div>
            @endif

            @php
                $keys = $errors->keys();
                $hasIn = fn ($arr) => (bool) array_intersect($arr, $keys);
                $imgErr = (bool) collect($keys)->first(fn ($k) => str_starts_with($k, 'images'));
                $start = $hasIn(['make_id', 'model_id', 'year', 'condition', 'color']) ? 0
                    : ($hasIn(['body_type', 'transmission', 'fuel_type', 'engine_cc', 'mileage', 'vin']) ? 1
                    : ($imgErr ? 3 : ($errors->any() ? 2 : 0)));
            @endphp

            <form method="POST" action="{{ route('seller.vehicles.store') }}" enctype="multipart/form-data">
                @csrf
                <x-stepper :steps="['Details', 'Specs', 'Pricing', 'Photos']" :start="$start" hint="Drafts stay private until you publish.">
                    @include('partials.vehicle-form-fields')
                    <div x-show="step === 3" x-cloak>
                        @include('partials.image-upload-create', ['max' => $imageLimit ?? null])
                    </div>

                    <x-slot:actions>
                        <x-button type="submit" name="action" value="publish">Publish</x-button>
                        <x-button type="submit" name="action" value="draft" variant="outline">Save as draft</x-button>
                        <a href="{{ route('seller.vehicles.index') }}" class="self-center text-body-sm text-muted hover:text-[rgb(var(--text))]">Cancel</a>
                    </x-slot:actions>
                </x-stepper>
            </form>
        @endif
    </div>
</x-layouts.app>
