<x-layouts.app>
    <x-slot:title>Add Product</x-slot:title>

    <div class="max-w-3xl mx-auto px-4 sm:px-6 lg:px-8 py-10">

        <div class="flex items-center gap-3 mb-6">
            <a href="{{ route('vendor.products.index') }}"
               class="text-sm text-neutral-500 hover:text-neutral-700">← Products</a>
            <span class="text-neutral-300">/</span>
            <span class="text-sm text-neutral-700">Add Product</span>
        </div>

        @if ($errors->any())
            <div class="mb-5 bg-red-50 border border-red-200 text-red-700 text-sm rounded-lg px-4 py-3">
                <ul class="list-disc list-inside space-y-1">
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <form method="POST" action="{{ route('vendor.products.store') }}" class="space-y-5" enctype="multipart/form-data">
            @csrf

            <div class="bg-white border border-neutral-200 rounded-xl shadow-sm p-6 space-y-5">
                <h2 class="text-base font-semibold text-neutral-800">Product Information</h2>

                <div>
                    <label for="category_id" class="block text-sm font-medium text-neutral-700 mb-1">Category <span class="text-red-500">*</span></label>
                    <select id="category_id" name="category_id" required
                            class="w-full border border-neutral-200 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-[#F0A820]/40 @error('category_id') border-red-400 @enderror">
                        <option value="">Select a category…</option>
                        @foreach ($categories as $root)
                            <optgroup label="{{ $root->icon }} {{ $root->name }}">
                                @foreach ($root->children as $child)
                                    <option value="{{ $child->id }}" @selected(old('category_id') === $child->id)>
                                        {{ $child->name }}
                                    </option>
                                @endforeach
                            </optgroup>
                        @endforeach
                    </select>
                    @error('category_id')
                        <p class="mt-1 text-xs text-red-500">{{ $message }}</p>
                    @enderror
                </div>

                <div>
                    <label for="title" class="block text-sm font-medium text-neutral-700 mb-1">Title <span class="text-red-500">*</span></label>
                    <input type="text" id="title" name="title" value="{{ old('title') }}" required maxlength="200"
                           class="w-full border border-neutral-200 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-[#F0A820]/40 @error('title') border-red-400 @enderror">
                    @error('title')
                        <p class="mt-1 text-xs text-red-500">{{ $message }}</p>
                    @enderror
                </div>

                <div>
                    <label for="description" class="block text-sm font-medium text-neutral-700 mb-1">Description <span class="text-red-500">*</span></label>
                    <textarea id="description" name="description" rows="5" required
                              class="w-full border border-neutral-200 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-[#F0A820]/40 resize-y @error('description') border-red-400 @enderror">{{ old('description') }}</textarea>
                    @error('description')
                        <p class="mt-1 text-xs text-red-500">{{ $message }}</p>
                    @enderror
                </div>

                <div>
                    <label for="sku" class="block text-sm font-medium text-neutral-700 mb-1">SKU <span class="text-neutral-400 font-normal">(optional)</span></label>
                    <input type="text" id="sku" name="sku" value="{{ old('sku') }}" maxlength="50"
                           placeholder="e.g. PART-12345"
                           class="w-full border border-neutral-200 rounded-lg px-3 py-2 text-sm font-mono focus:outline-none focus:ring-2 focus:ring-[#F0A820]/40 @error('sku') border-red-400 @enderror">
                    @error('sku')
                        <p class="mt-1 text-xs text-red-500">{{ $message }}</p>
                    @enderror
                </div>
            </div>

            <div class="bg-white border border-neutral-200 rounded-xl shadow-sm p-6 space-y-5">
                <h2 class="text-base font-semibold text-neutral-800">Pricing & Inventory</h2>

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-5">
                    <div>
                        <label for="price_zwl" class="block text-sm font-medium text-neutral-700 mb-1">Price ZWL <span class="text-red-500">*</span></label>
                        <div class="relative">
                            <span class="absolute left-3 top-2 text-sm text-neutral-400">ZWL</span>
                            <input type="number" id="price_zwl" name="price_zwl" value="{{ old('price_zwl') }}"
                                   step="0.01" min="0.01" required
                                   class="w-full pl-12 border border-neutral-200 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-[#F0A820]/40 @error('price_zwl') border-red-400 @enderror">
                        </div>
                        @error('price_zwl')
                            <p class="mt-1 text-xs text-red-500">{{ $message }}</p>
                        @enderror
                    </div>

                    <div>
                        <label for="price_usd" class="block text-sm font-medium text-neutral-700 mb-1">Price USD <span class="text-neutral-400 font-normal">(optional)</span></label>
                        <div class="relative">
                            <span class="absolute left-3 top-2 text-sm text-neutral-400">USD</span>
                            <input type="number" id="price_usd" name="price_usd" value="{{ old('price_usd') }}"
                                   step="0.01" min="0.01"
                                   class="w-full pl-12 border border-neutral-200 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-[#F0A820]/40 @error('price_usd') border-red-400 @enderror">
                        </div>
                        @error('price_usd')
                            <p class="mt-1 text-xs text-red-500">{{ $message }}</p>
                        @enderror
                    </div>
                </div>

                <div class="max-w-xs">
                    <label for="quantity" class="block text-sm font-medium text-neutral-700 mb-1">Stock Quantity <span class="text-red-500">*</span></label>
                    <input type="number" id="quantity" name="quantity" value="{{ old('quantity', 0) }}"
                           min="0" required
                           class="w-full border border-neutral-200 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-[#F0A820]/40 @error('quantity') border-red-400 @enderror">
                    @error('quantity')
                        <p class="mt-1 text-xs text-red-500">{{ $message }}</p>
                    @enderror
                </div>
            </div>

            @include('partials.image-upload-create', ['max' => $imageLimit ?? null])

            <div class="flex items-center justify-end gap-3">
                <a href="{{ route('vendor.products.index') }}"
                   class="text-sm text-neutral-600 hover:text-neutral-800 px-4 py-2">Cancel</a>
                <button type="submit"
                        class="bg-[#F0A820] hover:bg-[#F0A820]/90 text-[#1A1A24] font-semibold px-6 py-2 rounded-lg text-sm transition-colors">
                    Submit for Review
                </button>
            </div>
        </form>
    </div>
</x-layouts.app>
