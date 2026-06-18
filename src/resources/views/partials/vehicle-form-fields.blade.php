{{--
    Shared vehicle form fields.
    Expects: $makes (Collection of VehicleMake with loaded models), $vehicle (optional, for edit).
--}}
@php
    $v = $vehicle ?? null;
    $conditions   = ['new' => 'New', 'used' => 'Used', 'salvage' => 'Salvage', 'rebuilt' => 'Rebuilt'];
    $bodyTypes    = ['sedan' => 'Sedan', 'hatchback' => 'Hatchback', 'suv' => 'SUV', 'pickup' => 'Pickup',
                     'van' => 'Van', 'minivan' => 'Minivan', 'wagon' => 'Station Wagon',
                     'coupe' => 'Coupe', 'convertible' => 'Convertible',
                     'bus' => 'Bus', 'truck' => 'Truck', 'other' => 'Other'];
    $transmissions = ['manual' => 'Manual', 'automatic' => 'Automatic', 'cvt' => 'CVT'];
    $fuelTypes    = ['petrol' => 'Petrol', 'diesel' => 'Diesel', 'electric' => 'Electric', 'hybrid' => 'Hybrid'];
@endphp

<div class="bg-white border border-neutral-200 rounded-xl shadow-sm p-6 space-y-5">
    <h2 class="text-base font-semibold text-neutral-800">Vehicle Identity</h2>

    {{-- Make / Model --}}
    <div class="grid grid-cols-1 sm:grid-cols-2 gap-5" x-data="{
        makeId: '{{ old('make_id', $v?->make_id ?? '') }}',
        makes: {{ $makes->map(fn($m) => ['id' => $m->id, 'name' => $m->name, 'models' => $m->models->map(fn($mo) => ['id' => $mo->id, 'name' => $mo->name])->values()])->values()->toJson() }},
        get filteredModels() { return this.makes.find(m => m.id === this.makeId)?.models ?? []; }
    }">
        <div>
            <label class="block text-sm font-medium text-neutral-700 mb-1">Make <span class="text-red-500">*</span></label>
            <select name="make_id" x-model="makeId" required
                    class="w-full border border-neutral-200 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-[#F0A820]/40 @error('make_id') border-red-400 @enderror">
                <option value="">Select make…</option>
                @foreach ($makes as $make)
                    <option value="{{ $make->id }}" @selected(old('make_id', $v?->make_id) === $make->id)>{{ $make->name }}</option>
                @endforeach
            </select>
            @error('make_id')<p class="mt-1 text-xs text-red-500">{{ $message }}</p>@enderror
        </div>

        <div>
            <label class="block text-sm font-medium text-neutral-700 mb-1">Model <span class="text-red-500">*</span></label>
            <select name="model_id" required
                    class="w-full border border-neutral-200 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-[#F0A820]/40 @error('model_id') border-red-400 @enderror">
                <option value="">Select model…</option>
                <template x-for="model in filteredModels" :key="model.id">
                    <option :value="model.id"
                            :selected="model.id === '{{ old('model_id', $v?->model_id ?? '') }}'"
                            x-text="model.name"></option>
                </template>
                {{-- Fallback for when JS is off / initial render --}}
                @if ($v?->model_id)
                    <option value="{{ $v->model_id }}" selected>{{ $v->vehicleModel?->name }}</option>
                @endif
            </select>
            @error('model_id')<p class="mt-1 text-xs text-red-500">{{ $message }}</p>@enderror
        </div>
    </div>

    {{-- Year / Condition / Color --}}
    <div class="grid grid-cols-1 sm:grid-cols-3 gap-5">
        <div>
            <label for="year" class="block text-sm font-medium text-neutral-700 mb-1">Year <span class="text-red-500">*</span></label>
            <input type="number" id="year" name="year"
                   value="{{ old('year', $v?->year) }}"
                   min="1900" max="{{ date('Y') + 1 }}" required
                   class="w-full border border-neutral-200 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-[#F0A820]/40 @error('year') border-red-400 @enderror">
            @error('year')<p class="mt-1 text-xs text-red-500">{{ $message }}</p>@enderror
        </div>

        <div>
            <label for="condition" class="block text-sm font-medium text-neutral-700 mb-1">Condition <span class="text-red-500">*</span></label>
            <select id="condition" name="condition" required
                    class="w-full border border-neutral-200 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-[#F0A820]/40">
                @foreach ($conditions as $value => $label)
                    <option value="{{ $value }}" @selected(old('condition', $v?->condition ?? 'used') === $value)>{{ $label }}</option>
                @endforeach
            </select>
            @error('condition')<p class="mt-1 text-xs text-red-500">{{ $message }}</p>@enderror
        </div>

        <div>
            <label for="color" class="block text-sm font-medium text-neutral-700 mb-1">Color <span class="text-red-500">*</span></label>
            <input type="text" id="color" name="color"
                   value="{{ old('color', $v?->color) }}" maxlength="50"
                   placeholder="e.g. Silver"
                   class="w-full border border-neutral-200 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-[#F0A820]/40 @error('color') border-red-400 @enderror">
            @error('color')<p class="mt-1 text-xs text-red-500">{{ $message }}</p>@enderror
        </div>
    </div>
</div>

