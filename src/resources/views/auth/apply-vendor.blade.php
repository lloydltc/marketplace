<x-layouts.auth>
    <x-slot:title>Vendor Application</x-slot:title>

    <h1 class="text-2xl font-semibold text-neutral-900 mb-1">Apply as a Vendor</h1>
    <p class="text-sm text-neutral-500 mb-6">List your vehicles and products on SalmaDrive. Applications are reviewed within 1–2 business days.</p>

    {{-- R9: multi-step wizard (UI_STANDARDS.md — no long continuous scroll). The form
         still posts every field at once; the server is the source of validation truth.
         We start on whichever step holds the first validation error. --}}
    @php
        $accountFields  = ['name', 'email', 'password'];
        $startStep      = $errors->hasAny($accountFields) ? 1 : ($errors->any() ? 2 : 1);
    @endphp

    <div x-data="{ step: {{ $startStep }} }">

        {{-- Progress indicator --}}
        <div class="flex items-center gap-2 mb-6">
            <template x-for="n in 2" :key="n">
                <div class="flex-1 h-1.5 rounded-full transition-colors"
                     :class="step >= n ? 'bg-[#F0A820]' : 'bg-neutral-200'"></div>
            </template>
        </div>
        <p class="text-xs font-semibold text-neutral-400 uppercase tracking-wider mb-4">
            Step <span x-text="step"></span> of 2 —
            <span x-text="step === 1 ? 'Your account' : 'Business details'"></span>
        </p>

        <form method="POST" action="{{ route('apply.vendor.store') }}" class="space-y-4" novalidate>
            @csrf

            {{-- ── Step 1: Account details ───────────────────────────────────────── --}}
            <div x-show="step === 1" class="space-y-4">
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

                <button type="button" @click="step = 2"
                        class="w-full bg-[#F0A820] hover:bg-[#F0A820]/90 text-[#1A1A24] font-semibold py-2.5 rounded-lg transition-colors text-sm mt-2">
                    Continue
                </button>
            </div>

            {{-- ── Step 2: Business details ──────────────────────────────────────── --}}
            <div x-show="step === 2" x-cloak class="space-y-4">
                <div class="space-y-1">
                    <label for="business_name" class="block text-sm font-medium text-neutral-700">Business name</label>
                    <input id="business_name" name="business_name" type="text" required value="{{ old('business_name') }}"
                           class="block w-full border rounded-lg px-3 py-2.5 text-sm text-neutral-900 placeholder-neutral-400
                                  focus:outline-none focus:ring-2 focus:ring-[#F0A820] focus:border-[#F0A820]
                                  @error('business_name') border-red-500 @else border-neutral-300 @enderror">
                    @error('business_name')<p class="text-xs text-red-600">{{ $message }}</p>@enderror
                </div>

                <div class="space-y-1">
                    <label for="phone" class="block text-sm font-medium text-neutral-700">Phone number</label>
                    <input id="phone" name="phone" type="tel" required value="{{ old('phone') }}"
                           placeholder="+263 7X XXX XXXX"
                           class="block w-full border rounded-lg px-3 py-2.5 text-sm text-neutral-900 placeholder-neutral-400
                                  focus:outline-none focus:ring-2 focus:ring-[#F0A820] focus:border-[#F0A820]
                                  @error('phone') border-red-500 @else border-neutral-300 @enderror">
                    @error('phone')<p class="text-xs text-red-600">{{ $message }}</p>@enderror
                </div>

                <div class="space-y-1">
                    <label for="address" class="block text-sm font-medium text-neutral-700">Business address</label>
                    <input id="address" name="address" type="text" required value="{{ old('address') }}"
                           placeholder="Street, City"
                           class="block w-full border rounded-lg px-3 py-2.5 text-sm text-neutral-900 placeholder-neutral-400
                                  focus:outline-none focus:ring-2 focus:ring-[#F0A820] focus:border-[#F0A820]
                                  @error('address') border-red-500 @else border-neutral-300 @enderror">
                    @error('address')<p class="text-xs text-red-600">{{ $message }}</p>@enderror
                </div>

                <div class="space-y-1">
                    <label for="business_registration" class="block text-sm font-medium text-neutral-700">
                        Business registration number <span class="text-neutral-400 font-normal">(optional)</span>
                    </label>
                    <input id="business_registration" name="business_registration" type="text" value="{{ old('business_registration') }}"
                           class="block w-full border border-neutral-300 rounded-lg px-3 py-2.5 text-sm text-neutral-900 placeholder-neutral-400
                                  focus:outline-none focus:ring-2 focus:ring-[#F0A820] focus:border-[#F0A820]">
                    @error('business_registration')<p class="text-xs text-red-600">{{ $message }}</p>@enderror
                </div>

                <div class="space-y-1">
                    <label for="description" class="block text-sm font-medium text-neutral-700">
                        About your business <span class="text-neutral-400 font-normal">(optional)</span>
                    </label>
                    <textarea id="description" name="description" rows="3"
                              class="block w-full border border-neutral-300 rounded-lg px-3 py-2.5 text-sm text-neutral-900 placeholder-neutral-400
                                     focus:outline-none focus:ring-2 focus:ring-[#F0A820] focus:border-[#F0A820]">{{ old('description') }}</textarea>
                    @error('description')<p class="text-xs text-red-600">{{ $message }}</p>@enderror
                </div>

                <div class="flex gap-3 mt-2">
                    <button type="button" @click="step = 1"
                            class="flex-1 border border-neutral-300 hover:bg-neutral-50 text-neutral-700 font-semibold py-2.5 rounded-lg transition-colors text-sm">
                        Back
                    </button>
                    <button type="submit"
                            class="flex-1 bg-[#F0A820] hover:bg-[#F0A820]/90 text-[#1A1A24] font-semibold py-2.5 rounded-lg transition-colors text-sm">
                        Submit application
                    </button>
                </div>
            </div>
        </form>
    </div>

    <x-slot:footer>
        Already have an account?
        <a href="{{ route('login') }}" class="text-[#3DB8E8] hover:underline font-medium">Sign in</a>
        &nbsp;·&nbsp;
        <a href="{{ route('apply.seller') }}" class="text-[#3DB8E8] hover:underline font-medium">Apply as private seller</a>
    </x-slot:footer>
</x-layouts.auth>
