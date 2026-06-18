<x-layouts.app>
    <x-slot:title>{{ $user->name }}</x-slot:title>

    <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 py-10">

        <div class="flex items-center gap-3 mb-6">
            <a href="{{ route('admin.users.index') }}"
               class="text-sm text-neutral-500 hover:text-neutral-700">← Users</a>
            <span class="text-neutral-300">/</span>
            <span class="text-sm text-neutral-700">{{ $user->name }}</span>
        </div>

        @if (session('status'))
            <div class="mb-5 bg-green-50 border border-green-200 text-green-700 text-sm rounded-lg px-4 py-3">
                {{ session('status') }}
            </div>
        @endif

        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">

            <div class="lg:col-span-2 space-y-5">

                {{-- Account info --}}
                <div class="bg-white border border-neutral-200 rounded-xl shadow-sm p-6">
                    <h2 class="text-base font-semibold text-neutral-900 mb-4">Account Details</h2>
                    <dl class="space-y-3 text-sm">
                        <div class="flex justify-between">
                            <dt class="text-neutral-500">Name</dt>
                            <dd class="font-medium text-neutral-900">{{ $user->name }}</dd>
                        </div>
                        <div class="flex justify-between">
                            <dt class="text-neutral-500">Email</dt>
                            <dd class="text-neutral-900">{{ $user->email }}</dd>
                        </div>
                        <div class="flex justify-between">
                            <dt class="text-neutral-500">Role</dt>
                            <dd class="text-neutral-900 capitalize">{{ str_replace('_', ' ', $user->role ?? '—') }}</dd>
                        </div>
                        <div class="flex justify-between">
                            <dt class="text-neutral-500">Email verified</dt>
                            <dd class="text-neutral-900">
                                @if ($user->email_verified_at)
                                    {{ $user->email_verified_at->format('d M Y') }}
                                @else
                                    <span class="text-amber-600">Not verified</span>
                                @endif
                            </dd>
                        </div>
                        <div class="flex justify-between">
                            <dt class="text-neutral-500">Status</dt>
                            <dd>
                                @php $st = $user->status ?? 'active'; @endphp
                                <span class="px-2 py-0.5 rounded-full text-xs font-medium capitalize
                                    {{ match ($st) {
                                        'active'    => 'bg-green-100 text-green-700',
                                        'suspended' => 'bg-red-100 text-red-700',
                                        'rejected'  => 'bg-red-100 text-red-700',
                                        default     => 'bg-yellow-100 text-yellow-700',
                                    } }}">{{ $st }}</span>
                            </dd>
                        </div>
                        <div class="flex justify-between">
                            <dt class="text-neutral-500">Joined</dt>
                            <dd class="text-neutral-900">{{ $user->created_at->format('d M Y') }}</dd>
                        </div>
                    </dl>
                </div>

                {{-- Listing stats (for private sellers) --}}
                @if ($user->role === 'private_seller')
                    <div class="bg-white border border-neutral-200 rounded-xl shadow-sm p-6">
                        <h2 class="text-base font-semibold text-neutral-900 mb-4">Listing Usage</h2>
                        <div class="grid grid-cols-2 gap-4">
                            <div class="bg-neutral-50 rounded-lg p-4">
                                <div class="text-xs text-neutral-500 mb-1">Vehicles listed</div>
                                <div class="text-2xl font-bold text-neutral-900 tabular-nums">{{ $vehicleCount }}</div>
                                <div class="text-xs text-neutral-400 mt-1">
                                    Limit: {{ $vehicleLimit ?? '∞' }}
                                    @if ($remainingSlots !== null)
                                        · {{ $remainingSlots }} remaining
                                    @endif
                                </div>
                            </div>
                        </div>
                    </div>
                @endif

            </div>

            <div class="space-y-5">
                {{-- Tier management --}}
                <div class="bg-white border border-neutral-200 rounded-xl shadow-sm p-5">
                    <h3 class="text-sm font-semibold text-neutral-700 mb-3">Verification Tier</h3>
                    @if ($user->tier === 'premium')
                        <span class="inline-flex items-center px-3 py-1 rounded-full text-sm font-semibold bg-amber-100 text-amber-700 mb-4">★ Premium</span>
                    @else
                        <span class="inline-flex items-center px-3 py-1 rounded-full text-sm font-medium bg-neutral-100 text-neutral-600 mb-4">Unverified</span>
                    @endif

                    <form method="POST" action="{{ route('admin.users.tier.update', $user) }}"
                          x-data="{ tier: '{{ $user->tier }}' }" class="space-y-3 mt-3">
                        @csrf
                        <div>
                            <select name="tier" x-model="tier"
                                    class="w-full border border-neutral-200 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-[#F0A820]/40">
                                <option value="unverified">Unverified</option>
                                <option value="premium">Premium</option>
                            </select>
                        </div>
                        <button type="submit"
                                class="w-full bg-[#F0A820] hover:bg-[#F0A820]/90 text-[#1A1A24] font-semibold px-4 py-2 rounded-lg text-sm transition-colors">
                            Update Tier
                        </button>
                    </form>
                </div>

                {{-- Privileged user management — super_admin only (R6) --}}
                @role('super_admin')
                @if ($user->id !== auth()->id())
                    <div class="bg-white border border-neutral-200 rounded-xl shadow-sm p-5 space-y-4">
                        <h3 class="text-sm font-semibold text-neutral-700">User Management</h3>

                        {{-- Role change --}}
                        <form method="POST" action="{{ route('admin.users.role', $user) }}" class="space-y-2">
                            @csrf
                            @method('PUT')
                            <label class="block text-xs text-neutral-500">Role</label>
                            <select name="role"
                                    class="w-full border border-neutral-200 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-[#F0A820]/40">
                                @foreach (['super_admin','admin','agent','rider','private_seller','customer'] as $r)
                                    <option value="{{ $r }}" @selected($user->role === $r)>{{ str_replace('_',' ',$r) }}</option>
                                @endforeach
                            </select>
                            <button type="submit" class="w-full border border-neutral-300 hover:bg-neutral-50 text-neutral-700 font-medium px-3 py-2 rounded-lg text-sm">
                                Change role
                            </button>
                        </form>

                        {{-- Suspend / reactivate --}}
                        @if ($user->isSuspended())
                            <form method="POST" action="{{ route('admin.users.reactivate', $user) }}">
                                @csrf
                                <button type="submit" class="w-full bg-green-600 hover:bg-green-700 text-white font-medium px-3 py-2 rounded-lg text-sm">
                                    Reactivate account
                                </button>
                            </form>
                        @else
                            <form method="POST" action="{{ route('admin.users.suspend', $user) }}"
                                  onsubmit="return confirm('Suspend {{ $user->name }}? They will be logged out.');">
                                @csrf
                                <button type="submit" class="w-full bg-red-600 hover:bg-red-700 text-white font-medium px-3 py-2 rounded-lg text-sm">
                                    Suspend account
                                </button>
                            </form>
                        @endif

                        {{-- Reset password --}}
                        <form method="POST" action="{{ route('admin.users.reset-password', $user) }}">
                            @csrf
                            <button type="submit" class="w-full border border-neutral-300 hover:bg-neutral-50 text-neutral-700 font-medium px-3 py-2 rounded-lg text-sm">
                                Reset password
                            </button>
                        </form>

                        {{-- Verify email bypass --}}
                        @unless ($user->email_verified_at)
                            <form method="POST" action="{{ route('admin.users.verify-email', $user) }}">
                                @csrf
                                <button type="submit" class="w-full border border-neutral-300 hover:bg-neutral-50 text-neutral-700 font-medium px-3 py-2 rounded-lg text-sm">
                                    Mark email verified
                                </button>
                            </form>
                        @endunless
                    </div>
                @endif
                @endrole
            </div>

        </div>
    </div>
</x-layouts.app>
