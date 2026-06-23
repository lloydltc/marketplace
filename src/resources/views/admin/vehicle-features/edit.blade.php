<x-layouts.app>
    <x-slot:title>Edit Vehicle Feature</x-slot:title>
    <div class="max-w-2xl mx-auto px-4 sm:px-6 lg:px-8 py-10">
        <div class="flex items-center gap-3 mb-6">
            <a href="{{ route('admin.vehicle-features.index') }}" class="text-sm text-neutral-500 hover:text-neutral-700">← Features</a>
            <span class="text-neutral-300">/</span>
            <span class="text-sm text-neutral-700">{{ $feature->name }}</span>
        </div>
        @include('admin.vehicle-features._form')
    </div>
</x-layouts.app>
