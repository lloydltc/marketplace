<x-layouts.app>
    <x-slot:title>Admin Dashboard</x-slot:title>

    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
        <div class="lg:flex lg:gap-8">

            {{-- Left sidebar navigation (decongests the dashboard) --}}
            <x-admin-sidebar />

            {{-- Main --}}
            <div class="flex-1 min-w-0 mt-6 lg:mt-0">
                <div class="mb-6">
                    <h1 class="text-2xl font-semibold text-neutral-900">Admin Dashboard</h1>
                    <p class="text-sm text-neutral-500 mt-1">Platform overview — use the menu to manage each area.</p>
                </div>

                {{-- KPI cards --}}
                <div class="grid grid-cols-2 lg:grid-cols-4 gap-4 mb-8">
                    @foreach ([
                        ['Total Users', $stats['total_users'], 'admin.users.index'],
                        ['Active Vendors', $stats['active_vendors'], 'admin.vendors.index'],
                        ['Listings', $stats['listings'], 'admin.products.index'],
                        ['Pending Approvals', $stats['pending_approvals'], 'admin.applications.index'],
                    ] as [$label, $value, $routeName])
                        <a href="{{ route($routeName) }}"
                           class="bg-white border border-neutral-200 rounded-xl p-5 shadow-sm hover:border-[#F0A820] hover:shadow-md transition-all">
                            <p class="text-sm font-medium text-neutral-500">{{ $label }}</p>
                            <p class="mt-2 text-3xl font-semibold text-neutral-900 tabular-nums">{{ number_format($value) }}</p>
                        </a>
                    @endforeach
                </div>

                {{-- Pending-approvals shortcut row --}}
                <div class="bg-white border border-neutral-200 rounded-xl shadow-sm p-6">
                    <h2 class="text-base font-semibold text-neutral-900 mb-1">Needs your attention</h2>
                    <p class="text-sm text-neutral-500 mb-4">{{ number_format($stats['pending_approvals']) }} item(s) awaiting review across applications, products and vehicles.</p>
                    <div class="flex flex-wrap gap-3">
                        <a href="{{ route('admin.applications.index') }}" class="text-sm font-medium text-[#3DB8E8] hover:underline">Review applications →</a>
                        <a href="{{ route('admin.products.index') }}" class="text-sm font-medium text-[#3DB8E8] hover:underline">Review products →</a>
                        <a href="{{ route('admin.vehicles.index') }}" class="text-sm font-medium text-[#3DB8E8] hover:underline">Review vehicles →</a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</x-layouts.app>
