{{--
    Shared vehicle form fields — rendered as stepper panels (steps 0–2).
    Expects: $makes (Collection of VehicleMake with loaded models), $vehicle (optional, for edit).
    Must be placed inside an <x-stepper> (provides the `step` Alpine var).
--}}
@php
    $v = $vehicle ?? null;
    $listingType = $type ?? $v?->vehicle_type ?? config('vehicle_types.default', 'vehicle');
    $conditions   = ['new' => 'New', 'used' => 'Used', 'salvage' => 'Salvage', 'rebuilt' => 'Rebuilt'];
    $bodyTypes = collect(\App\Modules\Vehicles\Models\Vehicle::bodyTypesFor($listingType))
        ->mapWithKeys(fn ($b) => [$b => \Illuminate\Support\Str::title(str_replace('_', ' ', $b))])->all();
    $transmissions = ['manual' => 'Manual', 'automatic' => 'Automatic', 'cvt' => 'CVT'];
    $fuelTypes    = ['petrol' => 'Petrol', 'diesel' => 'Diesel', 'electric' => 'Electric', 'hybrid' => 'Hybrid'];

    $label = 'block text-body-sm font-medium text-[rgb(var(--text))] mb-1';
    $input = 'w-full h-11 px-3.5 rounded-md bg-surface text-ink border border-strong focus-visible:outline-none focus:ring-2 focus:ring-brand focus:border-brand text-body-sm';
    $sel   = $input . ' appearance-none';
    $err   = 'mt-1 text-caption text-[rgb(var(--danger))]';
    $req   = '<span class="text-[rgb(var(--danger))]">*</span>';
@endphp

<input type="hidden" name="vehicle_type" value="{{ $listingType }}">

{{-- Step 0 — Identity --}}
<x-card padding="lg" x-show="step === 0" x-cloak class="space-y-5">
    <h2 class="text-h4 text-ink">Vehicle identity</h2>

    <div class="grid grid-cols-1 sm:grid-cols-2 gap-5" x-data="{
        makeId: '{{ old('make_id', $v?->make_id ?? '') }}',
        makes: {{ $makes->map(fn($m) => ['id' => $m->id, 'name' => $m->name, 'models' => $m->models->map(fn($mo) => ['id' => $mo->id, 'name' => $mo->name])->values()])->values()->toJson() }},
        get filteredModels() { return this.makes.find(m => m.id === this.makeId)?.models ?? []; }
    }">
        <div>
            <label class="{{ $label }}">Make {!! $req !!}</label>
            <select name="make_id" x-model="makeId" required class="{{ $sel }} @error('make_id') !border-[rgb(var(--danger))] @enderror">
                <option value="">Select make…</option>
                @foreach ($makes as $make)
                    <option value="{{ $make->id }}" @selected(old('make_id', $v?->make_id) === $make->id)>{{ $make->name }}</option>
                @endforeach
            </select>
            @error('make_id')<p class="{{ $err }}">{{ $message }}</p>@enderror
        </div>

        <div>
            <label class="{{ $label }}">Model {!! $req !!}</label>
            <select name="model_id" required class="{{ $sel }} @error('model_id') !border-[rgb(var(--danger))] @enderror">
                <option value="">Select model…</option>
                <template x-for="model in filteredModels" :key="model.id">
                    <option :value="model.id" :selected="model.id === '{{ old('model_id', $v?->model_id ?? '') }}'" x-text="model.name"></option>
                </template>
                @if ($v?->model_id)
                    <option value="{{ $v->model_id }}" selected>{{ $v->vehicleModel?->name }}</option>
                @endif
            </select>
            @error('model_id')<p class="{{ $err }}">{{ $message }}</p>@enderror
        </div>
    </div>

    <div class="grid grid-cols-1 sm:grid-cols-3 gap-5">
        <div>
            <label for="year" class="{{ $label }}">Year {!! $req !!}</label>
            <input type="number" id="year" name="year" value="{{ old('year', $v?->year) }}" min="1900" max="{{ date('Y') + 1 }}" required class="{{ $input }} @error('year') !border-[rgb(var(--danger))] @enderror">
            @error('year')<p class="{{ $err }}">{{ $message }}</p>@enderror
        </div>
        <div>
            <label for="condition" class="{{ $label }}">Condition {!! $req !!}</label>
            <select id="condition" name="condition" required class="{{ $sel }}">
                @foreach ($conditions as $value => $lbl)
                    <option value="{{ $value }}" @selected(old('condition', $v?->condition ?? 'used') === $value)>{{ $lbl }}</option>
                @endforeach
            </select>
            @error('condition')<p class="{{ $err }}">{{ $message }}</p>@enderror
        </div>
        <div>
            <label for="color" class="{{ $label }}">Color {!! $req !!}</label>
            <input type="text" id="color" name="color" value="{{ old('color', $v?->color) }}" maxlength="50" placeholder="e.g. Silver" class="{{ $input }} @error('color') !border-[rgb(var(--danger))] @enderror">
            @error('color')<p class="{{ $err }}">{{ $message }}</p>@enderror
        </div>
    </div>
