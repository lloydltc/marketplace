<x-layouts.app>
    <x-slot:title>Vendor Dashboard</x-slot:title>

    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-10">

        {{-- Status banner --}}
        @if ($vendor?->isPending())
            <div class="mb-6 bg-yellow-50 border border-yellow-200 rounded-xl px-5 py-4 flex items-start gap-3">
                <svg class="w-5 h-5 text-yellow-500 flex-shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                          d="M12 9v2m0 4h.01M10.29 3.86L1.82 18a2 2 0 001.71 3h16.94a2 2 0 001.71-3L13.71 3.86a2 2 0 00-3.42 0z"/>
                </svg>
                <div>
                    <p class="text-sm font-semibold text-yellow-800">Account pending approval</p>
                    <p class="text-sm text-yellow-700 mt-0.5">
                        Your account is being reviewed. Upload your verification documents to speed things up.
                    </p>
                    <div class="mt-2 flex gap-3">
                        <a href="{{ route('vendor.documents.index') }}"
                           class="text-xs font-medium text-yellow-800 underline">Upload documents</a>
                        <a href="{{ route('vendor.bank-accounts.index') }}"
                           class="text-xs font-medium text-yellow-800 underline">Add bank account</a>
                    </div>
                </div>
            </div>
        @elseif ($vendor?->isSuspended())
            <div class="mb-6 bg-red-50 border border-red-200 rounded-xl px-5 py-4">
                <p class="text-sm font-semibold text-red-800">Account suspended</p>
                <p class="text-sm text-red-700 mt-0.5">
                    Your vendor account has been suspended. Contact
                    <a href="mailto:info@salmadrive.co.zw" class="underline">info@salmadrive.co.zw</a> for assistance.
                </p>
            </div>
        @endif

        {{-- Header --}}
        <div class="flex items-center justify-between mb-8">
            <div>
                <h1 class="text-2xl font-semibold text-neutral-900">
                    {{ $vendor?->name ?? 'Vendor Dashboard' }}
                </h1>
                <p class="text-sm text-neutral-500 mt-1">Manage your listings, team, and orders.</p>
            </div>
            @if (Auth::user()->isVendorAdmin())
                <div class="flex items-center gap-2">
                    <a href="{{ route('vendor.profile.show') }}"
                       class="border border-neutral-300 hover:bg-neutral-50 text-neutral-700 font-medium px-4 py-2 rounded-lg text-sm transition-colors">
                        Profile
                    </a>
                    <a href="{{ route('vendor.invitation.create') }}"
                       class="bg-[#F0A820] hover:bg-[#F0A820]/90 text-[#1A1A24] font-medium px-4 py-2 rounded-lg text-sm transition-colors">
                        + Invite member
                    </a>
                </div>
            @endif
        </div>

        {{-- KPI cards --}}
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4 mb-8">
            <div class="bg-white border border-neutral-200 rounded-xl p-5 shadow-sm">
                <p class="text-sm font-medium text-neutral-500">Active Listings</p>
                <p class="mt-2 text-3xl font-semibold text-neutral-900 tabular-nums">{{ number_format($stats['active_listings']) }}</p>
            </div>
            <div class="bg-white border border-neutral-200 rounded-xl p-5 shadow-sm">
                <p class="text-sm font-medium text-neutral-500">Pending Orders</p>
                <p class="mt-2 text-3xl font-semibold text-neutral-900 tabular-nums">{{ number_format($stats['pending_orders']) }}</p>
            </div>
            <div class="bg-white border border-neutral-200 rounded-xl p-5 shadow-sm">
                <p class="text-sm font-medium text-neutral-500">Team Members</p>
                <p class="mt-2 text-3xl font-semibold text-neutral-900 tabular-nums">{{ number_format($stats['team_members']) }}</p>
            </div>
            <div class="bg-white border border-neutral-200 rounded-xl p-5 shadow-sm">
                <p class="text-sm font-medium text-neutral-500">Tier</p>
                <p class="mt-2 text-3xl font-semibold text-neutral-900 capitalize">
                    {{ $stats['tier'] ?? '—' }}
                </p>
            </div>
        </div>

        {{-- Quick links (vendor admin only) --}}
        @if (Auth::user()->isVendorAdmin())
            <div class="grid grid-cols-2 sm:grid-cols-4 gap-3 mb-8">
                @foreach ([
                    ['Products', route('vendor.products.index'), '#1A1A24'],
                    ['Vehicles', route('vendor.vehicles.index'), '#1A1A24'],
                    ['Orders', route('vendor.orders.index'), '#1A1A24'],
                    ['Part Requests', route('vendor.requests.index'), '#3DB8E8'],
                    ['Wallet', route('vendor.wallet.show'), '#2EBD7A'],
                    ['Documents', route('vendor.documents.index'), '#F0A820'],
                    ['Bank Accounts', route('vendor.bank-accounts.index'), '#2EBD7A'],
                    ['Team', route('vendor.team.index'), '#5A6070'],
                ] as [$label, $href, $color])
                    <a href="{{ $href }}"
                       class="bg-white border border-neutral-200 rounded-xl p-4 text-center hover:border-neutral-400 transition-colors shadow-sm">
                        <p class="text-sm font-medium text-neutral-700">{{ $label }}</p>
                    </a>
                @endforeach
            </div>
        @endif

        {{-- Listing management --}}
        @if (Auth::user()->isVendorAdmin())
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <a href="{{ route('vendor.products.create') }}"
                   class="bg-white border border-neutral-200 rounded-xl p-6 shadow-sm hover:border-[#F0A820] transition-colors flex items-center justify-between">
                    <div>
                        <h3 class="text-base font-semibold text-neutral-900">List a product</h3>
                        <p class="text-sm text-neutral-500 mt-0.5">Add spare parts, accessories, or tools.</p>
                    </div>
                    <span class="text-2xl text-[#F0A820]">+</span>
                </a>
                <a href="{{ route('vendor.vehicles.create') }}"
                   class="bg-white border border-neutral-200 rounded-xl p-6 shadow-sm hover:border-[#F0A820] transition-colors flex items-center justify-between">
                    <div>
                        <h3 class="text-base font-semibold text-neutral-900">List a vehicle</h3>
                        <p class="text-sm text-neutral-500 mt-0.5">Add a car, truck, or other vehicle.</p>
                    </div>
                    <span class="text-2xl text-[#F0A820]">+</span>
                </a>
            </div>
        @endif

    </div>
</x-layouts.app>
