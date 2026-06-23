@php
    // Grouped admin navigation — one source for the left sidebar so the dashboard
    // (and any admin page) isn't a wall of quick-link tiles.
    $groups = [
        'Overview' => [
            ['Dashboard', 'admin.dashboard', 'admin.dashboard'],
        ],
        'Approvals' => [
            ['Applications', 'admin.applications.index', 'admin.applications.*'],
            ['Vendors', 'admin.vendors.index', 'admin.vendors.*'],
            ['Products', 'admin.products.index', 'admin.products.*'],
            ['Vehicles', 'admin.vehicles.index', 'admin.vehicles.*'],
        ],
        'Catalogue' => [
            ['Categories', 'admin.categories.index', 'admin.categories.*'],
            ['Vehicle features', 'admin.vehicle-features.index', 'admin.vehicle-features.*'],
            ['Promotions', 'admin.promotions.index', 'admin.promotions.*'],
        ],
        'Operations' => [
            ['Dispatch', 'admin.dispatch.index', 'admin.dispatch.*'],
            ['Cash sessions', 'admin.cash-sessions.index', 'admin.cash-sessions.*'],
            ['Delivery zones', 'admin.delivery-zones.index', 'admin.delivery-zones.*'],
            ['RFQ', 'admin.rfq.index', 'admin.rfq.*'],
            ['Concierge', 'admin.concierge.index', 'admin.concierge.*'],
        ],
        'Growth' => [
            ['Users', 'admin.users.index', 'admin.users.*'],
            ['Leads', 'admin.leads.index', 'admin.leads.*'],
        ],
        'Money' => [
            ['Payouts', 'admin.payouts.index', 'admin.payouts.*'],
        ],
    ];
@endphp

<nav x-data="{ open: false }" class="lg:w-56 lg:shrink-0">
    {{-- Mobile toggle --}}
    <button type="button" @click="open = !open"
            class="lg:hidden mb-3 w-full flex items-center justify-between border border-neutral-200 rounded-lg px-4 py-2 text-sm font-medium text-neutral-700 bg-white">
        <span>Admin menu</span>
        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M4 6h16M4 12h16M4 18h16"/></svg>
    </button>

    <div class="lg:block" :class="{ 'hidden': !open }">
        <div class="bg-white border border-neutral-200 rounded-xl shadow-sm p-3 space-y-4 lg:sticky lg:top-20">
            @foreach ($groups as $label => $links)
                <div>
                    <p class="px-2 text-[11px] font-semibold text-neutral-400 uppercase tracking-wide mb-1">{{ $label }}</p>
                    <ul class="space-y-0.5">
                        @foreach ($links as [$text, $route, $pattern])
                            @php $active = request()->routeIs($pattern); @endphp
                            <li>
                                <a href="{{ route($route) }}"
                                   class="block rounded-lg px-2 py-1.5 text-sm transition-colors {{ $active ? 'bg-[#F0A820]/15 text-[#B5790F] font-semibold' : 'text-neutral-600 hover:bg-neutral-50 hover:text-neutral-900' }}">
                                    {{ $text }}
                                </a>
                            </li>
                        @endforeach
                    </ul>
                </div>
            @endforeach

            @role('super_admin')
                <div>
                    <p class="px-2 text-[11px] font-semibold text-neutral-400 uppercase tracking-wide mb-1">System</p>
                    <a href="{{ route('admin.settings.index') }}"
                       class="block rounded-lg px-2 py-1.5 text-sm transition-colors {{ request()->routeIs('admin.settings.*') ? 'bg-[#F0A820]/15 text-[#B5790F] font-semibold' : 'text-neutral-600 hover:bg-neutral-50 hover:text-neutral-900' }}">
                        Settings
                    </a>
                </div>
            @endrole
        </div>
    </div>
</nav>
