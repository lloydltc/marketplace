<x-layouts.auth>
    <x-slot:title>Application Not Approved</x-slot:title>

    <div class="text-center">
        <div class="mx-auto w-16 h-16 bg-red-50 rounded-full flex items-center justify-center mb-4">
            <svg class="w-8 h-8 text-red-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                      d="M6 18L18 6M6 6l12 12"/>
            </svg>
        </div>

        <h1 class="text-2xl font-semibold text-neutral-900 mb-2">Application not approved</h1>
        <p class="text-sm text-neutral-500 mb-6">
            Unfortunately your SalmaDrive application was not approved at this time.
            Check your email for the reason and next steps.
        </p>

        <p class="text-xs text-neutral-400 mb-6">
            If you believe this was a mistake, contact us at
            <a href="mailto:support@salmadrive.co.zw" class="text-[#1E2D40] hover:underline">support@salmadrive.co.zw</a>
        </p>

        <form method="POST" action="{{ route('logout') }}" class="mt-4">
            @csrf
            <button type="submit" class="text-sm text-neutral-400 hover:text-neutral-600 transition-colors">
                Sign out
            </button>
        </form>
    </div>
</x-layouts.auth>