<div class="bg-white border border-neutral-200 rounded-xl shadow-sm p-6 space-y-5">
    <h2 class="text-base font-semibold text-neutral-800">Specifications</h2>

    <div class="grid grid-cols-1 sm:grid-cols-3 gap-5">
        <div>
            <label for="body_type" class="block text-sm font-medium text-neutral-700 mb-1">Body Type <span class="text-red-500">*</span></label>
            <select id="body_type" name="body_type" required
                    class="w-full border border-neutral-200 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-[#F0A820]/40">
                @foreach ($bodyTypes as $value => $label)
                    <option value="{{ $value }}" @selected(old('body_type', $v?->body_type) === $value)>{{ $label }}</option>
                @endforeach
            </select>
        </div>

        <div>
            <label for="transmission" class="block text-sm font-medium text-neutral-700 mb-1">Transmission <span class="text-red-500">*</span></label>
            <select id="transmission" name="transmission" required
                    class="w-full border border-neutral-200 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-[#F0A820]/40">
                @foreach ($transmissions as $value => $label)
                    <option value="{{ $value }}" @selected(old('transmission', $v?->transmission) === $value)>{{ $label }}</option>
                @endforeach
            </select>
        </div>

        <div>
            <label for="fuel_type" class="block text-sm font-medium text-neutral-700 mb-1">Fuel Type <span class="text-red-500">*</span></label>
            <select id="fuel_type" name="fuel_type" required
                    class="w-full border border-neutral-200 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-[#F0A820]/40">
                @foreach ($fuelTypes as $value => $label)
                    <option value="{{ $value }}" @selected(old('fuel_type', $v?->fuel_type) === $value)>{{ $label }}</option>
                @endforeach
            </select>
        </div>

        <div>
            <label for="engine_cc" class="block text-sm font-medium text-neutral-700 mb-1">Engine (cc) <span class="text-neutral-400 font-normal">(optional)</span></label>
            <input type="number" id="engine_cc" name="engine_cc"
                   value="{{ old('engine_cc', $v?->engine_cc) }}"
                   min="50" max="20000" placeholder="e.g. 1998"
                   class="w-full border border-neutral-200 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-[#F0A820]/40">
        </div>

        <div>
            <label for="mileage" class="block text-sm font-medium text-neutral-700 mb-1">Mileage (km) <span class="text-red-500">*</span></label>
            <input type="number" id="mileage" name="mileage"
                   value="{{ old('mileage', $v?->mileage ?? 0) }}"
                   min="0" required
                   class="w-full border border-neutral-200 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-[#F0A820]/40 @error('mileage') border-red-400 @enderror">
            @error('mileage')<p class="mt-1 text-xs text-red-500">{{ $message }}</p>@enderror
        </div>

        <div>
            <label for="vin" class="block text-sm font-medium text-neutral-700 mb-1">VIN <span class="text-neutral-400 font-normal">(optional)</span></label>
            <input type="text" id="vin" name="vin"
                   value="{{ old('vin', $v?->vin) }}" maxlength="17" minlength="17"
                   placeholder="17-character VIN"
                   class="w-full border border-neutral-200 rounded-lg px-3 py-2 text-sm font-mono uppercase focus:outline-none focus:ring-2 focus:ring-[#F0A820]/40 @error('vin') border-red-400 @enderror">
            @error('vin')<p class="mt-1 text-xs text-red-500">{{ $message }}</p>@enderror
        </div>
    </div>
</div>

<div class="bg-white border border-neutral-200 rounded-xl shadow-sm p-6 space-y-5">
    <h2 class="text-base font-semibold text-neutral-800">Pricing</h2>

    <div class="grid grid-cols-1 sm:grid-cols-2 gap-5">
        <div>
            <label for="price_zwl" class="block text-sm font-medium text-neutral-700 mb-1">Price ZWL <span class="text-red-500">*</span></label>
            <div class="relative">
                <span class="absolute left-3 top-2 text-sm text-neutral-400">ZWL</span>
                <input type="number" id="price_zwl" name="price_zwl"
                       value="{{ old('price_zwl', $v?->price_zwl) }}"
                       step="0.01" min="1" required
                       class="w-full pl-12 border border-neutral-200 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-[#F0A820]/40 @error('price_zwl') border-red-400 @enderror">
            </div>
            @error('price_zwl')<p class="mt-1 text-xs text-red-500">{{ $message }}</p>@enderror
        </div>

        <div>
            <label for="price_usd" class="block text-sm font-medium text-neutral-700 mb-1">Price USD <span class="text-neutral-400 font-normal">(optional)</span></label>
            <div class="relative">
                <span class="absolute left-3 top-2 text-sm text-neutral-400">USD</span>
                <input type="number" id="price_usd" name="price_usd"
                       value="{{ old('price_usd', $v?->price_usd) }}"
                       step="0.01" min="1"
                       class="w-full pl-12 border border-neutral-200 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-[#F0A820]/40">
            </div>
        </div>
    </div>

    <div>
        <label for="description" class="block text-sm font-medium text-neutral-700 mb-1">Description <span class="text-neutral-400 font-normal">(optional)</span></label>
        <textarea id="description" name="description" rows="4"
                  placeholder="Additional details about the vehicle…"
                  class="w-full border border-neutral-200 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-[#F0A820]/40 resize-y">{{ old('description', $v?->description) }}</textarea>
    </div>
</div>
