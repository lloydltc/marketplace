{{--
    Vehicle filter form. Rendered once; CSS turns it into a static rail on lg+ and a
    bottom-sheet on mobile (the `open` flag lives in the parent x-data on the index).
    Keeps every filter field name, the live-count Alpine (count seed asserted in tests),
    and save-search.
--}}
<form method="GET" action="{{ route('vehicles.index') }}"
      x-data="{
          endpoint: @js(route('search.vehicles.count')),
          count: {{ $vehicles->total() }},
          loading: false,
          get label() {
              if (this.loading) return 'Counting…';
              return 'Show ' + Number(this.count).toLocaleString() + (this.count === 1 ? ' vehicle' : ' vehicles');
          },
          refresh() {
              this.loading = true;
              const params = new URLSearchParams(new FormData(this.$root)).toString();
              fetch(this.endpoint + '?' + params, { headers: { Accept: 'application/json' } })
                  .then((r) => r.json()).then((d) => { this.count = d.count; })
                  .catch(() => {}).finally(() => { this.loading = false; });
          }
      }"
      x-on:change="refresh()" x-on:input.debounce.500ms="refresh()"
      class="space-y-5">

    @if (request('vehicle_type'))
        <input type="hidden" name="vehicle_type" value="{{ request('vehicle_type') }}">
    @endif

    <div>
        <label for="f-search" class="block mb-1.5 text-body-sm font-medium text-[rgb(var(--text))]">Keyword</label>
        <x-search-autocomplete id="f-search" name="search" :endpoint="route('search.vehicles')"
                               :value="request('search')" placeholder="Make, model or year…" />
    </div>

    <x-select label="Make" name="make_id">
        <option value="">All makes</option>
        @foreach ($makes as $make)
            <option value="{{ $make->id }}" @selected(request('make_id') === $make->id)>{{ $make->name }}</option>
        @endforeach
    </x-select>

    <div class="grid grid-cols-2 gap-3">
        <x-input label="Min price" name="min_price" type="number" min="0" :value="request('min_price')" placeholder="USD" />
        <x-input label="Max price" name="max_price" type="number" min="0" :value="request('max_price')" placeholder="USD" />
    </div>

    <div class="grid grid-cols-2 gap-3">
        <x-input label="Year from" name="year_min" type="number" min="1900" :value="request('year_min')" />
        <x-input label="Year to" name="year_max" type="number" min="1900" :value="request('year_max')" />
    </div>

    <x-select label="Body type" name="body_type">
        <option value="">All body types</option>
        @foreach (['sedan','hatchback','suv','pickup','van','minivan','wagon','coupe','convertible','bus','truck','other'] as $body)
            <option value="{{ $body }}" @selected(request('body_type') === $body)>{{ ucfirst($body) }}</option>
        @endforeach
    </x-select>

    <x-select label="Transmission" name="transmission">
        <option value="">Any transmission</option>
        <option value="manual" @selected(request('transmission') === 'manual')>Manual</option>
        <option value="automatic" @selected(request('transmission') === 'automatic')>Automatic</option>
        <option value="cvt" @selected(request('transmission') === 'cvt')>CVT</option>
    </x-select>

    <x-select label="Fuel type" name="fuel_type">
        <option value="">All fuel types</option>
        <option value="petrol" @selected(request('fuel_type') === 'petrol')>Petrol</option>
        <option value="diesel" @selected(request('fuel_type') === 'diesel')>Diesel</option>
        <option value="electric" @selected(request('fuel_type') === 'electric')>Electric</option>
        <option value="hybrid" @selected(request('fuel_type') === 'hybrid')>Hybrid</option>
    </x-select>

    <x-select label="Condition" name="condition">
        <option value="">All conditions</option>
        <option value="new" @selected(request('condition') === 'new')>New</option>
        <option value="used" @selected(request('condition') === 'used')>Used</option>
        <option value="salvage" @selected(request('condition') === 'salvage')>Salvage</option>
        <option value="rebuilt" @selected(request('condition') === 'rebuilt')>Rebuilt</option>
    </x-select>

    {{-- Dynamic feature facets (D3) — name embedded in options to stay compact --}}
    @if (isset($filterableFeatures) && $filterableFeatures->isNotEmpty())
        <div class="space-y-5 pt-5 border-t border-base">
            <p class="text-overline uppercase text-[rgb(var(--text-muted))]">Features</p>
            @foreach ($filterableFeatures as $def)
                @php $fv = request("features.{$def->id}"); @endphp
                @if ($def->type === 'boolean')
                    <x-select name="features[{{ $def->id }}]">
                        <option value="">{{ $def->name }}: any</option>
                        <option value="1" @selected($fv === '1')>{{ $def->name }}: Yes</option>
                        <option value="0" @selected($fv === '0')>{{ $def->name }}: No</option>
                    </x-select>
                @elseif ($def->type === 'enum')
                    <x-select name="features[{{ $def->id }}]">
                        <option value="">{{ $def->name }}: any</option>
                        @foreach (($def->options ?? []) as $opt)
                            <option value="{{ $opt }}" @selected((string) $fv === (string) $opt)>{{ $def->name }}: {{ $opt }}</option>
                        @endforeach
                    </x-select>
                @elseif ($def->type === 'number')
                    <x-input name="features[{{ $def->id }}]" type="number" min="0" :value="$fv"
                             placeholder="{{ $def->name }}{{ $def->unit ? ' (' . $def->unit . ')' : '' }}" />
                @endif
            @endforeach
        </div>
    @endif

    <div class="flex items-center gap-3 pt-1">
        <x-button type="submit" class="flex-1"><span x-text="label">Search</span></x-button>
        @if (request()->hasAny(['search','make_id','condition','fuel_type','body_type','transmission','year_min','year_max','min_price','max_price','features']))
            <a href="{{ route('vehicles.index', request('vehicle_type') ? ['vehicle_type' => request('vehicle_type')] : []) }}"
               class="text-body-sm text-[rgb(var(--text-muted))] hover:text-[rgb(var(--text))] px-2">Clear</a>
        @endif
    </div>

    <div class="pt-1">
        <x-save-search type="vehicles" :active="request()->hasAny(['search','make_id','condition','fuel_type','body_type','transmission','year_min','year_max','min_price','max_price'])" />
    </div>
</form>
