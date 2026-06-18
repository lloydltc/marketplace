{{-- Shared category form fields for create and edit --}}

<div class="space-y-1">
    <label for="name" class="block text-sm font-medium text-neutral-700">Category Name <span class="text-red-500">*</span></label>
    <input id="name" name="name" type="text" required
           value="{{ old('name', $category?->name) }}"
           placeholder="e.g. Spare Parts"
           class="block w-full border rounded-lg px-3 py-2.5 text-neutral-900 placeholder-neutral-400 text-sm
                  focus:outline-none focus:ring-2 focus:ring-[#F0A820] focus:border-[#F0A820]
                  @error('name') border-red-500 @else border-neutral-300 @enderror">
    @error('name') <p class="text-xs text-red-600">{{ $message }}</p> @enderror
</div>

<div class="space-y-1">
    <label for="parent_id" class="block text-sm font-medium text-neutral-700">Parent Category <span class="text-neutral-400">(optional)</span></label>
    <select id="parent_id" name="parent_id"
            class="block w-full border border-neutral-300 rounded-lg px-3 py-2.5 text-neutral-900 text-sm
                   focus:outline-none focus:ring-2 focus:ring-[#F0A820] focus:border-[#F0A820]">
        <option value="">— Top-level category —</option>
        @foreach ($parentOptions as $parent)
            <option value="{{ $parent->id }}"
                    @selected(old('parent_id', $category?->parent_id) === $parent->id)>
                {{ $parent->name }}
            </option>
        @endforeach
    </select>
    @error('parent_id') <p class="text-xs text-red-600">{{ $message }}</p> @enderror
</div>

<div class="space-y-1">
    <label for="description" class="block text-sm font-medium text-neutral-700">Description <span class="text-neutral-400">(optional)</span></label>
    <textarea id="description" name="description" rows="2"
              placeholder="Short description shown to customers"
              class="block w-full border border-neutral-300 rounded-lg px-3 py-2.5 text-neutral-900 text-sm
                     focus:outline-none focus:ring-2 focus:ring-[#F0A820] focus:border-[#F0A820]">{{ old('description', $category?->description) }}</textarea>
</div>

<div class="grid grid-cols-2 gap-4">
    <div class="space-y-1">
        <label for="icon" class="block text-sm font-medium text-neutral-700">Icon emoji <span class="text-neutral-400">(optional)</span></label>
        <input id="icon" name="icon" type="text" maxlength="10"
               value="{{ old('icon', $category?->icon) }}"
               placeholder="🚗"
               class="block w-full border border-neutral-300 rounded-lg px-3 py-2.5 text-neutral-900 text-sm
                      focus:outline-none focus:ring-2 focus:ring-[#F0A820] focus:border-[#F0A820]">
    </div>

    <div class="space-y-1">
        <label for="sort_order" class="block text-sm font-medium text-neutral-700">Sort Order</label>
        <input id="sort_order" name="sort_order" type="number" min="0"
               value="{{ old('sort_order', $category?->sort_order ?? 0) }}"
               class="block w-full border border-neutral-300 rounded-lg px-3 py-2.5 text-neutral-900 text-sm
                      focus:outline-none focus:ring-2 focus:ring-[#F0A820] focus:border-[#F0A820]">
    </div>
</div>

<div class="space-y-1">
    <label for="commission_override" class="block text-sm font-medium text-neutral-700">
        Commission Override % <span class="text-neutral-400">(optional — leave blank to use marketplace default)</span>
    </label>
    <div class="relative">
        <input id="commission_override" name="commission_override" type="number"
               min="0" max="100" step="0.01"
               value="{{ old('commission_override', $category?->commission_override) }}"
               placeholder="e.g. 7.5"
               class="block w-full border border-neutral-300 rounded-lg px-3 py-2.5 pr-8 text-neutral-900 text-sm
                      focus:outline-none focus:ring-2 focus:ring-[#F0A820] focus:border-[#F0A820]">
        <span class="absolute right-3 top-1/2 -translate-y-1/2 text-neutral-400 text-sm">%</span>
    </div>
    <p class="text-xs text-neutral-500">
        The marketplace default is 10%. Set a category-specific rate here to override it for all products in this category.
    </p>
    @error('commission_override') <p class="text-xs text-red-600">{{ $message }}</p> @enderror
</div>
