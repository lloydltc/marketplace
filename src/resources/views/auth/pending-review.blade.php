<x-layouts.auth>
    <x-slot:title>Application Under Review</x-slot:title>

    <div class="text-center">
        <div class="mx-auto w-16 h-16 bg-[#F0A820]/10 rounded-full flex items-center justify-center mb-4">
            <svg class="w-8 h-8 text-[#F0A820]" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                      d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
            </svg>
        </div>

        <h1 class="text-2xl font-semibold text-neutral-900 mb-2">Application under review</h1>
        <p class="text-sm text-neutral-500 mb-6">
            Thank you for applying to SalmaDrive. Our team is reviewing your application and
            you'll receive an email within <strong class="text-neutral-700">1–2 business days</strong>
            once a decision has been made.
        </p>

        <div class="bg-neutral-50 border border-neutral-200 rounded-xl p-5 text-left mb-6">
            <p class="text-xs font-semibold text-neutral-400 uppercase tracking-wider mb-3">What happens next</p>
            <ul class="space-y-2 text-sm text-neutral-600">
                <li class="flex items-start gap-2">
                    <span class="text-[#F0A820] font-bold mt-0.5">1.</span>
                    Our team reviews your application details.
                </li>
                <li class="flex items-start gap-2">
                    <span class="text-[#F0A820] font-bold mt-0.5">2.</span>
                    You receive an approval or feedback email.
                </li>
                <li class="flex items-start gap-2">
                    <span class="text-[#F0A820] font-bold mt-0.5">3.</span>
                    Once approved, you can log in and start listing.
                </li>
            </ul>
        </div>

        <p class="text-xs text-neutral-400">
            Questions? Email
            <a href="mailto:support@salmadrive.co.zw" class="text-[#1E2D40] hover:underline">support@salmadrive.co.zw</a>
        </p>

        <form method="POST" action="{{ route('logout') }}" class="mt-6">
            @csrf
            <button type="submit" class="text-sm text-neutral-400 hover:text-neutral-600 transition-colors">
                Sign out
            </button>
        </form>
    </div>
</x-layouts.auth>
