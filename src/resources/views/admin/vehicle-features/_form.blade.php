@php $isEdit = $feature->exists; @endphp
<form method="POST" action="{{ $isEdit ? route('admin.vehicle-features.update', $feature) : route('admin.vehicle-features.store') }}"
      class="bg-white border border-neutral-200 rounded-xl shadow-sm p-6 space-y-5" x-data="{ type: '{{ old('type', $feature->type ?: 'boolean') }}' }">
    @csrf
    @if ($isEdit) @method('PUT') @endif

    @if ($errors->any())
        <div class="bg-red-50 border border-red-200 text-red-700 text-sm rounded-lg px-4 py-2">
            <ul class="list-disc list-inside">@foreach ($errors->all() as $e)<li>{{ $e }}</li>@endforeach</ul>
        </div>
    @endif

    <div>
        <label class="block text-sm font-medium text-neutral-700 mb-1">Name</label>
        <input type="text" name="name" value="{{ old('name', $feature->name) }}" required
               class="w-full border border-neutral-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-[#F0A820]/40">
        @if ($isEdit)<p class="mt-1 text-xs text-neutral-400">Key: {{ $feature->key }} (fixed)</p>@endif
    </div>

    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
        <div>
            <label class="block text-sm font-medium text-neutral-700 mb-1">Type</label>
            <select name="type" x-model="type" class="w-full border border-neutral-300 rounded-lg px-3 py-2 text-sm">
                <option value="boolean">Yes / No</option>
                <option value="number">Number</option>
                <option value="enum">Choice list</option>
                <option value="text">Text</option>
            </select>
        </div>
        <div>
            <label class="block text-sm font-medium text-neutral-700 mb-1">Group <span class="text-neutral-400 font-normal">(optional)</span></label>
            <input type="text" name="group" value="{{ old('group', $feature->group) }}" placeholder="e.g. Safety"
                   class="w-full border border-neutral-300 rounded-lg px-3 py-2 text-sm">
        </div>
    </div>

    <div x-show="type === 'number'" x-cloak>
        <label class="block text-sm font-medium text-neutral-700 mb-1">Unit <span class="text-neutral-400 font-normal">(optional)</span></label>
        <input type="text" name="unit" value="{{ old('unit', $feature->unit) }}" placeholder="e.g. doors, seats, L"
               class="w-full border border-neutral-300 rounded-lg px-3 py-2 text-sm">
    </div>

    <div x-show="type === 'enum'" x-cloak>
        <label class="block text-sm font-medium text-neutral-700 mb-1">Choices <span class="text-neutral-400 font-normal">(comma-separated)</span></label>
        <input type="text" name="options" value="{{ old('options', $feature->options ? implode(', ', $feature->options) : '') }}" placeholder="e.g. FWD, RWD, AWD, 4WD"
               class="w-full border border-neutral-300 rounded-lg px-3 py-2 text-sm">
    </div>

    <div>
        <label class="block text-sm font-medium text-neutral-700 mb-1">Applies to listing types <span class="text-neutral-400 font-normal">(none = all)</span></label>
        @php $selectedTypes = old('applies_to_types', $feature->applies_to_types ?? []); @endphp
        <div class="flex flex-wrap gap-4 mt-1">
            @foreach (config('vehicle_types.types') as $key => $cfg)
                <label class="flex items-center gap-1.5 text-sm text-neutral-700">
                    <input type="checkbox" name="applies_to_types[]" value="{{ $key }}"
                           @checked(in_array($key, (array) $selectedTypes, true))
                           class="rounded border-neutral-300 text-[#F0A820] focus:ring-[#F0A820]/40">
                    {{ $cfg['icon'] }} {{ $cfg['label'] }}
                </label>
            @endforeach
        </div>
    </div>

    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
        <div>
            <label class="block text-sm font-medium text-neutral-700 mb-1">Sort order</label>
            <input type="number" name="sort_order" value="{{ old('sort_order', $feature->sort_order ?? 0) }}" min="0"
                   class="w-full border border-neutral-300 rounded-lg px-3 py-2 text-sm">
        </div>
        <label class="flex items-center gap-2 mt-7 text-sm text-neutral-700">
            <input type="hidden" name="is_filterable" value="0">
            <input type="checkbox" name="is_filterable" value="1" @checked(old('is_filterable', $feature->is_filterable))
                   class="rounded border-neutral-300 text-[#F0A820] focus:ring-[#F0A820]/40">
            Buyers can filter by this
        </label>
    </div>

    <div class="flex items-center gap-3">
        <button type="submit" class="bg-[#F0A820] hover:bg-[#F0A820]/90 text-[#1A1A24] font-semibold px-5 py-2 rounded-lg text-sm">
            {{ $isEdit ? 'Save changes' : 'Add feature' }}
        </button>
        <a href="{{ route('admin.vehicle-features.index') }}" class="text-sm text-neutral-500 hover:text-neutral-700">Cancel</a>
    </div>
</form>
