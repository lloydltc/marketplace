<x-layouts.auth>
    <x-slot:title>Sign In</x-slot:title>

    <h1 class="text-2xl font-semibold text-neutral-900 mb-1">Welcome back</h1>
    <p class="text-sm text-neutral-500 mb-6">Sign in to your SalmaDrive account</p>

    @if ($errors->any())
        <div class="mb-4 bg-red-50 border border-red-200 text-red-700 text-sm rounded-lg px-4 py-3">
            {{ $errors->first() }}
        </div>
    @endif

    <form method="POST" action="{{ route('login') }}" class="space-y-5">
        @csrf

        {{-- Email --}}
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

        {{-- Password --}}
        <div class="space-y-1">
            <div class="flex items-center justify-between">
                <label for="password" class="block text-sm font-medium text-neutral-700">Password</label>
                <a href="{{ route('password.request') }}" class="text-xs text-[#3DB8E8] hover:underline">Forgot password?</a>
            </div>
            <input id="password" name="password" type="password" autocomplete="current-password" required
                   class="block w-full border rounded-lg px-3 py-2.5 text-neutral-900 text-sm
                          focus:outline-none focus:ring-2 focus:ring-[#F0A820] focus:border-[#F0A820]
                          @error('password') border-red-500 @else border-neutral-300 @enderror">
            @error('password')
                <p class="text-xs text-red-600">{{ $message }}</p>
            @enderror
        </div>

        {{-- Remember me --}}
        <div class="flex items-center gap-2">
            <input id="remember" name="remember" type="checkbox"
                   class="w-4 h-4 rounded border-neutral-300 text-[#F0A820] focus:ring-[#F0A820]">
            <label for="remember" class="text-sm text-neutral-600">Remember me</label>
        </div>

        <button type="submit"
                class="w-full bg-[#1A1A24] hover:bg-[#080810] text-white font-semibold py-2.5 rounded-lg transition-colors text-sm">
            Sign in
        </button>
    </form>

    <x-slot:footer>
        Don't have an account?
        <a href="{{ route('register') }}" class="text-[#3DB8E8] hover:underline font-medium">Create one</a>
    </x-slot:footer>
</x-layouts.auth>
