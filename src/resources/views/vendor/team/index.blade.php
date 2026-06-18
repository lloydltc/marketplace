<x-layouts.app>
    <x-slot:title>Team</x-slot:title>

    <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 py-10">

        <div class="flex items-center justify-between mb-8">
            <div>
                <h1 class="text-2xl font-semibold text-neutral-900">Team</h1>
                <p class="text-sm text-neutral-500 mt-1">Manage who can access {{ $vendor->name }}.</p>
            </div>
            <a href="{{ route('vendor.invitation.create') }}"
               class="bg-[#F0A820] hover:bg-[#F0A820]/90 text-[#1A1A24] font-medium px-4 py-2 rounded-lg text-sm transition-colors">
                + Invite member
            </a>
        </div>

        @if (session('status'))
            <div class="mb-4 bg-green-50 border border-green-200 text-green-700 text-sm rounded-lg px-4 py-2">
                {{ session('status') }}
            </div>
        @endif
        @error('team')
            <div class="mb-4 bg-red-50 border border-red-200 text-red-700 text-sm rounded-lg px-4 py-2">
                {{ $message }}
            </div>
        @enderror

        <div class="bg-white border border-neutral-200 rounded-xl shadow-sm divide-y divide-neutral-100">
            @foreach ($members as $member)
                <div class="flex items-center justify-between gap-4 px-5 py-4">
                    <div class="min-w-0">
                        <p class="text-sm font-medium text-neutral-900 truncate">
                            {{ $member->name }}
                            @if ($member->id === auth()->id())
                                <span class="text-xs text-neutral-400">(you)</span>
                            @endif
                        </p>
                        <p class="text-xs text-neutral-500 truncate">{{ $member->email }}</p>
                    </div>

                    <div class="flex items-center gap-2">
                        <span class="text-xs px-2 py-0.5 rounded-full font-medium
                            {{ $member->pivot->vendor_role === 'admin' ? 'bg-blue-100 text-blue-700' : 'bg-neutral-100 text-neutral-600' }}">
                            {{ ucfirst($member->pivot->vendor_role) }}
                        </span>

                        @if ($member->id !== auth()->id())
                            {{-- Role toggle --}}
                            <form method="POST" action="{{ route('vendor.team.role', $member) }}">
                                @csrf
                                @method('PUT')
                                <input type="hidden" name="vendor_role"
                                       value="{{ $member->pivot->vendor_role === 'admin' ? 'worker' : 'admin' }}">
                                <button type="submit"
                                        class="text-xs text-neutral-600 hover:text-neutral-900 border border-neutral-200 rounded-lg px-2.5 py-1 transition-colors">
                                    Make {{ $member->pivot->vendor_role === 'admin' ? 'worker' : 'admin' }}
                                </button>
                            </form>

                            {{-- Remove --}}
                            <form method="POST" action="{{ route('vendor.team.remove', $member) }}"
                                  onsubmit="return confirm('Remove {{ $member->name }} from the team?');">
                                @csrf
                                @method('DELETE')
                                <button type="submit"
                                        class="text-xs text-red-600 hover:text-red-700 border border-red-200 rounded-lg px-2.5 py-1 transition-colors">
                                    Remove
                                </button>
                            </form>
                        @endif
                    </div>
                </div>
            @endforeach
        </div>
    </div>
</x-layouts.app>
