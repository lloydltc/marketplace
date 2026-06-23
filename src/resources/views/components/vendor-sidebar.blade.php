@php
    $isAdmin = auth()->user()?->isVendorAdmin();

    // Available to all vendor members (admin + worker).
    $core = [
        ['Dashboard', 'vendor.dashboard', 'vendor.dashboard'],
        ['Products', 'vendor.products.index', 'vendor.products.*'],
        ['Vehicles', 'vendor.vehicles.index', 'vendor.vehicles.*'],
        ['Sales', 'vendor.orders.index', 'vendor.orders.*'],
        ['Leads', 'vendor.leads.index', 'vendor.leads.*'],
        ['Analytics', 'vendor.analytics.index', 'vendor.analytics.*'],
        ['Part requests', 'vendor.requests.index', 'vendor.requests.*'],
    ];

    // Vendor-admin only (money / account management).
    $manage = [
        ['Wallet', 'vendor.wallet.show', 'vendor.wallet.*'],
        ['Team', 'vendor.team.index', 'vendor.team.*'],
        ['Documents', 'vendor.documents.index', 'vendor.documents.*'],
        ['Bank accounts', 'vendor.bank-accounts.index', 'vendor.bank-accounts.*'],
        ['Profile', 'vendor.profile.show', 'vendor.profile.*'],
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
            @foreach ($core as [$text, $route, $pattern])
                <a href="{{ route($route) }}"
                   class="block rounded-lg px-3 py-2 text-sm transition-colors {{ request()->routeIs($pattern) ? 'bg-[#F0A820]/15 text-[#B5790F] font-semibold' : 'text-neutral-600 hover:bg-neutral-50 hover:text-neutral-900' }}">
                    {{ $text }}
                </a>
            @endforeach

            @if ($isAdmin)
                <div class="pt-2 mt-2 border-t border-neutral-100">
                    <p class="px-3 text-[11px] font-semibold text-neutral-400 uppercase tracking-wide mb-1">Manage</p>
                    @foreach ($manage as [$text, $route, $pattern])
                        <a href="{{ route($route) }}"
                           class="block rounded-lg px-3 py-2 text-sm transition-colors {{ request()->routeIs($pattern) ? 'bg-[#F0A820]/15 text-[#B5790F] font-semibold' : 'text-neutral-600 hover:bg-neutral-50 hover:text-neutral-900' }}">
                            {{ $text }}
                        </a>
                    @endforeach
                </div>
            @endif
        </div>
    </div>
</nav>
