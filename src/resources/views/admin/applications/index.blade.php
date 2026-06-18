<x-layouts.app>
    <x-slot:title>Pending Applications</x-slot:title>

    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-10">

        <div class="flex items-center justify-between mb-6">
            <div>
                <h1 class="text-2xl font-semibold text-neutral-900">Pending Applications</h1>
                <p class="text-sm text-neutral-500 mt-1">Review and approve vendor and private seller applications.</p>
            </div>
        </div>

        @if (session('status'))
            <div class="mb-5 bg-green-50 border border-green-200 text-green-700 text-sm rounded-lg px-4 py-3">
                {{ session('status') }}
            </div>
        @endif

        {{-- Filters --}}
        <form method="GET" class="flex flex-wrap items-center gap-3 mb-6">
            <select name="role"
                    class="border border-neutral-300 rounded-lg px-3 py-2 text-sm text-neutral-700
                           focus:outline-none focus:ring-2 focus:ring-[#F0A820]">
                <option value="">All types</option>
                <option value="vendor_admin" @selected(request('role') === 'vendor_admin')>Vendor</option>
                <option value="private_seller" @selected(request('role') === 'private_seller')>Private Seller</option>
            </select>
            <button type="submit"
                    class="bg-[#1A1A24] hover:bg-[#080810] text-white text-sm font-medium px-4 py-2 rounded-lg transition-colors">
                Filter
            </button>
            @if (request('role'))
                <a href="{{ route('admin.applications.index') }}"
                   class="text-sm text-neutral-500 hover:text-neutral-700">Clear</a>
            @endif
        </form>

        <div class="bg-white border border-neutral-200 rounded-xl shadow-sm overflow-hidden">
            <div class="overflow-x-auto">
                <table class="w-full text-sm">
                    <thead>
                        <tr class="border-b border-neutral-200 bg-neutral-50">
                            <th class="text-left font-medium text-neutral-500 px-4 py-3">Applicant</th>
                            <th class="text-left font-medium text-neutral-500 px-4 py-3">Type</th>
                            <th class="text-left font-medium text-neutral-500 px-4 py-3">Business / Info</th>
                            <th class="text-left font-medium text-neutral-500 px-4 py-3">Applied</th>
                            <th class="text-right font-medium text-neutral-500 px-4 py-3">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-neutral-100">
                        @forelse ($applications as $user)
                            <tr class="hover:bg-neutral-50 transition-colors">
                                <td class="px-4 py-3">
                                    <div class="font-medium text-neutral-900">{{ $user->name }}</div>
                                    <div class="text-xs text-neutral-400">{{ $user->email }}</div>
                                </td>
                                <td class="px-4 py-3">
                                    @if ($user->role === 'vendor_admin')
                                        <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-blue-100 text-blue-700">Vendor</span>
                                    @else
                                        <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-purple-100 text-purple-700">Private Seller</span>
                                    @endif
                                </td>
                                <td class="px-4 py-3 text-neutral-600 text-xs">
                                    @if ($user->role === 'vendor_admin' && $vendor = $user->vendors->first())
                                        <div class="font-medium text-neutral-800">{{ $vendor->name }}</div>
                                        @if ($vendor->phone)
                                            <div>{{ $vendor->phone }}</div>
                                        @endif
                                        @if ($vendor->address)
                                            <div class="text-neutral-400">{{ Str::limit($vendor->address, 50) }}</div>
                                        @endif
                                        @if ($vendor->business_registration)
                                            <div class="text-neutral-400">Reg: {{ $vendor->business_registration }}</div>
                                        @endif
                                    @else
                                        <span class="text-neutral-400">—</span>
                                    @endif
                                </td>
                                <td class="px-4 py-3 text-neutral-500 text-xs">{{ $user->created_at->format('d M Y') }}</td>
                                <td class="px-4 py-3 text-right">
                                    <div class="flex items-center justify-end gap-3">
                                        {{-- Approve --}}
                                        <form method="POST" action="{{ route('admin.applications.approve', $user) }}"
                                              onsubmit="return confirm('Approve this application?')">
                                            @csrf
                                            <button type="submit"
                                                    class="text-sm font-medium text-green-600 hover:text-green-800 transition-colors">
                                                Approve
                                            </button>
                                        </form>

                                        {{-- Reject --}}
                                        <button type="button"
                                                onclick="document.getElementById('reject-{{ $user->id }}').classList.toggle('hidden')"
                                                class="text-sm font-medium text-red-500 hover:text-red-700 transition-colors">
                                            Reject
                                        </button>
                                    </div>

                                    {{-- Rejection reason form (hidden by default) --}}
                                    <div id="reject-{{ $user->id }}" class="hidden mt-2">
                                        <form method="POST" action="{{ route('admin.applications.reject', $user) }}">
                                            @csrf
                                            <textarea name="reason" rows="2" required placeholder="Reason for rejection…"
                                                      class="w-full border border-neutral-300 rounded-lg px-3 py-2 text-xs text-neutral-900
                                                             focus:outline-none focus:ring-1 focus:ring-red-400 mb-1 resize-none"></textarea>
                                            <button type="submit"
                                                    class="w-full bg-red-500 hover:bg-red-600 text-white text-xs font-medium py-1.5 rounded-lg transition-colors">
                                                Confirm rejection
                                            </button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5" class="px-4 py-12 text-center text-neutral-400 text-sm">
                                    No pending applications.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            @if ($applications->hasPages())
                <div class="px-4 py-3 border-t border-neutral-100">
                    {{ $applications->withQueryString()->links() }}
                </div>
            @endif
        </div>

    </div>
</x-layouts.app>
