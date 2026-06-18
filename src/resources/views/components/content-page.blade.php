@props(['title', 'metaDescription' => null, 'updated' => null])

<x-layouts.app>
    <x-slot:title>{{ $title }}</x-slot:title>
    @isset($metaDescription)
        <x-slot:metaDescription>{{ $metaDescription }}</x-slot:metaDescription>
    @endisset

    <div class="max-w-3xl mx-auto px-4 sm:px-6 lg:px-8 py-12">
        <h1 class="text-3xl font-semibold text-neutral-900 mb-2">{{ $title }}</h1>
        @isset($updated)
            <p class="text-xs text-neutral-400 mb-8">Last updated {{ $updated }}</p>
        @else
            <div class="mb-8"></div>
        @endisset

        <div class="prose prose-neutral prose-sm max-w-none text-neutral-700 space-y-4">
            {{ $slot }}
        </div>
    </div>
</x-layouts.app>
