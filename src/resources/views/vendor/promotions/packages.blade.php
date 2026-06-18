<x-layouts.app>
    <x-slot:title>Dealer Packages</x-slot:title>

    <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 py-10">
        <h1 class="text-2xl font-semibold text-neutral-900 mb-1">Dealer Packages</h1>
        <p class="text-sm text-neutral-500 mb-6">Bundle feature &amp; bump credits at a discount.</p>

        @if (session('status'))
            <div class="mb-5 bg-green-50 border border-green-200 text-green-700 text-sm rounded-lg px-4 py-3">{{ session('status') }}</div>
        @endif
        @error('promotion')
            <div class="mb-5 bg-red-50 border border-red-200 text-red-700 text-sm rounded-lg px-4 py-3">{{ $message }}</div>
        @enderror

        @if ($subscription)
            <div class="mb-6 bg-[#F0A820]/10 border border-[#F0A820]/40 rounded-xl p-4 text-sm">
                <div class="font-medium text-neutral-800">Active package</div>
                <div class="text-neutral-600 mt-1">
                    {{ $subscription->feature_credits_remaining }} feature ·
                    {{ $subscription->bump_credits_remaining }} bump credits remaining
                    @if ($subscription->expires_at) · expires {{ $subscription->expires_at->format('d M Y') }}@endif
                </div>
            </div>
        @endif

        <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
            @forelse ($packages as $package)
                <div class="bg-white border border-neutral-200 rounded-xl shadow-sm p-5 flex flex-col">
                    <div class="text-base font-semibold text-neutral-900">{{ $package->name }}</div>
                    <div class="text-2xl font-bold text-neutral-900 my-2 tabular-nums">ZWL {{ number_format($package->price, 2) }}</div>
                    <ul class="text-sm text-neutral-500 space-y-1 flex-1">
                        <li>{{ $package->listing_credits }} listing credits</li>
                        <li>{{ $package->feature_credits }} feature credits</li>
                        <li>{{ $package->bump_credits }} bump credits</li>
                        <li>{{ $package->duration_days }} days</li>
                    </ul>
                    <form method="POST" action="{{ route('vendor.promotions.buy', $package) }}" class="mt-4">
                        @csrf
                        <button type="submit" class="w-full bg-[#1A1A24] hover:bg-[#080810] text-white font-semibold py-2 rounded-lg text-sm">Buy package</button>
                    </form>
                </div>
            @empty
                <p class="text-sm text-neutral-400">No packages available right now.</p>
            @endforelse
        </div>
    </div>
</x-layouts.app>
