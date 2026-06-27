@props([
    'role' => null,   // 'admin' | 'vendor' | 'seller' — defaults from the signed-in user
])

@php
    $user = auth()->user();
    $key = $role ?? match ($user?->role) {
        'super_admin', 'admin' => 'admin',
        'vendor_admin', 'vendor_worker' => 'vendor',
        'private_seller' => 'seller',
        default => null,
    };
    $nav = $key ? config("portal_nav.$key") : null;

    $gate = fn ($link) => ! isset($link[3]) || $user?->role === $link[3];
@endphp

@if ($nav)
    <nav x-data="{ open: false }" class="lg:w-60 lg:shrink-0">
        {{-- Mobile toggle --}}
        <button type="button" @click="open = !open"
                class="lg:hidden mb-3 w-full flex items-center justify-between bg-surface border border-line rounded-lg px-4 py-2.5 text-body-sm font-medium text-[rgb(var(--text))]">
            <span x-text="open ? 'Hide menu' : 'Menu'">Menu</span>
            <svg class="size-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M4 6h16M4 12h16M4 18h16"/></svg>
        </button>

        <div class="lg:block" :class="{ 'hidden': !open }">
            <div class="bg-sidebar rounded-xl p-3 space-y-4 lg:sticky lg:top-20">

                @if ($nav['cta'] ?? false)
                    @php [$ctaLabel, $ctaRoute] = $nav['cta']; @endphp
                    <a href="{{ route($ctaRoute) }}"
                       class="flex items-center justify-center gap-1.5 rounded-lg px-3 py-2.5 text-body-sm font-semibold bg-brand hover:bg-brand-hover text-on-brand transition-colors">
                        <svg class="size-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" aria-hidden="true"><path stroke-linecap="round" d="M12 5v14M5 12h14"/></svg>
                        {{ $ctaLabel }}
                    </a>
                @endif

                @foreach ($nav['groups'] as $label => $links)
                    @php $visible = array_filter($links, $gate); @endphp
                    @if ($visible)
                        <div>
                            <p class="px-3 text-overline uppercase text-white/40 mb-1">{{ $label }}</p>
                            <ul class="space-y-0.5">
                                @foreach ($visible as $link)
                                    @php [$text, $routeName, $pattern] = $link; $active = request()->routeIs($pattern); @endphp
                                    <li>
                                        <a href="{{ route($routeName) }}" @if ($active) aria-current="page" @endif
                                           class="block rounded-lg px-3 py-2 text-body-sm transition-colors {{ $active ? 'bg-brand text-on-brand font-semibold' : 'text-white/70 hover:text-white hover:bg-white/10' }}">
                                            {{ $text }}
                                        </a>
                                    </li>
                                @endforeach
                            </ul>
                        </div>
                    @endif
                @endforeach
            </div>
        </div>
    </nav>
@endif
