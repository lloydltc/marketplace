<x-layouts.app>
    <x-slot:title>New User</x-slot:title>

    <div class="max-w-lg mx-auto px-4 sm:px-6 lg:px-8 py-10">

        <div class="flex items-center gap-3 mb-6">
            <a href="{{ route('admin.users.index') }}" class="text-sm text-neutral-500 hover:text-neutral-700">← Users</a>
            <span class="text-neutral-300">/</span>
            <span class="text-sm text-neutral-700">New user</span>
        </div>

        <div class="bg-white border border-neutral-200 rounded-xl shadow-sm p-6">
            <h1 class="text-xl font-semibold text-neutral-900 mb-1">Create a staff account</h1>
            <p class="text-sm text-neutral-500 mb-6">
                The account is created verified with a temporary password the user must change on first login.
            </p>

            @if ($errors->any())
                <div class="mb-4 bg-red-50 border border-red-200 text-red-700 text-sm rounded-lg px-4 py-2">
                    <ul class="list-disc list-inside">
                        @foreach ($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            <form method="POST" action="{{ route('admin.users.store') }}" class="space-y-4">
                @csrf
                <div>
                    <label class="block text-sm font-medium text-neutral-700 mb-1">Name</label>
                    <input type="text" name="name" value="{{ old('name') }}" required
                           class="w-full border border-neutral-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-[#F0A820]/40">
                </div>
                <div>
                    <label class="block text-sm font-medium text-neutral-700 mb-1">Email</label>
                    <input type="email" name="email" value="{{ old('email') }}" required
                           class="w-full border border-neutral-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-[#F0A820]/40">
                </div>
                <div>
                    <label class="block text-sm font-medium text-neutral-700 mb-1">Role</label>
                    <select name="role"
                            class="w-full border border-neutral-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-[#F0A820]/40">
                        @foreach ($roles as $r)
                            <option value="{{ $r }}" @selected(old('role') === $r)>{{ str_replace('_',' ',$r) }}</option>
                        @endforeach
                    </select>
                </div>
                <button type="submit"
                        class="w-full bg-[#F0A820] hover:bg-[#F0A820]/90 text-[#1A1A24] font-semibold px-4 py-2.5 rounded-lg text-sm transition-colors">
                    Create user
                </button>
            </form>
        </div>
    </div>
</x-layouts.app>
