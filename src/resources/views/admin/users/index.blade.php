<x-layouts.app>
    <x-slot:title>Users</x-slot:title>

    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-10">

        <div class="flex items-center justify-between mb-6">
            <div>
                <h1 class="text-2xl font-semibold text-neutral-900">Users</h1>
                <p class="text-sm text-neutral-500 mt-1">Manage user verification tiers and accounts.</p>
            </div>
        </div>

        @if (session('status'))
            <div class="mb-5 bg-green-50 border border-green-200 text-green-700 text-sm rounded-lg px-4 py-3">
                {{ session('status') }}
            </div>
        @endif

        <form method="GET" class="flex flex-wrap gap-3 mb-6">
            <input type="text" name="search" value="{{ request('search') }}"
                   placeholder="Search name or email…"
                   class="flex-1 min-w-48 border border-neutral-200 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-[#F0A820]/40">

            <select name="role"
                    class="border border-neutral-200 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-[#F0A820]/40">
                <option value="">All roles</option>
                <option value="private_seller" @selected(request('role') === 'private_seller')>Private Seller</option>
                <option value="vendor_admin"   @selected(request('role') === 'vendor_admin')>Vendor Admin</option>
                <option value="vendor_worker"  @selected(request('role') === 'vendor_worker')>Vendor Worker</option>
                <option value="agent"          @selected(request('role') === 'agent')>Agent</option>
                <option value="admin"          @selected(request('role') === 'admin')>Admin</option>
            </select>

            <select name="tier"
                    class="border border-neutral-200 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-[#F0A820]/40">
                <option value="">All tiers</option>
                <option value="unverified" @selected(request('tier') === 'unverified')>Unverified</option>
                <option value="premium"    @selected(request('tier') === 'premium')>Premium</option>
            </select>

            <button type="submit"
                    class="bg-[#F0A820] hover:bg-[#F0A820]/90 text-[#1A1A24] font-semibold px-4 py-2 rounded-lg text-sm transition-colors">
                Filter
            </button>
            <a href="{{ route('admin.users.index') }}"
               class="text-sm text-neutral-500 hover:text-neutral-700 px-2 py-2 self-center">Clear</a>
        </form>

        @if ($users->isEmpty())
            <div class="bg-white border border-neutral-200 rounded-xl shadow-sm py-16 text-center">
                <p class="text-neutral-500 text-sm">No users found.</p>
            </div>
        @else
            <div class="bg-white border border-neutral-200 rounded-xl shadow-sm overflow-hidden">
                <table class="w-full text-sm">
                    <thead>
                        <tr class="bg-neutral-50 border-b border-neutral-200">
                            <th class="text-left font-medium text-neutral-500 px-4 py-3">User</th>
                            <th class="text-left font-medium text-neutral-500 px-4 py-3 hidden sm:table-cell">Role</th>
                            <th class="text-center font-medium text-neutral-500 px-4 py-3">Tier</th>
                            <th class="text-left font-medium text-neutral-500 px-4 py-3 hidden md:table-cell">Joined</th>
                            <th class="text-right font-medium text-neutral-500 px-4 py-3">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-neutral-100">
                        @foreach ($users as $user)
                            <tr class="hover:bg-neutral-50 transition-colors">
                                <td class="px-4 py-3">
                                    <div class="font-medium text-neutral-900">{{ $user->name }}</div>
                                    <div class="text-xs text-neutral-400">{{ $user->email }}</div>
                                </td>
                                <td class="px-4 py-3 hidden sm:table-cell">
                                    <span class="text-xs capitalize text-neutral-600">{{ str_replace('_', ' ', $user->role ?? '—') }}</span>
                                </td>
                                <td class="px-4 py-3 text-center">
                                    @if ($user->tier === 'premium')
                                        <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-semibold bg-amber-100 text-amber-700">★ Premium</span>
                                    @else
                                        <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-neutral-100 text-neutral-600">Unverified</span>
                                    @endif
                                </td>
                                <td class="px-4 py-3 text-neutral-500 text-xs hidden md:table-cell">
                                    {{ $user->created_at->format('d M Y') }}
                                </td>
                                <td class="px-4 py-3 text-right">
                                    <a href="{{ route('admin.users.show', $user) }}"
                                       class="text-sm text-[#3DB8E8] hover:underline">Manage</a>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            <div class="mt-5">
                {{ $users->withQueryString()->links() }}
            </div>
        @endif

    </div>
</x-layouts.app>
