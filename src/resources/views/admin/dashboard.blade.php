<x-layouts.app>
    <x-slot:title>Admin Dashboard</x-slot:title>

    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-10">

        <div class="mb-8">
            <h1 class="text-2xl font-semibold text-neutral-900">Admin Dashboard</h1>
            <p class="text-sm text-neutral-500 mt-1">Platform overview and management tools.</p>
        </div>

        {{-- Management quick links --}}
        <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-6 gap-3 mb-8">
            @foreach ([
                ['Applications', 'admin.applications.index'],
                ['Users', 'admin.users.index'],
                ['Vendors', 'admin.vendors.index'],
                ['Categories', 'admin.categories.index'],
                ['Products', 'admin.products.index'],
                ['Vehicles', 'admin.vehicles.index'],
                ['Dispatch', 'admin.dispatch.index'],
                ['Cash sessions', 'admin.cash-sessions.index'],
                ['RFQ', 'admin.rfq.index'],
                ['Concierge', 'admin.concierge.index'],
                ['Promotions', 'admin.promotions.index'],
                ['Payouts', 'admin.payouts.index'],
                ['Settings', 'admin.settings.index'],
            ] as [$label, $routeName])
                <a href="{{ route($routeName) }}"
                   class="bg-white border border-neutral-200 rounded-xl px-4 py-3 text-center text-sm font-medium text-neutral-700
                          hover:border-[#F0A820] hover:shadow-md transition-all">
                    {{ $label }}
                </a>
            @endforeach
        </div>

        {{-- KPI cards --}}
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4 mb-8">
            @foreach ([
                ['Total Users', $stats['total_users']],
                ['Active Vendors', $stats['active_vendors']],
                ['Listings', $stats['listings']],
                ['Pending Approvals', $stats['pending_approvals']],
            ] as [$label, $value])
            <div class="bg-white border border-neutral-200 rounded-xl p-5 shadow-sm">
                <p class="text-sm font-medium text-neutral-500">{{ $label }}</p>
                <p class="mt-2 text-3xl font-semibold text-neutral-900 tabular-nums">{{ number_format($value) }}</p>
            </div>
            @endforeach
        </div>

    </div>
</x-layouts.app>
