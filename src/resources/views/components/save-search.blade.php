@props([
    'type' => 'products',
    'active' => false,
])

{{-- "Save this search" — only for signed-in users, and only when filters are active. --}}
@auth
    @if ($active)
        <form method="POST" action="{{ route('saved-searches.store') }}"
              class="flex items-center gap-2">
            @csrf
            <input type="hidden" name="type" value="{{ $type }}">
            @foreach (request()->except(['_token', '_method', 'page', 'name', 'type']) as $key => $val)
                @if (is_string($val) || is_numeric($val))
                    <input type="hidden" name="{{ $key }}" value="{{ $val }}">
                @endif
            @endforeach
            <input type="text" name="name" required maxlength="100" placeholder="Name this search"
                   class="border border-neutral-200 rounded-lg px-3 py-1.5 text-sm focus:outline-none focus:ring-2 focus:ring-[#F0A820]/40">
            @if ($type === 'vehicles')
                {{-- H7: opt into email alerts when new listings match this search --}}
                <label class="flex items-center gap-1.5 text-sm text-neutral-600 whitespace-nowrap">
                    <input type="checkbox" name="notify" value="1"
                           class="rounded border-neutral-300 text-[#F0A820] focus:ring-[#F0A820]/40">
                    Email me new matches
                </label>
            @endif
            <button type="submit"
                    class="text-sm font-medium text-[#3DB8E8] hover:underline whitespace-nowrap">
                ☆ Save search
            </button>
        </form>
    @endif
@endauth
