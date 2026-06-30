<x-layouts.app>
    <x-slot:title>{{ $vendor->name }}</x-slot:title>

    <div class="max-w-5xl mx-auto px-4 sm:px-6 lg:px-8 py-10">

        {{-- Breadcrumb --}}
        <nav class="text-sm text-neutral-500 mb-6">
            <a href="{{ route('admin.vendors.index') }}" class="hover:text-neutral-700">Vendors</a>
            <span class="mx-2">/</span>
            <span class="text-neutral-900">{{ $vendor->name }}</span>
        </nav>

        @if (session('status'))
            <div class="mb-5 bg-green-50 border border-green-200 text-green-700 text-sm rounded-lg px-4 py-3">
                {{ session('status') }}
            </div>
        @endif

        {{-- Header card --}}
        <div class="bg-white border border-neutral-200 rounded-xl shadow-sm p-6 mb-6">
            <div class="flex items-start justify-between">
                <div>
                    <div class="flex items-center gap-3 mb-1">
                        <h1 class="text-2xl font-semibold text-neutral-900">{{ $vendor->name }}</h1>
                        @php
                            $badge = match($vendor->status) {
                                'approved'  => 'bg-green-100 text-green-700',
                                'pending'   => 'bg-yellow-100 text-yellow-700',
                                'suspended' => 'bg-red-100 text-red-700',
                                default     => 'bg-neutral-100 text-neutral-600',
                            };
                        @endphp
                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-semibold {{ $badge }}">
                            {{ ucfirst($vendor->status ?? 'pending') }}
                        </span>
                    </div>
                    <p class="text-sm text-neutral-500">{{ $vendor->contact_email }} · Tier: <strong class="capitalize">{{ $vendor->tier ?? 'bronze' }}</strong> · Commission: {{ $vendor->commission_rate ?? 10 }}%</p>
                </div>

                {{-- Action buttons --}}
                <div class="flex items-center gap-2 flex-wrap justify-end">
                    @if ($vendor->status === 'pending')
                        <button onclick="document.getElementById('approve-modal').classList.remove('hidden')"
                                class="bg-[#2EBD7A] hover:bg-[#2EBD7A]/90 text-white font-medium px-4 py-2 rounded-lg text-sm transition-colors">
                            Approve
                        </button>
                        <button onclick="document.getElementById('reject-modal').classList.remove('hidden')"
                                class="border border-red-300 text-red-600 hover:bg-red-50 font-medium px-4 py-2 rounded-lg text-sm transition-colors">
                            Reject
                        </button>
                    @elseif ($vendor->status === 'approved')
                        <button onclick="document.getElementById('suspend-modal').classList.remove('hidden')"
                                class="border border-red-300 text-red-600 hover:bg-red-50 font-medium px-4 py-2 rounded-lg text-sm transition-colors">
                            Suspend
                        </button>
                    @elseif ($vendor->status === 'suspended')
                        <form method="POST" action="{{ route('admin.vendors.reactivate', $vendor) }}">
                            @csrf
                            <button type="submit"
                                    class="bg-[#3DB8E8] hover:bg-[#3DB8E8]/90 text-white font-medium px-4 py-2 rounded-lg text-sm transition-colors">
                                Reactivate
                            </button>
                        </form>
                    @endif
                </div>
            </div>
        </div>

        {{-- Tier management --}}
        <div class="bg-white border border-neutral-200 rounded-xl shadow-sm p-6 mb-6">
            <div class="flex items-center justify-between">
                <div>
                    <h2 class="text-base font-semibold text-neutral-900">Verification Tier</h2>
                    <p class="text-sm text-neutral-500 mt-0.5">Controls this vendor's listing limits.</p>
                </div>
                <div class="flex items-center gap-3">
                    @php
                        $tierBadge = $vendor->isPremium()
                            ? 'bg-amber-100 text-amber-700'
                            : 'bg-neutral-100 text-neutral-600';
                    @endphp
                    <span class="inline-flex items-center px-3 py-1 rounded-full text-sm font-semibold {{ $tierBadge }}">
                        {{ $vendor->isPremium() ? '★ Premium' : 'Unverified' }}
                    </span>

                    <form method="POST" action="{{ route('admin.vendors.tier.update', $vendor) }}"
                          x-data="{ tier: '{{ $vendor->tier }}' }">
                        @csrf
                        <div class="flex items-center gap-2">
                            <select name="tier" x-model="tier"
                                    class="border border-neutral-200 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-[#F0A820]/40">
                                <option value="unverified">Unverified</option>
                                <option value="premium">Premium</option>
                            </select>
                            <button type="submit"
                                    class="bg-[#F0A820] hover:bg-[#F0A820]/90 text-[#1A1A24] font-semibold px-4 py-2 rounded-lg text-sm transition-colors">
                                Save
                            </button>
                        </div>
                    </form>
                </div>
            </div>

            <div class="mt-4 grid grid-cols-2 sm:grid-cols-4 gap-4 text-sm">
                @php
                    $vLimit  = config("tiers.limits.vendor.{$vendor->tier}.vehicles");
                    $pLimit  = config("tiers.limits.vendor.{$vendor->tier}.products");
                    $vUsed   = $vendor->vehicles()->whereNull('deleted_at')->count();
                    $pUsed   = $vendor->products()->whereNull('deleted_at')->count();
                @endphp
                <div class="bg-neutral-50 rounded-lg p-3">
                    <div class="text-xs text-neutral-500 mb-1">Vehicles used</div>
                    <div class="font-semibold text-neutral-900">{{ $vUsed }} / {{ $vLimit ?? '∞' }}</div>
                </div>
                <div class="bg-neutral-50 rounded-lg p-3">
                    <div class="text-xs text-neutral-500 mb-1">Products used</div>
                    <div class="font-semibold text-neutral-900">{{ $pUsed }} / {{ $pLimit ?? '∞' }}</div>
                </div>
            </div>
        </div>

        {{-- H8: featured-dealer placement (paid) --}}
        <div class="bg-white border border-neutral-200 rounded-xl shadow-sm p-6 mb-6">
            <div class="flex items-center justify-between flex-wrap gap-3">
                <div>
                    <h2 class="text-base font-semibold text-neutral-900">Featured placement</h2>
                    <p class="text-sm text-neutral-500 mt-0.5">
                        @if ($vendor->isFeaturedDealer())
                            Featured until <strong>{{ $vendor->featured_until->toFormattedDateString() }}</strong> — shown in the dealer carousel.
                        @else
                            Not currently featured.
                        @endif
                    </p>
                </div>
                <div class="flex items-center gap-2">
                    @if ($vendor->isApproved())
                        <form method="POST" action="{{ route('admin.vendors.feature', $vendor) }}" class="flex items-center gap-2">
                            @csrf
                            <input type="number" name="days" min="1" max="365" value="30"
                                   class="w-20 border border-neutral-200 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-[#F0A820]/40">
                            <span class="text-sm text-neutral-500">days</span>
                            <button type="submit"
                                    class="bg-[#F0A820] hover:bg-[#F0A820]/90 text-[#1A1A24] font-semibold px-4 py-2 rounded-lg text-sm transition-colors">
                                Feature
                            </button>
                        </form>
                        @if ($vendor->isFeaturedDealer())
                            <form method="POST" action="{{ route('admin.vendors.unfeature', $vendor) }}">
                                @csrf @method('DELETE')
                                <button type="submit" class="border border-neutral-300 text-neutral-600 hover:bg-neutral-50 font-medium px-4 py-2 rounded-lg text-sm transition-colors">Remove</button>
                            </form>
                        @endif
                    @else
                        <span class="text-sm text-neutral-400">Approve the dealer to enable featuring.</span>
                    @endif
                </div>
            </div>
        </div>

        {{-- VB5: trust badge & verification management --}}
        <div class="bg-white border border-neutral-200 rounded-xl shadow-sm p-6 mb-6">
            <div class="flex items-center justify-between flex-wrap gap-3 mb-4">
                <div>
                    <h2 class="text-base font-semibold text-neutral-900">Trust badge &amp; verification</h2>
                    <p class="text-sm text-neutral-500 mt-0.5">
                        Tier:
                        <strong>{{ $vendor->verification_tier ? config('verification.tiers.' . $vendor->verification_tier . '.label') : 'None' }}</strong>
                        · Reputation: <strong class="tabular-nums">{{ $vendor->reputation_score }}/100</strong>
                        @if ($vendor->isBadgeRevoked())<span class="ml-2 text-xs font-semibold bg-red-100 text-red-700 px-2 py-0.5 rounded-full">Revoked</span>@endif
                    </p>
                </div>
            </div>

            {{-- Per-dimension decisions (VB2) --}}
            @php $dims = $vendor->verifications()->pluck('status', 'dimension')->all(); @endphp
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-3 mb-4">
                @foreach (config('verification.dimensions') as $dimension)
                    <div class="flex items-center justify-between gap-2 border border-neutral-200 rounded-lg px-3 py-2">
                        <div>
                            <div class="text-sm font-medium text-neutral-800 capitalize">{{ str_replace('_', ' ', $dimension) }}</div>
                            <div class="text-xs text-neutral-500 capitalize">{{ $dims[$dimension] ?? 'not submitted' }}</div>
                        </div>
                        <form method="POST" action="{{ route('admin.vendors.verifications.update', [$vendor, $dimension]) }}" class="flex gap-1">
                            @csrf
                            <button name="status" value="approved" class="text-xs font-medium bg-green-600 text-white px-2 py-1 rounded">Approve</button>
                            <button name="status" value="rejected" class="text-xs font-medium border border-neutral-300 text-neutral-600 px-2 py-1 rounded">Reject</button>
                        </form>
                    </div>
                @endforeach
            </div>

            {{-- Badge actions (VB4) --}}
            <div class="flex flex-wrap items-end gap-3 pt-3 border-t border-neutral-100">
                @if ($vendor->isBadgeRevoked())
                    <form method="POST" action="{{ route('admin.vendors.badge.update', $vendor) }}">
                        @csrf <input type="hidden" name="action" value="reinstate">
                        <button class="text-sm font-medium bg-[#2EBD7A] text-white px-3 py-2 rounded-lg">Reinstate badge</button>
                    </form>
                @else
                    <form method="POST" action="{{ route('admin.vendors.badge.update', $vendor) }}" class="flex items-end gap-2">
                        @csrf <input type="hidden" name="action" value="revoke">
                        <input type="text" name="reason" placeholder="Revoke reason" class="border border-neutral-200 rounded-lg px-3 py-2 text-sm">
                        <button class="text-sm font-medium border border-red-300 text-red-600 px-3 py-2 rounded-lg">Revoke badge</button>
                    </form>
                @endif

                <form method="POST" action="{{ route('admin.vendors.badge.update', $vendor) }}" class="flex items-end gap-2">
                    @csrf <input type="hidden" name="action" value="grant">
                    <select name="manual_tier" class="border border-neutral-200 rounded-lg px-3 py-2 text-sm">
                        @foreach (config('verification.tiers') as $key => $def)
                            <option value="{{ $key }}">{{ $def['label'] }}</option>
                        @endforeach
                    </select>
                    <button class="text-sm font-medium bg-[#1A1A24] text-white px-3 py-2 rounded-lg">Grant</button>
                </form>
            </div>
        </div>

        {{-- Details grid --}}
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-6">

            {{-- Business info --}}
            <div class="bg-white border border-neutral-200 rounded-xl shadow-sm p-6">
                <h2 class="text-base font-semibold text-neutral-900 mb-4">Business Information</h2>
                @foreach ([
                    'Phone'                 => $vendor->phone,
                    'Address'               => $vendor->address,
                    'Business Registration' => $vendor->business_registration,
                    'Tax ID'                => $vendor->tax_id,
                    'Registered'            => $vendor->created_at?->format('d M Y'),
                    'Verified at'           => $vendor->verified_at?->format('d M Y H:i'),
                ] as $label => $value)
                    @if ($value)
                        <div class="flex justify-between py-1.5 border-b border-neutral-50 last:border-0">
                            <span class="text-sm text-neutral-500">{{ $label }}</span>
                            <span class="text-sm text-neutral-900 font-medium">{{ $value }}</span>
                        </div>
                    @endif
                @endforeach
            </div>

            {{-- Team --}}
            <div class="bg-white border border-neutral-200 rounded-xl shadow-sm p-6">
                <h2 class="text-base font-semibold text-neutral-900 mb-4">Team ({{ $vendor->admins->count() + $vendor->workers->count() }})</h2>
                @foreach ($vendor->admins as $admin)
                    <div class="flex items-center justify-between py-2 border-b border-neutral-50">
                        <span class="text-sm text-neutral-900">{{ $admin->name }}</span>
                        <span class="text-xs bg-[#F0A820]/20 text-[#F0A820] px-2 py-0.5 rounded-full font-medium">Admin</span>
                    </div>
                @endforeach
                @foreach ($vendor->workers as $worker)
                    <div class="flex items-center justify-between py-2 border-b border-neutral-50">
                        <span class="text-sm text-neutral-900">{{ $worker->name }}</span>
                        <span class="text-xs bg-neutral-100 text-neutral-600 px-2 py-0.5 rounded-full font-medium">Worker</span>
                    </div>
                @endforeach
                @if ($vendor->admins->isEmpty() && $vendor->workers->isEmpty())
                    <p class="text-sm text-neutral-400">No team members yet.</p>
                @endif
            </div>

        </div>

        {{-- Bank accounts + documents --}}
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">

            {{-- Bank accounts --}}
            <div class="bg-white border border-neutral-200 rounded-xl shadow-sm p-6">
                <div class="flex items-center justify-between mb-4">
                    <h2 class="text-base font-semibold text-neutral-900">Bank Accounts</h2>
                </div>
                @forelse ($vendor->bankAccounts as $account)
                    <div class="flex items-center justify-between py-2 border-b border-neutral-50 last:border-0">
                        <div>
                            <div class="text-sm font-medium text-neutral-900">{{ $account->bank_name }}</div>
                            <div class="text-xs text-neutral-400">{{ $account->maskedAccountNumber() }} · {{ $account->account_holder }}</div>
                        </div>
                        <div class="flex items-center gap-2">
                            @if ($account->isVerified())
                                <span class="text-xs bg-green-100 text-green-700 px-2 py-0.5 rounded-full font-medium">Verified</span>
                            @else
                                <span class="text-xs bg-yellow-100 text-yellow-700 px-2 py-0.5 rounded-full font-medium">Pending</span>
                                <form method="POST" action="{{ route('admin.vendors.bank.verify', [$vendor, $account]) }}">
                                    @csrf
                                    <button type="submit"
                                            class="text-xs text-[#3DB8E8] hover:underline font-medium">Verify</button>
                                </form>
                            @endif
                        </div>
                    </div>
                @empty
                    <p class="text-sm text-neutral-400">No bank accounts added.</p>
                @endforelse
            </div>

            {{-- Documents --}}
            <div class="bg-white border border-neutral-200 rounded-xl shadow-sm p-6">
                <div class="flex items-center justify-between mb-4">
                    <h2 class="text-base font-semibold text-neutral-900">Documents</h2>
                    <a href="{{ route('admin.vendors.documents', $vendor) }}"
                       class="text-xs text-[#3DB8E8] hover:underline">Review all</a>
                </div>
                @forelse ($vendor->documents as $doc)
                    <div class="flex items-center justify-between py-2 border-b border-neutral-50 last:border-0">
                        <span class="text-sm text-neutral-700">{{ $doc->labelForType() }}</span>
                        @php
                            $docBadge = match($doc->status) {
                                'approved' => 'bg-green-100 text-green-700',
                                'rejected' => 'bg-red-100 text-red-700',
                                default    => 'bg-yellow-100 text-yellow-700',
                            };
                        @endphp
                        <span class="text-xs px-2 py-0.5 rounded-full font-medium {{ $docBadge }}">{{ ucfirst($doc->status) }}</span>
                    </div>
                @empty
                    <p class="text-sm text-neutral-400">No documents uploaded.</p>
                @endforelse
            </div>

        </div>

    </div>

    {{-- Approve modal --}}
    <div id="approve-modal" class="hidden fixed inset-0 z-50 flex items-center justify-center bg-black/50">
        <div class="bg-white rounded-2xl shadow-xl max-w-md w-full mx-4 p-6">
            <h3 class="text-lg font-semibold text-neutral-900 mb-2">Approve Vendor</h3>
            <p class="text-sm text-neutral-600 mb-5">This will approve <strong>{{ $vendor->name }}</strong> and send them a confirmation email.</p>
            <div class="flex justify-between">
                <button onclick="document.getElementById('approve-modal').classList.add('hidden')"
                        class="border border-neutral-300 text-neutral-700 font-medium px-4 py-2 rounded-lg text-sm transition-colors hover:bg-neutral-50">
                    Cancel
                </button>
                <form method="POST" action="{{ route('admin.vendors.approve', $vendor) }}">
                    @csrf
                    <button type="submit"
                            class="bg-[#2EBD7A] hover:bg-[#2EBD7A]/90 text-white font-medium px-5 py-2 rounded-lg text-sm transition-colors">
                        Confirm Approval
                    </button>
                </form>
            </div>
        </div>
    </div>

    {{-- Reject modal --}}
    <div id="reject-modal" class="hidden fixed inset-0 z-50 flex items-center justify-center bg-black/50">
        <div class="bg-white rounded-2xl shadow-xl max-w-md w-full mx-4 p-6">
            <h3 class="text-lg font-semibold text-neutral-900 mb-2">Reject Application</h3>
            <p class="text-sm text-neutral-600 mb-4">Provide a reason. This will be sent to the vendor.</p>
            <form method="POST" action="{{ route('admin.vendors.reject', $vendor) }}" class="space-y-4">
                @csrf
                <div>
                    <label for="reject_reason" class="block text-sm font-medium text-neutral-700 mb-1">Reason</label>
                    <textarea id="reject_reason" name="reason" rows="3" required
                              class="block w-full border border-neutral-300 rounded-lg px-3 py-2 text-sm text-neutral-900
                                     focus:outline-none focus:ring-2 focus:ring-[#F0A820] focus:border-[#F0A820]"
                              placeholder="e.g. Missing business registration certificate…"></textarea>
                </div>
                <div class="flex justify-between">
                    <button type="button" onclick="document.getElementById('reject-modal').classList.add('hidden')"
                            class="border border-neutral-300 text-neutral-700 font-medium px-4 py-2 rounded-lg text-sm hover:bg-neutral-50 transition-colors">
                        Cancel
                    </button>
                    <button type="submit"
                            class="bg-red-600 hover:bg-red-700 text-white font-medium px-5 py-2 rounded-lg text-sm transition-colors">
                        Reject Application
                    </button>
                </div>
            </form>
        </div>
    </div>

    {{-- Suspend modal --}}
    <div id="suspend-modal" class="hidden fixed inset-0 z-50 flex items-center justify-center bg-black/50">
        <div class="bg-white rounded-2xl shadow-xl max-w-md w-full mx-4 p-6">
            <h3 class="text-lg font-semibold text-neutral-900 mb-2">Suspend Vendor</h3>
            <p class="text-sm text-neutral-600 mb-4">Provide a reason. This will be sent to the vendor admin.</p>
            <form method="POST" action="{{ route('admin.vendors.suspend', $vendor) }}" class="space-y-4">
                @csrf
                <div>
                    <label for="suspend_reason" class="block text-sm font-medium text-neutral-700 mb-1">Reason</label>
                    <textarea id="suspend_reason" name="reason" rows="3" required
                              class="block w-full border border-neutral-300 rounded-lg px-3 py-2 text-sm text-neutral-900
                                     focus:outline-none focus:ring-2 focus:ring-[#F0A820] focus:border-[#F0A820]"
                              placeholder="e.g. Violation of marketplace policy…"></textarea>
                </div>
                <div class="flex justify-between">
                    <button type="button" onclick="document.getElementById('suspend-modal').classList.add('hidden')"
                            class="border border-neutral-300 text-neutral-700 font-medium px-4 py-2 rounded-lg text-sm hover:bg-neutral-50 transition-colors">
                        Cancel
                    </button>
                    <button type="submit"
                            class="bg-red-600 hover:bg-red-700 text-white font-medium px-5 py-2 rounded-lg text-sm transition-colors">
                        Suspend Account
                    </button>
                </div>
            </form>
        </div>
    </div>

</x-layouts.app>
