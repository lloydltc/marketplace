<x-layouts.app>
    <x-slot:title>My products</x-slot:title>

    <div class="max-w-5xl mx-auto px-4 sm:px-6 lg:px-8 py-10">

        <div class="flex items-center justify-between gap-4 mb-6">
            <div>
                <h1 class="text-h1 text-ink">My products</h1>
                <p class="text-body-sm text-muted mt-1">Manage your product listings.</p>
            </div>
            @can('create', App\Modules\Products\Models\Product::class)
                <x-button :href="route('vendor.products.create')">+ Add product</x-button>
            @endcan
        </div>

        @if (session('status'))
            <div class="mb-5 bg-[rgb(var(--success)/0.12)] border border-[rgb(var(--success)/0.3)] text-[rgb(var(--success))] text-body-sm rounded-lg px-4 py-3" role="status">{{ session('status') }}</div>
        @endif

        <form method="GET" class="flex flex-wrap items-end gap-3 mb-6">
            <input type="text" name="search" value="{{ request('search') }}" placeholder="Search title or SKU…"
                   class="h-11 px-3.5 w-56 rounded-md bg-surface text-ink border border-strong placeholder:text-[rgb(var(--text-muted))] focus-visible:outline-none focus:ring-2 focus:ring-brand focus:border-brand text-body-sm">
            <x-select name="status" class="!w-auto min-w-[10rem]">
                <option value="">All</option>
                <option value="pending" @selected(request('status') === 'pending')>Pending</option>
                <option value="active" @selected(request('status') === 'active')>Active</option>
                <option value="inactive" @selected(request('status') === 'inactive')>Inactive</option>
                <option value="rejected" @selected(request('status') === 'rejected')>Rejected</option>
            </x-select>
            <x-button type="submit" variant="outline">Filter</x-button>
        </form>

        @if ($products->isEmpty())
            <x-empty title="No products yet" message="Add your first product to start selling.">
                @can('create', App\Modules\Products\Models\Product::class)
                    <x-button :href="route('vendor.products.create')">Create first product</x-button>
                @endcan
            </x-empty>
        @else
            <x-table>
                <x-slot:head>
                    <th>Product</th>
                    <th class="!text-right hidden sm:table-cell">Price (ZWL)</th>
                    <th class="!text-center hidden sm:table-cell">Qty</th>
                    <th class="!text-center">Status</th>
                    <th class="!text-right">Actions</th>
                </x-slot:head>
                @foreach ($products as $product)
                    @php
                        $badge = match ($product->status) {
                            'active'   => 'bg-[rgb(var(--success)/0.15)] text-[rgb(var(--success))]',
                            'pending'  => 'bg-[rgb(var(--warning)/0.15)] text-[rgb(var(--warning))]',
                            'rejected' => 'bg-[rgb(var(--danger)/0.15)] text-[rgb(var(--danger))]',
                            default    => 'bg-surface-2 text-muted',
                        };
                    @endphp
                    <tr>
                        <td>
                            <div class="font-medium text-ink truncate max-w-xs">{{ $product->title }}</div>
                            @if ($product->sku)<div class="text-caption text-muted font-mono">{{ $product->sku }}</div>@endif
                        </td>
                        <td class="text-right tabular-nums hidden sm:table-cell">{{ number_format($product->price_zwl, 2) }}</td>
                        <td class="text-center tabular-nums hidden sm:table-cell">{{ $product->quantity }}</td>
                        <td class="text-center">
                            <span class="inline-flex items-center px-2 py-0.5 rounded-full text-caption font-medium {{ $badge }}">{{ ucfirst($product->status) }}</span>
                        </td>
                        <td class="text-right whitespace-nowrap">
                            <div class="flex items-center justify-end gap-3">
                                <a href="{{ route('vendor.products.show', $product) }}" class="text-body-sm text-[rgb(var(--info))] hover:underline">View</a>
                                @can('update', $product)
                                    <a href="{{ route('vendor.products.edit', $product) }}" class="text-body-sm text-muted hover:text-[rgb(var(--text))]">Edit</a>
                                @endcan
                            </div>
                        </td>
                    </tr>
                @endforeach
            </x-table>

            <x-pagination :paginator="$products->withQueryString()" class="mt-5" />
        @endif
    </div>
</x-layouts.app>
