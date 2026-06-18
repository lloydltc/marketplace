<x-layouts.app>
    <x-slot:title>Vendor Profile</x-slot:title>

    <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 py-10">

        {{-- Status banner --}}
        @if ($vendor->isPending())
            <div class="mb-6 bg-yellow-50 border border-yellow-200 rounded-xl px-5 py-4 flex items-start gap-3">
                <svg class="w-5 h-5 text-yellow-500 flex-shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                          d="M12 9v2m0 4h.01M10.29 3.86L1.82 18a2 2 0 001.71 3h16.94a2 2 0 001.71-3L13.71 3.86a2 2 0 00-3.42 0z"/>
                </svg>
                <div>
                    <p class="text-sm font-semibold text-yellow-800">Account pending approval</p>
                    <p class="text-sm text-yellow-700 mt-0.5">
                        Upload your business documents and bank account details to speed up your review.
                        <a href="{{ route('vendor.documents.index') }}" class="underline font-medium">Upload documents →</a>
                    </p>
                </div>
            </div>
        @elseif ($vendor->isSuspended())
            <div class="mb-6 bg-red-50 border border-red-200 rounded-xl px-5 py-4">
                <p class="text-sm font-semibold text-red-800">Account suspended</p>
                <p class="text-sm text-red-700 mt-0.5">Contact <a href="mailto:info@salmadrive.co.zw" class="underline">info@salmadrive.co.zw</a> for assistance.</p>
            </div>
        @endif

        <div class="flex items-center justify-between mb-6">
            <h1 class="text-2xl font-semibold text-neutral-900">{{ $vendor->name }}</h1>
            @if (Auth::user()->isVendorAdmin())
                <a href="{{ route('vendor.profile.edit') }}"
                   class="border border-neutral-300 hover:bg-neutral-50 text-neutral-700 font-medium px-4 py-2 rounded-lg text-sm transition-colors">
                    Edit Profile
                </a>
            @endif
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
            <div class="bg-white border border-neutral-200 rounded-xl shadow-sm p-6">
                <h2 class="text-base font-semibold text-neutral-900 mb-4">Business Details</h2>
                @foreach ([
                    'Email'                 => $vendor->contact_email,
                    'Phone'                 => $vendor->phone ?? '—',
                    'Address'               => $vendor->address ?? '—',
                    'Business Registration' => $vendor->business_registration ?? 'Not provided',
                    'Tax ID'                => $vendor->tax_id ?? 'Not provided',
                    'Tier'                  => ucfirst($vendor->tier ?? 'bronze'),
                    'Commission Rate'       => ($vendor->commission_rate ?? 10) . '%',
                ] as $label => $value)
                    <div class="flex justify-between py-2 border-b border-neutral-50 last:border-0">
                        <span class="text-sm text-neutral-500">{{ $label }}</span>
                        <span class="text-sm text-neutral-900">{{ $value }}</span>
                    </div>
                @endforeach
            </div>

            <div class="bg-white border border-neutral-200 rounded-xl shadow-sm p-6">
                <h2 class="text-base font-semibold text-neutral-900 mb-4">
                    Team ({{ $vendor->admins->count() + $vendor->workers->count() }})
                </h2>
                @foreach ($vendor->admins as $admin)
                    <div class="flex items-center justify-between py-2 border-b border-neutral-50">
                        <span class="text-sm text-neutral-900">{{ $admin->name }}</span>
                        <span class="text-xs bg-[#F0A820]/20 text-[#F0A820] px-2 py-0.5 rounded-full">Admin</span>
                    </div>
                @endforeach
                @foreach ($vendor->workers as $worker)
                    <div class="flex items-center justify-between py-2 border-b border-neutral-50">
                        <span class="text-sm text-neutral-900">{{ $worker->name }}</span>
                        <span class="text-xs bg-neutral-100 text-neutral-600 px-2 py-0.5 rounded-full">Worker</span>
                    </div>
                @endforeach
            </div>
        </div>

    </div>
</x-layouts.app>
