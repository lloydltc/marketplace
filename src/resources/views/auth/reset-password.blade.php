<x-layouts.auth>
    <x-slot:title>Set New Password</x-slot:title>

    <h1 class="text-2xl font-semibold text-neutral-900 mb-1">Set a new password</h1>
    <p class="text-sm text-neutral-500 mb-6">Choose a strong password for your account.</p>

    <form method="POST" action="{{ route('password.store') }}" class="space-y-5">
        @csrf

        <input type="hidden" name="token" value="{{ $token }}">

        <div class="space-y-1">
            <label for="email" class="block text-sm font-medium text-neutral-700">Email address</label>
            <input id="email" name="email" type="email" autocomplete="email" required
                   value="{{ old('email', request()->email) }}"
                   class="block w-full border rounded-lg px-3 py-2.5 text-neutral-900 text-sm
                          focus:outline-none focus:ring-2 focus:ring-[#F0A820] focus:border-[#F0A820]
                          @error('email') border-red-500 @else border-neutral-300 @enderror">
            @error('email')
                <p class="text-xs text-red-600">{{ $message }}</p>
            @enderror
        </div>

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
            Reset password
        </button>
    </form>
</x-layouts.auth>
