@props([
    'paginator',   // Illuminate\Contracts\Pagination\LengthAwarePaginator
])

@if ($paginator->hasPages())
    @php
        $btn = 'inline-flex items-center justify-center h-10 min-w-10 px-3 rounded-md text-body-sm font-medium transition-colors duration-150';
        $idle = 'text-[rgb(var(--text))] hover:bg-surface-2';
        $active = 'bg-brand text-on-brand';
        $disabled = 'text-[rgb(var(--text-muted))] opacity-50 pointer-events-none';
    @endphp
    <nav role="navigation" aria-label="Pagination" {{ $attributes->class('flex items-center justify-between gap-4') }}>
        <p class="text-caption text-[rgb(var(--text-muted))] tabular-nums">
            {{ $paginator->firstItem() }}–{{ $paginator->lastItem() }} of {{ $paginator->total() }}
        </p>

        <div class="flex items-center gap-1">
            <a href="{{ $paginator->previousPageUrl() }}" rel="prev"
               class="{{ $btn }} {{ $paginator->onFirstPage() ? $disabled : $idle }}"
               @if ($paginator->onFirstPage()) aria-disabled="true" tabindex="-1" @endif aria-label="Previous page">
                <svg class="size-4" viewBox="0 0 20 20" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M12 5l-5 5 5 5" /></svg>
            </a>

            @foreach ($paginator->getUrlRange(max(1, $paginator->currentPage() - 2), min($paginator->lastPage(), $paginator->currentPage() + 2)) as $page => $url)
                <a href="{{ $url }}" class="{{ $btn }} {{ $page == $paginator->currentPage() ? $active : $idle }} tabular-nums"
                   @if ($page == $paginator->currentPage()) aria-current="page" @endif>{{ $page }}</a>
            @endforeach

            <a href="{{ $paginator->nextPageUrl() }}" rel="next"
               class="{{ $btn }} {{ $paginator->hasMorePages() ? $idle : $disabled }}"
               @unless ($paginator->hasMorePages()) aria-disabled="true" tabindex="-1" @endunless aria-label="Next page">
                <svg class="size-4" viewBox="0 0 20 20" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M8 5l5 5-5 5" /></svg>
            </a>
        </div>
    </nav>
@endif
