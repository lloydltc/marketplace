<x-layouts.auth>
    <x-slot:title>Change Password Required</x-slot:title>

    <div class="flex items-center gap-3 mb-5 p-3 bg-[#F0A820]/10 border border-[#F0A820]/30 rounded-lg">
        <svg class="w-5 h-5 text-[#F0A820] flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                  d="M12 9v2m0 4h.01M10.29 3.86L1.82 18a2 2 0 001.71 3h16.94a2 2 0 001.71-3L13.71 3.86a2 2 0 00-3.42 0z"/>
        </svg>
        <p class="text-sm text-[#1A1A24] font-medium">You must change your temporary password before continuing.</p>
    </div>

    <h1 class="text-xl font-semibold text-neutral-900 mb-1">Set your new password</h1>
    <p class="text-sm text-neutral-500 mb-6">Choose a secure password. You will not be able to access your account until this is done.</p>

    <form method="POST" action="{{ route('password.force-change.update') }}" class="space-y-5">
        @csrf
        @method('POST')

        <div class="space-y-1">
            <label for="password" class="block text-sm font-medium text-neutral-700">New password</label>
            <input id="password" name="password" type="password" autocomplete="new-password" required
                   class="block w-full border rounded-lg px-3 py-2.5 text-neutral-900 text-sm
                          focus:outline-none focus:ring-2 focus:ring-[#F0A820] focus:border-[#F0A820]
                          @error('password') border-red-500 @else border-neutral-300 @enderror">
            <p class="text-xs text-neutral-500">Min. 10 characters with uppercase, number, and special character.</p>
            @error('password')
                <p class="text-xs text-red-600">{{ $message }}</p>
            @enderror
        </div>

        <div class="space-y-1">
            <label for="password_confirmation" class="block text-sm font-medium text-neutral-700">Confirm new password</label>
            <input id="password_confirmation" name="password_confirmation" type="password" autocomplete="new-password" required
                   class="block w-full border rounded-lg px-3 py-2.5 text-neutral-900 text-sm
                          focus:outline-none focus:ring-2 focus:ring-[#F0A820] focus:border-[#F0A820]
                          border-neutral-300">
        </div>

        <button type="submit"
                class="w-full bg-[#F0A820] hover:bg-[#F0A820]/90 text-[#1A1A24] font-semibold py-2.5 rounded-lg transition-colors text-sm">
            Set new password
        </button>
    </form>
</x-layouts.auth>
