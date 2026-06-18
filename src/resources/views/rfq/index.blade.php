<x-layouts.app>
    <x-slot:title>My Requests</x-slot:title>

    <div class="max-w-3xl mx-auto px-4 sm:px-6 lg:px-8 py-10">
        <div class="flex items-center justify-between mb-6">
            <h1 class="text-2xl font-semibold text-neutral-900">My Part Requests</h1>
            <a href="{{ route('rfq.create') }}" class="bg-[#F0A820] hover:bg-[#F0A820]/90 text-[#1A1A24] font-semibold px-4 py-2 rounded-lg text-sm">+ New request</a>
        </div>

        @if (session('status'))
            <div class="mb-5 bg-green-50 border border-green-200 text-green-700 text-sm rounded-lg px-4 py-3">{{ session('status') }}</div>
        @endif

        @if ($requests->isEmpty())
            <div class="bg-white border border-neutral-200 rounded-xl shadow-sm py-16 text-center text-sm text-neutral-400">
                You haven't posted any requests yet.
            </div>
        @else
            <div class="bg-white border border-neutral-200 rounded-xl shadow-sm divide-y divide-neutral-100">
                @foreach ($requests as $req)
                    <a href="{{ route('rfq.show', $req) }}" class="flex items-center justify-between px-5 py-4 hover:bg-neutral-50">
                        <div class="min-w-0">
                            <div class="text-sm font-medium text-neutral-900 truncate">{{ Str::limit($req->part_description, 60) }}</div>
                            <div class="text-xs text-neutral-400">{{ $req->created_at->format('d M Y') }} · {{ $req->quotes->count() }} {{ Str::plural('quote', $req->quotes->count()) }}</div>
                        </div>
                        <span class="text-xs px-2 py-0.5 rounded-full {{ match($req->status) {
                            'converted' => 'bg-green-50 text-green-700',
                            'closed', 'expired' => 'bg-neutral-100 text-neutral-500',
                            'quoted' => 'bg-blue-50 text-blue-700',
                            default => 'bg-amber-50 text-amber-700',
                        } }}">{{ ucfirst($req->status) }}</span>
                    </a>
                @endforeach
            </div>
            <div class="mt-6">{{ $requests->links() }}</div>
        @endif
    </div>
</x-layouts.app>
