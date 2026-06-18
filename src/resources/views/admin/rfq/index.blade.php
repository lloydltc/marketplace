<x-layouts.app>
    <x-slot:title>RFQ Moderation</x-slot:title>

    <div class="max-w-5xl mx-auto px-4 sm:px-6 lg:px-8 py-10">
        <h1 class="text-2xl font-semibold text-neutral-900 mb-6">Part Requests</h1>

        @if (session('status'))
            <div class="mb-5 bg-green-50 border border-green-200 text-green-700 text-sm rounded-lg px-4 py-3">{{ session('status') }}</div>
        @endif

        <div class="bg-white border border-neutral-200 rounded-xl shadow-sm overflow-hidden">
            <table class="w-full text-sm">
                <thead>
                    <tr class="bg-neutral-50 border-b border-neutral-200 text-neutral-500">
                        <th class="text-left font-medium px-4 py-3">Request</th>
                        <th class="text-left font-medium px-4 py-3 hidden sm:table-cell">Buyer</th>
                        <th class="text-left font-medium px-4 py-3">Status</th>
                        <th class="text-right font-medium px-4 py-3">Action</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-neutral-100">
                    @forelse ($requests as $req)
                        <tr class="hover:bg-neutral-50">
                            <td class="px-4 py-3 text-neutral-800">{{ Str::limit($req->part_description, 60) }}</td>
                            <td class="px-4 py-3 text-neutral-500 hidden sm:table-cell">{{ $req->buyer?->name }}</td>
                            <td class="px-4 py-3">
                                <span class="text-xs px-2 py-0.5 rounded-full {{ $req->moderation_status === 'rejected' ? 'bg-red-50 text-red-600' : 'bg-neutral-100 text-neutral-600' }}">
                                    {{ $req->moderation_status === 'rejected' ? 'Rejected' : ucfirst($req->status) }}
                                </span>
                            </td>
                            <td class="px-4 py-3 text-right">
                                @if ($req->moderation_status !== 'rejected' && $req->status !== 'converted')
                                    <form method="POST" action="{{ route('admin.rfq.reject', $req) }}" onsubmit="return confirm('Reject this request as spam/abuse?')">
                                        @csrf
                                        <button type="submit" class="text-sm text-red-500 hover:underline">Reject</button>
                                    </form>
                                @else
                                    <span class="text-xs text-neutral-400">—</span>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="4" class="px-4 py-12 text-center text-sm text-neutral-400">No requests yet.</td></tr>
                    @endforelse
                </tbody>
            </table>
            @if ($requests->hasPages())
                <div class="px-4 py-3 border-t border-neutral-100">{{ $requests->links() }}</div>
            @endif
        </div>
    </div>
</x-layouts.app>
