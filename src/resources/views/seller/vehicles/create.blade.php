<x-layouts.app>
    <x-slot:title>List a Vehicle</x-slot:title>

    <div class="max-w-3xl mx-auto px-4 sm:px-6 lg:px-8 py-10">

        <div class="flex items-center gap-3 mb-6">
            <a href="{{ route('seller.vehicles.index') }}"
               class="text-sm text-neutral-500 hover:text-neutral-700">← My Vehicles</a>
            <span class="text-neutral-300">/</span>
            <span class="text-sm text-neutral-700">List a Vehicle</span>
        </div>

        <h1 class="text-2xl font-semibold text-neutral-900 mb-2">List a Vehicle for Sale</h1>
        <p class="text-sm text-neutral-500 mb-6">Your listing will be reviewed by our team before going live.</p>

        @if ($errors->any())
            <div class="mb-5 bg-red-50 border border-red-200 text-red-700 text-sm rounded-lg px-4 py-3">
                <ul class="list-disc list-inside space-y-1">
                    @foreach ($errors->all() as $error)<li>{{ $error }}</li>@endforeach
                </ul>
            </div>
        @endif

        <form method="POST" action="{{ route('seller.vehicles.store') }}" class="space-y-5" enctype="multipart/form-data">
            @csrf

            @include('partials.vehicle-form-fields')

            @include('partials.image-upload-create', ['max' => $imageLimit ?? null])

            <div class="flex items-center gap-3 pt-2">
                <button type="submit"
                        class="bg-[#F0A820] hover:bg-[#F0A820]/90 text-[#1A1A24] font-semibold px-6 py-2.5 rounded-lg text-sm transition-colors">
                    Submit for Review
                </button>
                <a href="{{ route('seller.vehicles.index') }}"
                   class="text-sm text-neutral-500 hover:text-neutral-700">Cancel</a>
            </div>
        </form>
    </div>
</x-layouts.app>