</x-card>

{{-- Step 1 — Specifications --}}
<x-card padding="lg" x-show="step === 1" x-cloak class="space-y-5">
    <h2 class="text-h4 text-ink">Specifications</h2>

    <div class="grid grid-cols-1 sm:grid-cols-3 gap-5">
        <div>
            <label for="body_type" class="{{ $label }}">Body type {!! $req !!}</label>
            <select id="body_type" name="body_type" required class="{{ $sel }}">
                @foreach ($bodyTypes as $value => $lbl)
                    <option value="{{ $value }}" @selected(old('body_type', $v?->body_type) === $value)>{{ $lbl }}</option>
                @endforeach
            </select>
        </div>
        <div>
            <label for="transmission" class="{{ $label }}">Transmission {!! $req !!}</label>
            <select id="transmission" name="transmission" required class="{{ $sel }}">
                @foreach ($transmissions as $value => $lbl)
                    <option value="{{ $value }}" @selected(old('transmission', $v?->transmission) === $value)>{{ $lbl }}</option>
                @endforeach
            </select>
        </div>
        <div>
            <label for="fuel_type" class="{{ $label }}">Fuel type {!! $req !!}</label>
            <select id="fuel_type" name="fuel_type" required class="{{ $sel }}">
                @foreach ($fuelTypes as $value => $lbl)
                    <option value="{{ $value }}" @selected(old('fuel_type', $v?->fuel_type) === $value)>{{ $lbl }}</option>
                @endforeach
            </select>
        </div>
        <div>
            <label for="engine_cc" class="{{ $label }}">Engine (cc) <span class="text-muted font-normal">(optional)</span></label>
            <input type="number" id="engine_cc" name="engine_cc" value="{{ old('engine_cc', $v?->engine_cc) }}" min="50" max="20000" placeholder="e.g. 1998" class="{{ $input }}">
        </div>
        <div>
            <label for="mileage" class="{{ $label }}">Mileage (km) {!! $req !!}</label>
            <input type="number" id="mileage" name="mileage" value="{{ old('mileage', $v?->mileage ?? 0) }}" min="0" required class="{{ $input }} @error('mileage') !border-[rgb(var(--danger))] @enderror">
            @error('mileage')<p class="{{ $err }}">{{ $message }}</p>@enderror
        </div>
        <div>
            <label for="vin" class="{{ $label }}">VIN <span class="text-muted font-normal">(optional)</span></label>
            <input type="text" id="vin" name="vin" value="{{ old('vin', $v?->vin) }}" maxlength="17" minlength="17" placeholder="17-character VIN" class="{{ $input }} font-mono uppercase @error('vin') !border-[rgb(var(--danger))] @enderror">
            @error('vin')<p class="{{ $err }}">{{ $message }}</p>@enderror
        </div>
    </div>
</x-card>

