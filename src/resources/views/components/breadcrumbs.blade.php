@props([
    'items' => [],   // [['label' => 'Vehicles', 'url' => '/vehicles'], ['label' => 'Toyota Hilux']]
])

<nav aria-label="Breadcrumb" {{ $attributes->class('text-body-sm') }}>
    <ol class="flex items-center gap-1.5 text-[rgb(var(--text-muted))]">
        @foreach ($items as $i => $item)
            @php $last = $loop->last; @endphp
            <li class="flex items-center gap-1.5 min-w-0">
                @if (! $last && ! empty($item['url']))
                    <a href="{{ $item['url'] }}" class="hover:text-[rgb(var(--text))] transition-colors truncate">{{ $item['label'] }}</a>
                @else
                    <span @class(['truncate', 'text-[rgb(var(--text))] font-medium' => $last]) @if ($last) aria-current="page" @endif>{{ $item['label'] }}</span>
                @endif
                @unless ($last)
                    <svg class="size-3.5 shrink-0 text-[rgb(var(--border-strong))]" viewBox="0 0 20 20" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M8 5l4 5-4 5" />
                    </svg>
                @endunless
            </li>
        @endforeach
    </ol>
</nav>
