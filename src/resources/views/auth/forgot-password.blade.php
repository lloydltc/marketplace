<x-layouts.auth>
    <x-slot:title>Reset Password</x-slot:title>

    <h1 class="text-2xl font-semibold text-neutral-900 mb-1">Forgot your password?</h1>
    <p class="text-sm text-neutral-500 mb-6">Enter your email and we'll send a reset link. Link expires in 60 minutes.</p>

    @if (session('status'))
        <div class="mb-4 bg-green-50 border border-green-200 text-green-700 text-sm rounded-lg px-4 py-3">
            {{ session('status') }}
        </div>
    @endif

    <form method="POST" action="{{ route('password.email') }}" class="space-y-5">
        @csrf

        <div class="space-y-1">
            <label for="email" class="block text-sm font-medium text-neutral-700">Email address</label>
            <input id="email" name="email" type="email" autocomplete="email" required
                   value="{{ old('email') }}"
                   class="block w-full border rounded-lg px-3 py-2.5 text-neutral-900 placeholder-neutral-400 text-sm
                          focus:outline-none focus:ring-2 focus:ring-[#F0A820] focus:border-[#F0A820]
                          @error('email') border-red-500 @else border-neutral-300 @enderror">
            @error('email')
                <p class="text-xs text-red-600">{{ $message }}</p>
            @enderror
        </div>

        <button type="submit"
                class="w-full bg-[#1A1A24] hover:bg-[#080810] text-white font-semibold py-2.5 rounded-lg transition-colors text-sm">
            Send reset link
        </button>
    </form>

    <x-slot:footer>
        <a href="{{ route('login') }}" class="text-[#3DB8E8] hover:underline">Back to sign in</a>
    </x-slot:footer>
</x-layouts.auth>
