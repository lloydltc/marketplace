@props([
    'name' => 'q',
    'endpoint',
    'placeholder' => 'Search…',
    'value' => '',
])

{{-- Progressive enhancement: works as a plain text input without JS; Alpine adds
     a live suggestions dropdown when the bundle is loaded. --}}
<div x-data="searchAutocomplete('{{ $endpoint }}', @js($value))"
     class="relative flex-1 min-w-[200px]">
    <input type="text" name="{{ $name }}" autocomplete="off"
           x-model="query"
           @input.debounce.250ms="fetchSuggestions()"
           @focus="open = suggestions.length > 0"
           @keydown.escape="open = false"
           value="{{ $value }}"
           placeholder="{{ $placeholder }}"
           {{ $attributes->merge(['class' => 'w-full border border-neutral-200 rounded-lg px-4 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-[#F0A820]/40']) }}>

    <ul x-show="open && suggestions.length"
        x-cloak
        @click.outside="open = false"
        class="absolute z-30 mt-1 w-full bg-white border border-neutral-200 rounded-lg shadow-lg max-h-64 overflow-auto">
        <template x-for="s in suggestions" :key="s">
            <li @click="select(s)"
                class="px-3 py-2 text-sm text-neutral-700 hover:bg-neutral-50 cursor-pointer"
                x-text="s"></li>
        </template>
    </ul>
</div>
