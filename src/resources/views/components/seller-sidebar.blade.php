@php
    $links = [
        ['Dashboard', 'seller.dashboard', 'seller.dashboard'],
        ['My listings', 'seller.vehicles.index', 'seller.vehicles.index'],
        ['Sales & enquiries', 'seller.sales.index', 'seller.sales.*'],
        ['Leads', 'seller.leads.index', 'seller.leads.*'],
        ['Analytics', 'seller.analytics.index', 'seller.analytics.*'],
    ];
@endphp

<nav x-data="{ open: false }" class="lg:w-56 lg:shrink-0">
    <button type="button" @click="open = !open"
            class="lg:hidden mb-3 w-full flex items-center justify-between border border-neutral-200 rounded-lg px-4 py-2 text-sm font-medium text-neutral-700 bg-white">
        <span>Menu</span>
        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M4 6h16M4 12h16M4 18h16"/></svg>
    </button>

    <div class="lg:block" :class="{ 'hidden': !open }">
        <div class="bg-white border border-neutral-200 rounded-xl shadow-sm p-3 space-y-1 lg:sticky lg:top-20">
            @foreach ($links as [$text, $route, $pattern])
                <a href="{{ route($route) }}"
                   class="block rounded-lg px-3 py-2 text-sm transition-colors {{ request()->routeIs($pattern) ? 'bg-[#F0A820]/15 text-[#B5790F] font-semibold' : 'text-neutral-600 hover:bg-neutral-50 hover:text-neutral-900' }}">
                    {{ $text }}
                </a>
            @endforeach
            <a href="{{ route('seller.vehicles.create') }}"
               class="block rounded-lg px-3 py-2 mt-2 text-sm font-semibold text-center bg-[#F0A820] hover:bg-[#F0A820]/90 text-[#1A1A24] transition-colors">
                + List a vehicle
            </a>
        </div>
    </div>
</nav>
