@php $part = $part ?? null; @endphp

<div>
    <label class="block text-body-sm font-medium text-ink mb-1">Name <span class="text-[rgb(var(--danger))]">*</span></label>
    <input type="text" name="name" value="{{ old('name', $part?->name) }}" required maxlength="200"
           class="w-full border border-line rounded-lg px-3 py-2 text-body-sm bg-surface focus:outline-none focus:ring-2 focus:ring-[rgb(var(--brand)/0.4)]">
    @error('name')<p class="text-caption text-[rgb(var(--danger))] mt-1">{{ $message }}</p>@enderror
</div>

<div class="grid grid-cols-2 gap-4">
    <div>
        <label class="block text-body-sm font-medium text-ink mb-1">Brand</label>
        <input type="text" name="brand" value="{{ old('brand', $part?->brand) }}" maxlength="100"
               class="w-full border border-line rounded-lg px-3 py-2 text-body-sm bg-surface focus:outline-none focus:ring-2 focus:ring-[rgb(var(--brand)/0.4)]">
    </div>
    <div>
        <label class="block text-body-sm font-medium text-ink mb-1">Category</label>
        <select name="category_id" class="w-full border border-line rounded-lg px-3 py-2 text-body-sm bg-surface">
            <option value="">—</option>
            @foreach ($categories as $cat)
                <option value="{{ $cat->id }}" @selected(old('category_id', $part?->category_id) === $cat->id)>{{ $cat->name }}</option>
            @endforeach
        </select>
    </div>
</div>

<div class="grid grid-cols-2 gap-4">
    <div>
        <label class="block text-body-sm font-medium text-ink mb-1">Primary OEM</label>
        <input type="text" name="primary_oem" value="{{ old('primary_oem', $part?->primary_oem) }}" maxlength="100"
               class="w-full border border-line rounded-lg px-3 py-2 text-body-sm bg-surface focus:outline-none focus:ring-2 focus:ring-[rgb(var(--brand)/0.4)]">
    </div>
    <div>
        <label class="block text-body-sm font-medium text-ink mb-1">Warranty (months)</label>
        <input type="number" name="warranty_months" value="{{ old('warranty_months', $part?->warranty_months) }}" min="0" max="600"
               class="w-full border border-line rounded-lg px-3 py-2 text-body-sm bg-surface focus:outline-none focus:ring-2 focus:ring-[rgb(var(--brand)/0.4)]">
    </div>
</div>

<div>
    <label class="block text-body-sm font-medium text-ink mb-1">Description</label>
    <textarea name="description" rows="3" maxlength="5000"
              class="w-full border border-line rounded-lg px-3 py-2 text-body-sm bg-surface focus:outline-none focus:ring-2 focus:ring-[rgb(var(--brand)/0.4)]">{{ old('description', $part?->description) }}</textarea>
</div>

<div class="flex items-center gap-6">
    <label class="flex items-center gap-2 text-body-sm text-ink">
        <input type="hidden" name="is_universal" value="0">
        <input type="checkbox" name="is_universal" value="1" @checked(old('is_universal', $part?->is_universal)) class="rounded border-line text-brand">
        Universal fit (no fitment rules needed)
    </label>
    <div>
        <label class="block text-body-sm font-medium text-ink mb-1">Status</label>
        <select name="status" class="border border-line rounded-lg px-3 py-2 text-body-sm bg-surface">
            <option value="active" @selected(old('status', $part?->status ?? 'active') === 'active')>Active</option>
            <option value="inactive" @selected(old('status', $part?->status) === 'inactive')>Inactive</option>
        </select>
    </div>
</div>
