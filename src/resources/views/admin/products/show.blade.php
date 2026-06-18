<x-layouts.app>
    <x-slot:title>Product: {{ $product->title }}</x-slot:title>

    <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 py-10">

        <div class="flex items-center gap-3 mb-6">
            <a href="{{ route('admin.products.index') }}"
               class="text-sm text-neutral-500 hover:text-neutral-700">← Products</a>
            <span class="text-neutral-300">/</span>
            <span class="text-sm text-neutral-700 truncate">{{ $product->title }}</span>
        </div>

        @if (session('status'))
            <div class="mb-5 bg-green-50 border border-green-200 text-green-700 text-sm rounded-lg px-4 py-3">
                {{ session('status') }}
            </div>
        @endif

        @if ($errors->any())
            <div class="mb-5 bg-red-50 border border-red-200 text-red-700 text-sm rounded-lg px-4 py-3">
                <ul class="list-disc list-inside space-y-1">
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">

            {{-- Main details --}}
            <div class="lg:col-span-2 space-y-5">
                <div class="bg-white border border-neutral-200 rounded-xl shadow-sm p-6">
                    <h2 class="text-lg font-semibold text-neutral-900 mb-4">Product Details</h2>

                    <dl class="space-y-3 text-sm">
                        <div>
                            <dt class="text-neutral-500 text-xs uppercase tracking-wide mb-1">Title</dt>
                            <dd class="text-neutral-900 font-medium">{{ $product->title }}</dd>
                        </div>

                        @if ($product->sku)
                        <div>
                            <dt class="text-neutral-500 text-xs uppercase tracking-wide mb-1">SKU</dt>
                            <dd class="text-neutral-700 font-mono">{{ $product->sku }}</dd>
                        </div>
                        @endif

                        <div>
                            <dt class="text-neutral-500 text-xs uppercase tracking-wide mb-1">Description</dt>
                            <dd class="text-neutral-700 whitespace-pre-line">{{ $product->description }}</dd>
                        </div>

                        <div class="grid grid-cols-2 gap-4">
                            <div>
                                <dt class="text-neutral-500 text-xs uppercase tracking-wide mb-1">Price ZWL</dt>
                                <dd class="text-neutral-900 font-semibold tabular-nums">ZWL {{ number_format($product->price_zwl, 2) }}</dd>
                            </div>
                            @if ($product->price_usd)
                            <div>
                                <dt class="text-neutral-500 text-xs uppercase tracking-wide mb-1">Price USD</dt>
                                <dd class="text-neutral-900 font-semibold tabular-nums">USD {{ number_format($product->price_usd, 2) }}</dd>
                            </div>
                            @endif
                        </div>

                        <div class="grid grid-cols-2 gap-4">
                            <div>
                                <dt class="text-neutral-500 text-xs uppercase tracking-wide mb-1">Quantity</dt>
                                <dd class="text-neutral-900 tabular-nums">{{ $product->quantity }}</dd>
                            </div>
                            <div>
                                <dt class="text-neutral-500 text-xs uppercase tracking-wide mb-1">Category</dt>
                                <dd class="text-neutral-700">{{ $product->category?->name ?? '—' }}</dd>
                            </div>
                        </div>
                    </dl>
                </div>
            </div>

            {{-- Sidebar --}}
            <div class="space-y-5">
                {{-- Status card --}}
                <div class="bg-white border border-neutral-200 rounded-xl shadow-sm p-5">
                    <h3 class="text-sm font-semibold text-neutral-700 mb-3">Status</h3>
                    @php
                        $badge = match($product->status) {
                            'active'   => 'bg-green-100 text-green-700',
                            'pending'  => 'bg-amber-100 text-amber-700',
                            'rejected' => 'bg-red-100 text-red-700',
                            default    => 'bg-neutral-100 text-neutral-600',
                        };
                    @endphp
                    <span class="inline-flex items-center px-3 py-1 rounded-full text-sm font-medium {{ $badge }}">
                        {{ ucfirst($product->status) }}
                    </span>

                    <div class="mt-4 text-xs text-neutral-500">
                        <div>Vendor: <span class="text-neutral-700 font-medium">{{ $product->vendor?->name ?? '—' }}</span></div>
                        <div class="mt-1">Listed: {{ $product->created_at->diffForHumans() }}</div>
                    </div>
                </div>

                {{-- Approval actions --}}
                @if ($product->isPending())
                <div class="bg-white border border-neutral-200 rounded-xl shadow-sm p-5 space-y-3">
                    <h3 class="text-sm font-semibold text-neutral-700 mb-1">Review Actions</h3>

                    <form method="POST" action="{{ route('admin.products.approve', $product) }}">
                        @csrf
                        <button type="submit"
                                class="w-full bg-[#2EBD7A] hover:bg-[#2EBD7A]/90 text-white font-semibold px-4 py-2 rounded-lg text-sm transition-colors">
                            Approve
                        </button>
                    </form>

                    <form method="POST" action="{{ route('admin.products.reject', $product) }}" x-data="{ open: false }">
                        @csrf
                        <button type="button" @click="open = !open"
                                class="w-full bg-red-500 hover:bg-red-600 text-white font-semibold px-4 py-2 rounded-lg text-sm transition-colors">
                            Reject
                        </button>
                        <div x-show="open" class="mt-3">
                            <textarea name="reason" rows="3" required
                                      placeholder="Rejection reason (required)…"
                                      class="w-full border border-neutral-200 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-red-300 resize-none"></textarea>
                            <button type="submit"
                                    class="mt-2 w-full bg-red-600 hover:bg-red-700 text-white font-semibold px-4 py-2 rounded-lg text-sm transition-colors">
                                Confirm Rejection
                            </button>
                        </div>
                    </form>
                </div>
                @endif
            </div>

        </div>
    </div>
</x-layouts.app>
