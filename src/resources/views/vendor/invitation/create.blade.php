<x-layouts.app>
    <x-slot:title>Invite Team Member</x-slot:title>

    <div class="max-w-2xl mx-auto px-4 py-10">

        <div class="mb-6">
            <h1 class="text-2xl font-semibold text-neutral-900">Invite a Team Member</h1>
            <p class="text-sm text-neutral-500 mt-1">
                The invited worker will receive an email with a temporary password and a link to join your vendor account.
            </p>
        </div>

        @if (session('status') === 'invitation-sent')
            <div class="mb-5 bg-green-50 border border-green-200 text-green-700 text-sm rounded-lg px-4 py-3 flex items-center gap-2">
                <svg class="w-4 h-4 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                </svg>
                Invitation sent successfully.
            </div>
        @endif

        <div class="bg-white border border-neutral-200 rounded-xl p-6 shadow-sm">
            <form method="POST" action="{{ route('vendor.invitation.store') }}" class="space-y-5">
                @csrf

                <div class="space-y-1">
                    <label for="email" class="block text-sm font-medium text-neutral-700">Worker email address</label>
                    <input id="email" name="email" type="email" required
                           value="{{ old('email') }}"
                           placeholder="worker@example.com"
                           class="block w-full border rounded-lg px-3 py-2.5 text-neutral-900 placeholder-neutral-400 text-sm
                                  focus:outline-none focus:ring-2 focus:ring-[#F0A820] focus:border-[#F0A820]
                                  @error('email') border-red-500 @else border-neutral-300 @enderror">
                    @error('email')
                        <p class="text-xs text-red-600">{{ $message }}</p>
                    @enderror
                </div>

                <div class="space-y-1">
                    <label for="temp_password" class="block text-sm font-medium text-neutral-700">Temporary password</label>
                    <input id="temp_password" name="temp_password" type="text" required
                           placeholder="Min. 8 characters"
                           class="block w-full border rounded-lg px-3 py-2.5 text-neutral-900 placeholder-neutral-400 text-sm font-mono
                                  focus:outline-none focus:ring-2 focus:ring-[#F0A820] focus:border-[#F0A820]
                                  @error('temp_password') border-red-500 @else border-neutral-300 @enderror">
                    <p class="text-xs text-neutral-500">This will be sent to the worker in the invitation email. They must change it on first login.</p>
                    @error('temp_password')
                        <p class="text-xs text-red-600">{{ $message }}</p>
                    @enderror
                </div>

                <div class="flex items-center justify-between pt-2">
                    <a href="{{ route('vendor.dashboard') }}"
                       class="text-sm text-neutral-500 hover:text-neutral-700 transition-colors">Cancel</a>
                    <button type="submit"
                            class="bg-[#1A1A24] hover:bg-[#080810] text-white font-medium px-5 py-2.5 rounded-lg transition-colors text-sm">
                        Send invitation
                    </button>
                </div>
            </form>
        </div>

    </div>
</x-layouts.app>
