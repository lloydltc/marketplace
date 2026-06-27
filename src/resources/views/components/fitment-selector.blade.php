@props([
    'compact' => false,
    'action' => null,        // defaults to fitment.select
    'submitLabel' => 'Show compatible parts',
    'extraFields' => null,   // optional extra inputs (e.g. nickname) rendered in the form
])

@php
    $taxonomy = app(\App\Modules\Vehicles\Services\TaxonomyService::class);
    $ctx = app(\App\Modules\Parts\Services\FitmentContext::class);
    $sel = $ctx->get();
    $makes = $taxonomy->makes();
    $engines = $taxonomy->engines();
    $transmissions = $taxonomy->transmissions();
@endphp

<div class="bg-surface border border-line rounded-xl p-4 sm:p-5"
     x-data="fitmentSelector({
        modelsUrl: @js(route('fitment.models')),
        generationsUrl: @js(route('fitment.generations')),
        variantsUrl: @js(route('fitment.variants')),
        makeId: @js($sel['make_id'] ?? ''),
        modelId: @js($sel['model_id'] ?? ''),
        generationId: @js($sel['generation_id'] ?? ''),
        variantId: @js($sel['variant_id'] ?? ''),
     })" x-init="init()">

    <div class="flex items-center justify-between mb-3">
        <h3 class="text-body font-semibold text-ink">Find parts for your vehicle</h3>
        @if ($ctx->has())
            <form method="POST" action="{{ route('fitment.clear') }}">
                @csrf
                <button type="submit" class="text-caption text-[rgb(var(--text-muted))] hover:text-ink">Clear</button>
            </form>
        @endif
    </div>

    <form method="POST" action="{{ $action ?? route('fitment.select') }}" class="grid grid-cols-2 lg:grid-cols-3 gap-3">
        @csrf
        {{ $extraFields }}

        <select name="make_id" required x-model="makeId" @change="onMakeChange()"
                class="border border-line rounded-lg px-3 py-2 text-body-sm bg-surface focus:outline-none focus:ring-2 focus:ring-[rgb(var(--brand)/0.4)]">
            <option value="">Make</option>
            @foreach ($makes as $m)
                <option value="{{ $m['id'] }}">{{ $m['name'] }}</option>
            @endforeach
        </select>

        <select name="model_id" required x-model="modelId" @change="onModelChange()" :disabled="!makeId"
                class="border border-line rounded-lg px-3 py-2 text-body-sm bg-surface focus:outline-none focus:ring-2 focus:ring-[rgb(var(--brand)/0.4)]">
            <option value="">Model</option>
            <template x-for="o in models" :key="o.id"><option :value="o.id" x-text="o.name"></option></template>
        </select>

        <input type="number" name="year" min="1900" max="2100" placeholder="Year" value="{{ $sel['year'] }}"
               class="border border-line rounded-lg px-3 py-2 text-body-sm bg-surface focus:outline-none focus:ring-2 focus:ring-[rgb(var(--brand)/0.4)]">

        @unless ($compact)
            <select name="generation_id" x-model="generationId" @change="onGenerationChange()" :disabled="!modelId"
                    class="border border-line rounded-lg px-3 py-2 text-body-sm bg-surface focus:outline-none focus:ring-2 focus:ring-[rgb(var(--brand)/0.4)]">
                <option value="">Generation (any)</option>
                <template x-for="o in generations" :key="o.id"><option :value="o.id" x-text="o.name"></option></template>
            </select>

            <select name="variant_id" x-model="variantId" :disabled="!modelId"
                    class="border border-line rounded-lg px-3 py-2 text-body-sm bg-surface focus:outline-none focus:ring-2 focus:ring-[rgb(var(--brand)/0.4)]">
                <option value="">Variant (any)</option>
                <template x-for="o in variants" :key="o.id"><option :value="o.id" x-text="o.name"></option></template>
            </select>

            <select name="engine_id"
                    class="border border-line rounded-lg px-3 py-2 text-body-sm bg-surface focus:outline-none focus:ring-2 focus:ring-[rgb(var(--brand)/0.4)]">
                <option value="">Engine (any)</option>
                @foreach ($engines as $e)
                    <option value="{{ $e['id'] }}" @selected(($sel['engine_id'] ?? null) === $e['id'])>{{ $e['code'] }}</option>
                @endforeach
            </select>

            <select name="transmission_id"
                    class="border border-line rounded-lg px-3 py-2 text-body-sm bg-surface focus:outline-none focus:ring-2 focus:ring-[rgb(var(--brand)/0.4)]">
                <option value="">Transmission (any)</option>
                @foreach ($transmissions as $t)
                    <option value="{{ $t['id'] }}" @selected(($sel['transmission_id'] ?? null) === $t['id'])>{{ ucfirst($t['type']) }}</option>
                @endforeach
            </select>
        @endunless

        <button type="submit"
                class="col-span-2 lg:col-span-1 bg-brand hover:opacity-90 text-on-brand font-semibold px-4 py-2 rounded-lg text-body-sm transition-opacity">
            {{ $submitLabel }}
        </button>
    </form>
</div>

@once
    <script>
        function fitmentSelector(cfg) {
            return {
                ...cfg,
                models: [], generations: [], variants: [],
                async fetchJson(url, params) {
                    const q = new URLSearchParams(params).toString();
                    const r = await fetch(url + '?' + q, { headers: { Accept: 'application/json' } });
                    return r.ok ? r.json() : [];
                },
                async init() {
                    if (this.makeId) this.models = await this.fetchJson(this.modelsUrl, { make_id: this.makeId });
                    if (this.modelId) {
                        this.generations = await this.fetchJson(this.generationsUrl, { model_id: this.modelId });
                        this.variants = await this.fetchJson(this.variantsUrl, { model_id: this.modelId });
                    }
                },
                async onMakeChange() {
                    this.modelId = ''; this.generationId = ''; this.variantId = '';
                    this.generations = []; this.variants = [];
                    this.models = this.makeId ? await this.fetchJson(this.modelsUrl, { make_id: this.makeId }) : [];
                },
                async onModelChange() {
                    this.generationId = ''; this.variantId = '';
                    if (!this.modelId) { this.generations = []; this.variants = []; return; }
                    this.generations = await this.fetchJson(this.generationsUrl, { model_id: this.modelId });
                    this.variants = await this.fetchJson(this.variantsUrl, { model_id: this.modelId });
                },
                async onGenerationChange() {
                    this.variants = await this.fetchJson(this.variantsUrl, { model_id: this.modelId, generation_id: this.generationId });
                },
            };
        }
    </script>
@endonce
