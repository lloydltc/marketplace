<x-layouts.auth>
    <x-slot:title>Confirm Password</x-slot:title>

    <h1 class="text-2xl font-semibold text-neutral-900 mb-1">Confirm your password</h1>
    <p class="text-sm text-neutral-500 mb-6">
        This action requires your current password to continue.
    </p>

    <form method="POST" action="{{ route('password.confirm') }}" class="space-y-5">
        @csrf

        <div class="space-y-1">
            <label for="password" class="block text-sm font-medium text-neutral-700">Current password</label>
            <input id="password" name="password" type="password" autocomplete="current-password" required
                   class="block w-full border rounded-lg px-3 py-2.5 text-neutral-900 text-sm
                          focus:outline-none focus:ring-2 focus:ring-[#F0A820] focus:border-[#F0A820]
                          @error('password') border-red-500 @else border-neutral-300 @enderror">
            @error('password')
                <p class="text-xs text-red-600">{{ $message }}</p>
            @enderror
        </div>

        <button type="submit"
                class="w-full bg-[#1A1A24] hover:bg-[#080810] text-white font-semibold py-2.5 rounded-lg transition-colors text-sm">
            Confirm
        </button>
    </form>
</x-layouts.auth>
