<x-layouts.auth>
    <x-slot:title>Create Account</x-slot:title>

    <h1 class="text-2xl font-semibold text-neutral-900 mb-1">Create your account</h1>
    <p class="text-sm text-neutral-500 mb-6">Join Zimbabwe's trusted automotive marketplace</p>

    <form method="POST" action="{{ route('register') }}" class="space-y-5">
        @csrf

        <div class="space-y-1">
            <label for="name" class="block text-sm font-medium text-neutral-700">Full name</label>
            <input id="name" name="name" type="text" autocomplete="name" required
                   value="{{ old('name') }}"
                   class="block w-full border rounded-lg px-3 py-2.5 text-neutral-900 placeholder-neutral-400 text-sm
                          focus:outline-none focus:ring-2 focus:ring-[#F0A820] focus:border-[#F0A820]
                          @error('name') border-red-500 @else border-neutral-300 @enderror">
            @error('name')
                <p class="text-xs text-red-600">{{ $message }}</p>
            @enderror
        </div>

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

        <div class="space-y-1">
            <label for="password" class="block text-sm font-medium text-neutral-700">Password</label>
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
            <label for="password_confirmation" class="block text-sm font-medium text-neutral-700">Confirm password</label>
            <input id="password_confirmation" name="password_confirmation" type="password" autocomplete="new-password" required
                   class="block w-full border rounded-lg px-3 py-2.5 text-neutral-900 text-sm
                          focus:outline-none focus:ring-2 focus:ring-[#F0A820] focus:border-[#F0A820]
                          border-neutral-300">
        </div>

        <button type="submit"
                class="w-full bg-[#F0A820] hover:bg-[#F0A820]/90 text-[#1A1A24] font-semibold py-2.5 rounded-lg transition-colors text-sm">
            Create account
        </button>
    </form>

    <x-slot:footer>
        Already have an account?
        <a href="{{ route('login') }}" class="text-[#3DB8E8] hover:underline font-medium">Sign in</a><br>
        <span class="text-neutral-400">Want to sell?</span>
        <a href="{{ route('apply.vendor') }}" class="text-[#3DB8E8] hover:underline font-medium">Apply as a vendor</a>
        or
        <a href="{{ route('apply.seller') }}" class="text-[#3DB8E8] hover:underline font-medium">private seller</a>
    </x-slot:footer>
</x-layouts.auth>
