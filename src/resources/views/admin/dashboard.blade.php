<x-layouts.app>
    <x-slot:title>Admin dashboard</x-slot:title>

    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
        <div class="lg:flex lg:gap-8">

            {{-- Role-aware dark sidebar --}}
            <x-admin-sidebar />

            {{-- Main --}}
            <div class="flex-1 min-w-0 mt-6 lg:mt-0">
                <div class="mb-6">
                    <h1 class="text-h1 text-ink">Admin dashboard</h1>
                    <p class="text-body-sm text-muted mt-1">Platform overview — use the menu to manage each area.</p>
                </div>

                {{-- KPI tiles --}}
                <div class="grid grid-cols-2 lg:grid-cols-4 gap-5 mb-8">
                    @foreach ([
                        ['Total users', $stats['total_users'], 'admin.users.index'],
                        ['Active vendors', $stats['active_vendors'], 'admin.vendors.index'],
                        ['Listings', $stats['listings'], 'admin.products.index'],
                        ['Pending approvals', $stats['pending_approvals'], 'admin.applications.index'],
                    ] as [$label, $value, $routeName])
                        <a href="{{ route($routeName) }}"
                           class="group bg-surface border border-line rounded-lg p-5 shadow-e1 hover:border-brand hover:shadow-e2 transition-all">
                            <p class="text-overline uppercase text-muted">{{ $label }}</p>
                            <p class="mt-2 text-display-lg tabular-nums text-ink leading-none group-hover:text-brand transition-colors">{{ number_format($value) }}</p>
                        </a>
                    @endforeach
                </div>

                {{-- Needs attention --}}
                <x-card padding="lg">
                    <h2 class="text-h4 text-ink mb-1">Needs your attention</h2>
                    <p class="text-body-sm text-muted mb-4">{{ number_format($stats['pending_approvals']) }} item(s) awaiting review across applications, products and vehicles.</p>
                    <div class="flex flex-wrap gap-4">
                        <a href="{{ route('admin.applications.index') }}" class="text-body-sm font-semibold text-[rgb(var(--info))] hover:underline">Review applications →</a>
                        <a href="{{ route('admin.products.index') }}" class="text-body-sm font-semibold text-[rgb(var(--info))] hover:underline">Review products →</a>
                        <a href="{{ route('admin.vehicles.index') }}" class="text-body-sm font-semibold text-[rgb(var(--info))] hover:underline">Review vehicles →</a>
                    </div>
                </x-card>
            </div>
        </div>
    </div>
</x-layouts.app>
