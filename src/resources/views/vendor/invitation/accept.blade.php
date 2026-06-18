<x-layouts.auth>
    <x-slot:title>Accept Invitation</x-slot:title>

    @if ($invalid ?? false)
        <div class="text-center">
            <div class="mx-auto w-16 h-16 bg-red-50 rounded-full flex items-center justify-center mb-4">
                <svg class="w-8 h-8 text-red-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                </svg>
            </div>
            <h1 class="text-2xl font-semibold text-neutral-900 mb-2">Invitation not found</h1>
            <p class="text-sm text-neutral-500 mb-6">
                This invitation link is invalid or has expired. Please ask your vendor admin to send a new invitation.
            </p>
            <a href="{{ route('login') }}" class="text-sm text-[#3DB8E8] hover:underline">Back to sign in</a>
        </div>
    @else
        <h1 class="text-2xl font-semibold text-neutral-900 mb-1">Accept your invitation</h1>
        <p class="text-sm text-neutral-500 mb-2">
            You've been invited to join <strong>{{ $invitation->vendor->name }}</strong> on SalmaDrive.
        </p>
        <p class="text-sm text-neutral-500 mb-6">
            Your account email: <strong>{{ $invitation->email }}</strong>
        </p>

        <form method="POST" action="{{ route('vendor.invitation.accept.store', $invitation->token) }}" class="space-y-5">
            @csrf

            <div class="space-y-1">
                <label for="name" class="block text-sm font-medium text-neutral-700">Your name</label>
                <input id="name" name="name" type="text" required
                       value="{{ old('name') }}"
                       placeholder="Full name"
                       class="block w-full border border-neutral-300 rounded-lg px-3 py-2.5 text-neutral-900 placeholder-neutral-400 text-sm
                              focus:outline-none focus:ring-2 focus:ring-[#F0A820] focus:border-[#F0A820]">
            </div>

            <div class="bg-neutral-50 border border-neutral-200 rounded-lg px-4 py-3 text-sm text-neutral-600">
                You will be asked to set a new password immediately after joining.
            </div>

            <button type="submit"
                    class="w-full bg-[#2EBD7A] hover:bg-[#2EBD7A]/90 text-white font-semibold py-2.5 rounded-lg transition-colors text-sm">
                Accept and join {{ $invitation->vendor->name }}
            </button>
        </form>
    @endif
</x-layouts.auth>