{{-- Step 2 — Pricing & details --}}
<x-card padding="lg" x-show="step === 2" x-cloak class="space-y-5">
    <h2 class="text-h4 text-ink">Pricing &amp; details</h2>

    <p class="text-body-sm text-muted -mb-2">Enter a price in USD, ZWL, or both — at least one is required.</p>
    <div class="grid grid-cols-1 sm:grid-cols-2 gap-5">
        <div>
            <label for="price_usd" class="{{ $label }}">Price USD</label>
            <div class="relative">
                <span class="absolute left-3 top-1/2 -translate-y-1/2 text-body-sm text-muted">$</span>
                <input type="number" id="price_usd" name="price_usd" value="{{ old('price_usd', $v?->price_usd) }}" step="0.01" min="1" class="{{ $input }} !pl-8 @error('price_usd') !border-[rgb(var(--danger))] @enderror">
            </div>
            @error('price_usd')<p class="{{ $err }}">{{ $message }}</p>@enderror
        </div>
        <div>
            <label for="price_zwl" class="{{ $label }}">Price ZWL</label>
            <div class="relative">
                <span class="absolute left-3 top-1/2 -translate-y-1/2 text-caption text-muted">ZWL</span>
                <input type="number" id="price_zwl" name="price_zwl" value="{{ old('price_zwl', $v?->price_zwl) }}" step="0.01" min="1" class="{{ $input }} !pl-12 @error('price_zwl') !border-[rgb(var(--danger))] @enderror">
            </div>
            @error('price_zwl')<p class="{{ $err }}">{{ $message }}</p>@enderror
        </div>
    </div>

    {{-- Dynamic features / specs (D4) --}}
    @isset($featureDefinitions)
        @php $typeFeatures = $featureDefinitions->filter(fn ($d) => $d->appliesToType($listingType)); @endphp
        @if ($typeFeatures->isNotEmpty())
            <div class="border-t border-line pt-5">
                <h3 class="text-body-sm font-semibold text-ink mb-1">Features &amp; specs</h3>
                <p class="text-caption text-muted mb-4">Optional — set any that apply. Leave blank to skip.</p>
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    @foreach ($typeFeatures as $def)
                        @php $current = old("features.{$def->id}", $v?->featureValueFor($def->id)?->value); @endphp
                        <div>
                            <label class="{{ $label }}">{{ $def->name }}@if($def->unit) <span class="text-muted font-normal">({{ $def->unit }})</span>@endif</label>
                            @if ($def->type === 'boolean')
                                <select name="features[{{ $def->id }}]" class="{{ $sel }}">
                                    <option value="">—</option>
                                    <option value="1" @selected($current === '1' || $current === 1)>Yes</option>
                                    <option value="0" @selected($current === '0' || $current === 0)>No</option>
                                </select>
                            @elseif ($def->type === 'enum')
                                <select name="features[{{ $def->id }}]" class="{{ $sel }}">
                                    <option value="">—</option>
                                    @foreach (($def->options ?? []) as $opt)
                                        <option value="{{ $opt }}" @selected((string) $current === (string) $opt)>{{ $opt }}</option>
                                    @endforeach
                                </select>
                            @elseif ($def->type === 'number')
                                <input type="number" step="any" name="features[{{ $def->id }}]" value="{{ $current }}" class="{{ $input }}">
                            @else
                                <input type="text" name="features[{{ $def->id }}]" value="{{ $current }}" maxlength="255" class="{{ $input }}">
                            @endif
                        </div>
                    @endforeach
                </div>
            </div>
        @endif
    @endisset

    {{-- H2: Zimbabwe-market details --}}
    <div class="border-t border-line pt-5">
        <h3 class="text-body-sm font-semibold text-ink mb-3">Import &amp; pricing details</h3>
        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
            <div>
                <label for="ref_code" class="{{ $label }}">Reference code <span class="text-muted font-normal">(optional)</span></label>
                <input type="text" id="ref_code" name="ref_code" value="{{ old('ref_code', $v?->ref_code) }}" maxlength="40" class="{{ $input }}">
            </div>
            @if (in_array($listingType, ['vehicle', 'motorbike'], true))
                <div>
                    <label for="steering" class="{{ $label }}">Steering</label>
                    <select id="steering" name="steering" class="{{ $sel }}">
                        <option value="">—</option>
                        <option value="rhd" @selected(old('steering', $v?->steering) === 'rhd')>Right-hand drive</option>
                        <option value="lhd" @selected(old('steering', $v?->steering) === 'lhd')>Left-hand drive</option>
                    </select>
                </div>
            @endif
        </div>
        <div class="flex flex-wrap gap-x-6 gap-y-3 mt-4">
            <label class="flex items-center gap-2 text-body-sm text-[rgb(var(--text))]">
                <input type="hidden" name="show_price" value="0">
                <input type="checkbox" name="show_price" value="1" @checked(old('show_price', $v?->show_price ?? true)) class="size-5 rounded-[6px] border-strong text-brand accent-[rgb(var(--brand))] focus-visible:ring-2 focus-visible:ring-brand">
                Show price publicly <span class="text-muted">(uncheck for POA)</span>
            </label>
            <label class="flex items-center gap-2 text-body-sm text-[rgb(var(--text))]">
                <input type="hidden" name="duty_paid" value="0">
                <input type="checkbox" name="duty_paid" value="1" @checked(old('duty_paid', $v?->duty_paid)) class="size-5 rounded-[6px] border-strong text-brand accent-[rgb(var(--brand))] focus-visible:ring-2 focus-visible:ring-brand">
                Duty paid
            </label>
            <label class="flex items-center gap-2 text-body-sm text-[rgb(var(--text))]">
                <input type="hidden" name="is_recent_import" value="0">
                <input type="checkbox" name="is_recent_import" value="1" @checked(old('is_recent_import', $v?->is_recent_import)) class="size-5 rounded-[6px] border-strong text-brand accent-[rgb(var(--brand))] focus-visible:ring-2 focus-visible:ring-brand">
                Recent import
            </label>
        </div>
    </div>

    <div>
        <label for="description" class="{{ $label }}">Description <span class="text-muted font-normal">(optional)</span></label>
        <textarea id="description" name="description" rows="4" placeholder="Additional details about the vehicle…" class="w-full px-3.5 py-2.5 rounded-md bg-surface text-ink border border-strong focus-visible:outline-none focus:ring-2 focus:ring-brand focus:border-brand text-body-sm resize-y">{{ old('description', $v?->description) }}</textarea>
    </div>
</x-card>
