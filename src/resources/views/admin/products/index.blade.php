<x-layouts.app>
    <x-slot:title>Products</x-slot:title>

    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-10">

        <div class="flex items-center justify-between mb-6">
            <div>
                <h1 class="text-2xl font-semibold text-neutral-900">Products</h1>
                <p class="text-sm text-neutral-500 mt-1">Review, approve, and manage vendor product listings.</p>
            </div>
        </div>

        @if (session('status'))
            <div class="mb-5 bg-green-50 border border-green-200 text-green-700 text-sm rounded-lg px-4 py-3">
                {{ session('status') }}
            </div>
        @endif

        {{-- Filters --}}
        <form method="GET" class="flex flex-wrap gap-3 mb-6">
            <input type="text" name="search" value="{{ request('search') }}"
                   placeholder="Search title or SKU…"
                   class="border border-neutral-200 rounded-lg px-3 py-2 text-sm w-56 focus:outline-none focus:ring-2 focus:ring-[#F0A820]/40">

            <select name="status"
                    class="border border-neutral-200 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-[#F0A820]/40">
                <option value="">All statuses</option>
                <option value="pending"  @selected(request('status') === 'pending')>Pending</option>
                <option value="active"   @selected(request('status') === 'active')>Active</option>
                <option value="inactive" @selected(request('status') === 'inactive')>Inactive</option>
                <option value="rejected" @selected(request('status') === 'rejected')>Rejected</option>
            </select>

            <button type="submit"
                    class="bg-[#F0A820] hover:bg-[#F0A820]/90 text-[#1A1A24] font-semibold px-4 py-2 rounded-lg text-sm transition-colors">
                Filter
            </button>
            <a href="{{ route('admin.products.index') }}"
               class="text-sm text-neutral-500 hover:text-neutral-700 px-2 py-2 self-center">Clear</a>
        </form>

        @if ($products->isEmpty())
            <div class="bg-white border border-neutral-200 rounded-xl shadow-sm py-16 text-center">
                <p class="text-neutral-500 text-sm">No products found.</p>
            </div>
        @else
            <div class="bg-white border border-neutral-200 rounded-xl shadow-sm overflow-hidden">
                <table class="w-full text-sm">
                    <thead>
                        <tr class="bg-neutral-50 border-b border-neutral-200">
                            <th class="text-left font-medium text-neutral-500 px-4 py-3">Product</th>
                            <th class="text-left font-medium text-neutral-500 px-4 py-3 hidden md:table-cell">Vendor</th>
                            <th class="text-right font-medium text-neutral-500 px-4 py-3 hidden sm:table-cell">Price (ZWL)</th>
                            <th class="text-center font-medium text-neutral-500 px-4 py-3 hidden sm:table-cell">Qty</th>
                            <th class="text-center font-medium text-neutral-500 px-4 py-3">Status</th>
                            <th class="text-right font-medium text-neutral-500 px-4 py-3">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-neutral-100">
                        @foreach ($products as $product)
                            <tr class="hover:bg-neutral-50 transition-colors">
                                <td class="px-4 py-3">
                                    <div class="font-medium text-neutral-900 truncate max-w-xs">{{ $product->title }}</div>
                                    @if ($product->sku)
                                        <div class="text-xs text-neutral-400 font-mono mt-0.5">{{ $product->sku }}</div>
                                    @endif
                                </td>
                                <td class="px-4 py-3 text-neutral-600 hidden md:table-cell">
                                    {{ $product->vendor?->name ?? '—' }}
                                </td>
                                <td class="px-4 py-3 text-right tabular-nums hidden sm:table-cell">
                                    {{ number_format($product->price_zwl, 2) }}
                                </td>
                                <td class="px-4 py-3 text-center tabular-nums hidden sm:table-cell">
                                    {{ $product->quantity }}
                                </td>
                                <td class="px-4 py-3 text-center">
                                    @php
                                        $badge = match($product->status) {
                                            'active'   => 'bg-green-100 text-green-700',
                                            'pending'  => 'bg-amber-100 text-amber-700',
                                            'rejected' => 'bg-red-100 text-red-700',
                                            default    => 'bg-neutral-100 text-neutral-600',
                                        };
                                    @endphp
                                    <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium {{ $badge }}">
                                        {{ ucfirst($product->status) }}
                                    </span>
                                </td>
                                <td class="px-4 py-3 text-right">
                                    <a href="{{ route('admin.products.show', $product) }}"
                                       class="text-sm text-[#3DB8E8] hover:underline">Review</a>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            <div class="mt-5">
                {{ $products->withQueryString()->links() }}
            </div>
        @endif

    </div>
</x-layouts.app>
