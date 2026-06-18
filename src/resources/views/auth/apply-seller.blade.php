<x-layouts.auth>
    <x-slot:title>Private Seller Application</x-slot:title>

    <h1 class="text-2xl font-semibold text-neutral-900 mb-1">Apply as a Private Seller</h1>
    <p class="text-sm text-neutral-500 mb-6">List your own vehicle(s) on SalmaDrive. Applications are reviewed within 1–2 business days.</p>

    <form method="POST" action="{{ route('apply.seller.store') }}" class="space-y-5">
        @csrf

        <div class="space-y-1">
            <label for="name" class="block text-sm font-medium text-neutral-700">Full name</label>
            <input id="name" name="name" type="text" autocomplete="name" required value="{{ old('name') }}"
                   class="block w-full border rounded-lg px-3 py-2.5 text-sm text-neutral-900 placeholder-neutral-400
                          focus:outline-none focus:ring-2 focus:ring-[#F0A820] focus:border-[#F0A820]
                          @error('name') border-red-500 @else border-neutral-300 @enderror">
            @error('name')<p class="text-xs text-red-600">{{ $message }}</p>@enderror
        </div>

        <div class="space-y-1">
            <label for="email" class="block text-sm font-medium text-neutral-700">Email address</label>
            <input id="email" name="email" type="email" autocomplete="email" required value="{{ old('email') }}"
                   class="block w-full border rounded-lg px-3 py-2.5 text-sm text-neutral-900 placeholder-neutral-400
                          focus:outline-none focus:ring-2 focus:ring-[#F0A820] focus:border-[#F0A820]
                          @error('email') border-red-500 @else border-neutral-300 @enderror">
            @error('email')<p class="text-xs text-red-600">{{ $message }}</p>@enderror
        </div>

        <div class="space-y-1">
            <label for="phone" class="block text-sm font-medium text-neutral-700">
                Phone number <span class="text-neutral-400 font-normal">(optional)</span>
            </label>
            <input id="phone" name="phone" type="tel" value="{{ old('phone') }}"
                   placeholder="+263 7X XXX XXXX"
                   class="block w-full border border-neutral-300 rounded-lg px-3 py-2.5 text-sm text-neutral-900 placeholder-neutral-400
                          focus:outline-none focus:ring-2 focus:ring-[#F0A820] focus:border-[#F0A820]">
            @error('phone')<p class="text-xs text-red-600">{{ $message }}</p>@enderror
        </div>

        <div class="space-y-1">
            <label for="password" class="block text-sm font-medium text-neutral-700">Password</label>
            <input id="password" name="password" type="password" autocomplete="new-password" required
                   class="block w-full border rounded-lg px-3 py-2.5 text-sm text-neutral-900
                          focus:outline-none focus:ring-2 focus:ring-[#F0A820] focus:border-[#F0A820]
                          @error('password') border-red-500 @else border-neutral-300 @enderror">
            <p class="text-xs text-neutral-500">Min. 10 characters with uppercase, number, and symbol.</p>
            @error('password')<p class="text-xs text-red-600">{{ $message }}</p>@enderror
        </div>

        <div class="space-y-1">
            <label for="password_confirmation" class="block text-sm font-medium text-neutral-700">Confirm password</label>
            <input id="password_confirmation" name="password_confirmation" type="password" autocomplete="new-password" required
                   class="block w-full border border-neutral-300 rounded-lg px-3 py-2.5 text-sm text-neutral-900
                          focus:outline-none focus:ring-2 focus:ring-[#F0A820] focus:border-[#F0A820]">
        </div>

        <button type="submit"
                class="w-full bg-[#F0A820] hover:bg-[#F0A820]/90 text-[#1A1A24] font-semibold py-2.5 rounded-lg transition-colors text-sm">
            Submit application
        </button>
    </form>

    <x-slot:footer>
        Already have an account?
        <a href="{{ route('login') }}" class="text-[#3DB8E8] hover:underline font-medium">Sign in</a>
        &nbsp;·&nbsp;
        <a href="{{ route('apply.vendor') }}" class="text-[#3DB8E8] hover:underline font-medium">Apply as a vendor instead</a>
    </x-slot:footer>
</x-layouts.auth>
