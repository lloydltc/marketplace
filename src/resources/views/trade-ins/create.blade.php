<x-layouts.app>
    <x-slot:title>Trade in your vehicle</x-slot:title>

    <div class="max-w-2xl mx-auto px-4 sm:px-6 lg:px-8 py-10">
        <h1 class="text-h1 text-ink mb-1">Trade in your vehicle</h1>
        <p class="text-body-sm text-muted mb-6">Get a free estimate from comparable listings, then let verified dealers bid. An estimate — not an offer.</p>

        @if ($errors->any())
            <div class="mb-5 bg-[rgb(var(--danger)/0.12)] border border-[rgb(var(--danger)/0.3)] text-[rgb(var(--danger))] text-body-sm rounded-lg px-4 py-3">
                <ul class="list-disc list-inside">@foreach ($errors->all() as $e)<li>{{ $e }}</li>@endforeach</ul>
            </div>
        @endif

        <form method="POST" action="{{ route('trade-ins.store') }}" enctype="multipart/form-data"
              class="bg-surface border border-line rounded-xl shadow-e1 p-6 space-y-4"
              x-data="{ makes: {{ Illuminate\Support\Js::from($makes->map(fn ($m) => ['id' => $m->id, 'name' => $m->name, 'models' => $m->models->map(fn ($mo) => ['id' => $mo->id, 'name' => $mo->name])->values()])) }}, makeId: '', get models() { return (this.makes.find(m => m.id === this.makeId)?.models) ?? []; } }">
            @csrf
            <div class="grid grid-cols-2 gap-4">
                <div>
                    <label class="block text-body-sm font-medium text-ink mb-1">Make</label>
                    <select name="make_id" x-model="makeId" required class="w-full border border-line rounded-lg px-3 py-2 text-body-sm bg-surface">
                        <option value="">Select…</option>
                        <template x-for="m in makes" :key="m.id"><option :value="m.id" x-text="m.name"></option></template>
                    </select>
                </div>
                <div>
                    <label class="block text-body-sm font-medium text-ink mb-1">Model</label>
                    <select name="model_id" required :disabled="!makeId" class="w-full border border-line rounded-lg px-3 py-2 text-body-sm bg-surface">
                        <option value="">Select…</option>
                        <template x-for="mo in models" :key="mo.id"><option :value="mo.id" x-text="mo.name"></option></template>
                    </select>
                </div>
                <div>
                    <label class="block text-body-sm font-medium text-ink mb-1">Year</label>
                    <input type="number" name="year" min="1900" max="2100" value="{{ old('year') }}" required class="w-full border border-line rounded-lg px-3 py-2 text-body-sm bg-surface">
                </div>
                <div>
                    <label class="block text-body-sm font-medium text-ink mb-1">Mileage (km)</label>
                    <input type="number" name="mileage" min="0" value="{{ old('mileage') }}" required class="w-full border border-line rounded-lg px-3 py-2 text-body-sm bg-surface">
                </div>
            </div>
            <div>
                <label class="block text-body-sm font-medium text-ink mb-1">Condition</label>
                <select name="condition" required class="w-full border border-line rounded-lg px-3 py-2 text-body-sm bg-surface">
                    @foreach ($conditions as $c)<option value="{{ $c }}">{{ ucfirst($c) }}</option>@endforeach
                </select>
            </div>
            <div>
                <label class="block text-body-sm font-medium text-ink mb-1">Notes (optional)</label>
                <textarea name="notes" rows="3" maxlength="1000" class="w-full border border-line rounded-lg px-3 py-2 text-body-sm bg-surface">{{ old('notes') }}</textarea>
            </div>
            <div>
                <label class="block text-body-sm font-medium text-ink mb-1">Photos (optional)</label>
                <input type="file" name="photos[]" accept="image/*" multiple class="block w-full text-body-sm text-ink file:mr-4 file:rounded-lg file:border-0 file:bg-surface-2 file:px-4 file:py-2 file:text-body-sm">
            </div>
            <div class="flex justify-end pt-2">
                <x-button type="submit" variant="primary" size="lg">Get my estimate</x-button>
            </div>
        </form>
    </div>
</x-layouts.app>
