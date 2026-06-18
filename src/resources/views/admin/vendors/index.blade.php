<x-layouts.app>
    <x-slot:title>Vendor Management</x-slot:title>

    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-10">

        {{-- Header --}}
        <div class="flex items-center justify-between mb-6">
            <div>
                <h1 class="text-2xl font-semibold text-neutral-900">Vendor Management</h1>
                <p class="text-sm text-neutral-500 mt-1">Review, approve and manage vendor accounts.</p>
            </div>
        </div>

        {{-- Flash status --}}
        @if (session('status'))
            <div class="mb-5 bg-green-50 border border-green-200 text-green-700 text-sm rounded-lg px-4 py-3">
                {{ session('status') }}
            </div>
        @endif

        {{-- Filters --}}
        <form method="GET" class="flex flex-wrap items-center gap-3 mb-6">
            <input type="text" name="search" value="{{ request('search') }}"
                   placeholder="Search name, email, tax ID…"
                   class="border border-neutral-300 rounded-lg px-3 py-2 text-sm text-neutral-900 placeholder-neutral-400
                          focus:outline-none focus:ring-2 focus:ring-[#F0A820] focus:border-[#F0A820] w-64">

            <select name="status"
                    class="border border-neutral-300 rounded-lg px-3 py-2 text-sm text-neutral-700
                           focus:outline-none focus:ring-2 focus:ring-[#F0A820]">
                <option value="">All statuses</option>
                @foreach (['pending', 'approved', 'suspended', 'closed'] as $s)
                    <option value="{{ $s }}" @selected(request('status') === $s)>{{ ucfirst($s) }}</option>
                @endforeach
            </select>

            <select name="tier"
                    class="border border-neutral-300 rounded-lg px-3 py-2 text-sm text-neutral-700
                           focus:outline-none focus:ring-2 focus:ring-[#F0A820]">
                <option value="">All tiers</option>
                @foreach (['bronze', 'silver', 'gold', 'platinum'] as $t)
                    <option value="{{ $t }}" @selected(request('tier') === $t)>{{ ucfirst($t) }}</option>
                @endforeach
            </select>

            <button type="submit"
                    class="bg-[#1A1A24] hover:bg-[#080810] text-white text-sm font-medium px-4 py-2 rounded-lg transition-colors">
                Filter
            </button>
            @if (request()->hasAny(['search','status','tier']))
                <a href="{{ route('admin.vendors.index') }}"
                   class="text-sm text-neutral-500 hover:text-neutral-700 transition-colors">Clear</a>
            @endif
        </form>

        {{-- Table --}}
        <div class="bg-white border border-neutral-200 rounded-xl shadow-sm overflow-hidden">
            <div class="overflow-x-auto">
                <table class="w-full text-sm">
                    <thead>
                        <tr class="border-b border-neutral-200 bg-neutral-50">
                            <th class="text-left font-medium text-neutral-500 px-4 py-3">Vendor</th>
                            <th class="text-left font-medium text-neutral-500 px-4 py-3">Status</th>
                            <th class="text-left font-medium text-neutral-500 px-4 py-3">Tier</th>
                            <th class="text-left font-medium text-neutral-500 px-4 py-3">Commission</th>
                            <th class="text-left font-medium text-neutral-500 px-4 py-3">Registered</th>
                            <th class="text-right font-medium text-neutral-500 px-4 py-3">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-neutral-100">
                        @forelse ($vendors as $vendor)
                            <tr class="hover:bg-neutral-50 transition-colors">
                                <td class="px-4 py-3">
                                    <div class="font-medium text-neutral-900">{{ $vendor->name }}</div>
                                    <div class="text-xs text-neutral-400">{{ $vendor->contact_email }}</div>
                                </td>
                                <td class="px-4 py-3">
                                    @php
                                        $badge = match($vendor->status) {
                                            'approved'  => 'bg-green-100 text-green-700',
                                            'pending'   => 'bg-yellow-100 text-yellow-700',
                                            'suspended' => 'bg-red-100 text-red-700',
                                            default     => 'bg-neutral-100 text-neutral-600',
                                        };
                                    @endphp
                                    <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium {{ $badge }}">
                                        {{ ucfirst($vendor->status) }}
                                    </span>
                                </td>
                                <td class="px-4 py-3 text-neutral-700 capitalize">{{ $vendor->tier ?? 'bronze' }}</td>
                                <td class="px-4 py-3 text-neutral-700 tabular-nums">{{ $vendor->commission_rate ?? 10 }}%</td>
                                <td class="px-4 py-3 text-neutral-500 text-xs">{{ $vendor->created_at->format('d M Y') }}</td>
                                <td class="px-4 py-3 text-right">
                                    <a href="{{ route('admin.vendors.show', $vendor) }}"
                                       class="text-sm text-[#3DB8E8] hover:underline font-medium">Review</a>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="px-4 py-12 text-center text-neutral-400 text-sm">
                                    No vendors found.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            @if ($vendors->hasPages())
                <div class="px-4 py-3 border-t border-neutral-100">
                    {{ $vendors->withQueryString()->links() }}
                </div>
            @endif
        </div>

    </div>
</x-layouts.app>
