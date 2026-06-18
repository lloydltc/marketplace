<x-layouts.app>
    <x-slot:title>Documents — {{ $vendor->name }}</x-slot:title>

    <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 py-10">

        <nav class="text-sm text-neutral-500 mb-6">
            <a href="{{ route('admin.vendors.index') }}" class="hover:text-neutral-700">Vendors</a>
            <span class="mx-2">/</span>
            <a href="{{ route('admin.vendors.show', $vendor) }}" class="hover:text-neutral-700">{{ $vendor->name }}</a>
            <span class="mx-2">/</span>
            <span class="text-neutral-900">Documents</span>
        </nav>

        <h1 class="text-xl font-semibold text-neutral-900 mb-6">Document Review — {{ $vendor->name }}</h1>

        @if (session('status'))
            <div class="mb-5 bg-green-50 border border-green-200 text-green-700 text-sm rounded-lg px-4 py-3">
                {{ session('status') }}
            </div>
        @endif

        @forelse ($vendor->documents as $doc)
            <div class="bg-white border border-neutral-200 rounded-xl shadow-sm p-5 mb-4">
                <div class="flex items-start justify-between mb-3">
                    <div>
                        <div class="text-sm font-semibold text-neutral-900">{{ $doc->labelForType() }}</div>
                        <div class="text-xs text-neutral-400 mt-0.5">{{ $doc->original_filename }} · Uploaded {{ $doc->created_at->format('d M Y') }}</div>
                    </div>
                    @php
                        $docBadge = match($doc->status) {
                            'approved' => 'bg-green-100 text-green-700',
                            'rejected' => 'bg-red-100 text-red-700',
                            default    => 'bg-yellow-100 text-yellow-700',
                        };
                    @endphp
                    <span class="text-xs px-2.5 py-0.5 rounded-full font-medium {{ $docBadge }}">{{ ucfirst($doc->status) }}</span>
                </div>

                @if ($doc->rejection_reason)
                    <p class="text-xs text-red-600 bg-red-50 px-3 py-2 rounded mb-3">
                        <strong>Rejection reason:</strong> {{ $doc->rejection_reason }}
                    </p>
                @endif

                @if ($doc->status !== 'approved')
                    <form method="POST" action="{{ route('admin.vendors.documents.review', [$vendor, $doc]) }}"
                          class="flex items-center gap-3">
                        @csrf
                        <div class="flex-1">
                            <input type="text" name="rejection_reason" placeholder="Rejection reason (required if rejecting)"
                                   class="block w-full border border-neutral-300 rounded-lg px-3 py-1.5 text-sm text-neutral-900
                                          placeholder-neutral-400 focus:outline-none focus:ring-2 focus:ring-[#F0A820]">
                        </div>
                        <button type="submit" name="action" value="approve"
                                class="bg-[#2EBD7A] hover:bg-[#2EBD7A]/90 text-white font-medium px-4 py-1.5 rounded-lg text-sm transition-colors">
                            Approve
                        </button>
                        <button type="submit" name="action" value="reject"
                                class="border border-red-300 text-red-600 hover:bg-red-50 font-medium px-4 py-1.5 rounded-lg text-sm transition-colors">
                            Reject
                        </button>
                    </form>
                @endif
            </div>
        @empty
            <div class="bg-white border border-neutral-200 rounded-xl shadow-sm py-16 text-center">
                <p class="text-sm text-neutral-500">No documents have been uploaded by this vendor yet.</p>
            </div>
        @endforelse

    </div>
</x-layouts.app>
