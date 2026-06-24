<x-layouts.app>
    <x-slot:title>Saved Searches</x-slot:title>

    <div class="max-w-3xl mx-auto px-4 sm:px-6 lg:px-8 py-10">

        <div class="mb-6">
            <h1 class="text-2xl font-semibold text-neutral-900">Saved Searches</h1>
            <p class="text-sm text-neutral-500 mt-1">Re-run a search you saved earlier.</p>
        </div>

        @if (session('status'))
            <div class="mb-5 bg-green-50 border border-green-200 text-green-700 text-sm rounded-lg px-4 py-3">
                {{ session('status') }}
            </div>
        @endif

        @if ($searches->isEmpty())
            <div class="bg-white border border-neutral-200 rounded-xl shadow-sm py-16 text-center">
                <p class="text-sm text-neutral-500">You haven't saved any searches yet.</p>
                <div class="mt-4 flex items-center justify-center gap-4">
                    <a href="{{ route('products.index') }}" class="text-sm text-[#3DB8E8] hover:underline">Browse products</a>
                    <a href="{{ route('vehicles.index') }}" class="text-sm text-[#3DB8E8] hover:underline">Browse vehicles</a>
                </div>
            </div>
        @else
            <div class="bg-white border border-neutral-200 rounded-xl shadow-sm divide-y divide-neutral-100">
                @foreach ($searches as $search)
                    <div class="flex items-center justify-between px-5 py-4">
                        <div>
                            <div class="flex items-center gap-2">
                                <span class="font-medium text-neutral-900">{{ $search->name }}</span>
                                <span class="text-xs px-2 py-0.5 rounded-full {{ $search->type === 'vehicles' ? 'bg-blue-50 text-blue-600' : 'bg-neutral-100 text-neutral-500' }}">
                                    {{ ucfirst($search->type) }}
                                </span>
                            </div>
                            <div class="text-xs text-neutral-400 mt-0.5">Saved {{ $search->created_at->diffForHumans() }}</div>
                        </div>
                        <div class="flex items-center gap-3">
                            @if ($search->type === 'vehicles')
                                {{-- H7: toggle email alerts for this search --}}
                                <form method="POST" action="{{ route('saved-searches.update', $search) }}">
                                    @csrf @method('PATCH')
                                    <input type="hidden" name="notify" value="{{ $search->notify ? '0' : '1' }}">
                                    <button type="submit"
                                            class="text-xs font-medium px-2.5 py-1 rounded-full transition-colors {{ $search->notify ? 'bg-[#2EBD7A]/15 text-[#1B8F5A]' : 'bg-neutral-100 text-neutral-500 hover:bg-neutral-200' }}">
                                        {{ $search->notify ? '🔔 Alerts on' : 'Alerts off' }}
                                    </button>
                                </form>
                            @endif
                            <a href="{{ $search->url() }}"
                               class="text-sm font-medium text-[#F0A820] hover:underline">Run search</a>
                            <form method="POST" action="{{ route('saved-searches.destroy', $search) }}"
                                  onsubmit="return confirm('Remove this saved search?')">
                                @csrf @method('DELETE')
                                <button type="submit" class="text-sm text-red-500 hover:underline">Delete</button>
                            </form>
                        </div>
                    </div>
                @endforeach
            </div>
        @endif

    </div>
</x-layouts.app>
