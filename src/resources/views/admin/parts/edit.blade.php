<x-layouts.app>
    <x-slot:title>Edit {{ $part->name }}</x-slot:title>

    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
      <div class="lg:flex lg:gap-8">
        <x-admin-sidebar />
        <div class="flex-1 min-w-0 mt-6 lg:mt-0">
            <a href="{{ route('admin.parts.index') }}" class="text-body-sm text-muted hover:text-ink">← Parts catalog</a>

            @if (session('status'))
                <div class="my-4 bg-[rgb(var(--success)/0.12)] border border-[rgb(var(--success)/0.3)] text-[rgb(var(--success))] text-body-sm rounded-lg px-4 py-3">{{ session('status') }}</div>
            @endif

            <h1 class="text-h1 text-ink mt-2 mb-6">{{ $part->name }}</h1>

            <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                {{-- Details --}}
                <form method="POST" action="{{ route('admin.parts.update', $part) }}" class="bg-surface border border-line rounded-xl shadow-e1 p-6 space-y-4">
                    @csrf @method('PUT')
                    @include('admin.parts._form-fields', ['part' => $part])
                    <div class="flex items-center justify-between pt-2">
                        <form method="POST" action="{{ route('admin.parts.destroy', $part) }}" onsubmit="return confirm('Delete this part?')">
                            @csrf @method('DELETE')
                            <button type="submit" class="text-body-sm text-[rgb(var(--danger))] hover:underline">Delete</button>
                        </form>
                        <x-button type="submit" variant="primary">Save</x-button>
                    </div>
                </form>

                <div class="space-y-6">
                    {{-- OEM numbers --}}
                    <div class="bg-surface border border-line rounded-xl shadow-e1 p-6">
                        <h2 class="text-h4 text-ink mb-3">OEM &amp; cross-reference numbers</h2>
                        @if ($part->oemNumbers->isNotEmpty())
                            <div class="flex flex-wrap gap-2 mb-4">
                                @foreach ($part->oemNumbers as $oem)
                                    <span class="inline-flex items-center gap-2 text-caption font-mono bg-surface-2 border border-line rounded-full pl-3 pr-2 py-1">
                                        {{ $oem->number }} <span class="text-muted">{{ $oem->type }}</span>
                                        <form method="POST" action="{{ route('admin.parts.oem.remove', [$part, $oem]) }}">@csrf @method('DELETE')<button class="text-muted hover:text-[rgb(var(--danger))]">✕</button></form>
                                    </span>
                                @endforeach
                            </div>
                        @endif
                        <form method="POST" action="{{ route('admin.parts.oem.add', $part) }}" class="flex flex-wrap items-end gap-2">
                            @csrf
                            <input type="text" name="number" placeholder="Number" required class="flex-1 border border-line rounded-lg px-3 py-2 text-body-sm bg-surface">
                            <select name="type" class="border border-line rounded-lg px-3 py-2 text-body-sm bg-surface">
                                <option value="oem">OEM</option><option value="aftermarket">Aftermarket</option><option value="cross_ref">Cross-ref</option>
                            </select>
                            <x-button type="submit" variant="outline" size="sm">Add</x-button>
                        </form>
                    </div>

                    {{-- Fitment authoring (with ranges) --}}
                    <div class="bg-surface border border-line rounded-xl shadow-e1 p-6"
                         x-data="{ makes: {{ Illuminate\Support\Js::from($makes->map(fn($m)=>['id'=>$m->id,'name'=>$m->name,'models'=>$m->models->map(fn($mo)=>['id'=>$mo->id,'name'=>$mo->name])->values()])) }}, makeId:'', get models(){ return (this.makes.find(m=>m.id===this.makeId)?.models) ?? []; } }">
                        <h2 class="text-h4 text-ink mb-3">Fitment {{ $part->is_universal ? '(part is universal — rules optional)' : '' }}</h2>
                        @if ($part->fitments->isNotEmpty())
                            <div class="flex flex-wrap gap-2 mb-4">
                                @foreach ($part->fitments as $f)
                                    <span class="inline-flex items-center gap-2 text-caption bg-surface-2 border border-line rounded-full pl-3 pr-2 py-1">
                                        {{ $f->label() }}
                                        <form method="POST" action="{{ route('admin.parts.fitments.remove', [$part, $f]) }}">@csrf @method('DELETE')<button class="text-muted hover:text-[rgb(var(--danger))]">✕</button></form>
                                    </span>
                                @endforeach
                            </div>
                        @endif
                        <form method="POST" action="{{ route('admin.parts.fitments.add', $part) }}" class="grid grid-cols-2 gap-2">
                            @csrf
                            <select name="make_id" x-model="makeId" required class="border border-line rounded-lg px-3 py-2 text-body-sm bg-surface">
                                <option value="">Make</option>
                                <template x-for="m in makes" :key="m.id"><option :value="m.id" x-text="m.name"></option></template>
                            </select>
                            <select name="model_id" required :disabled="!makeId" class="border border-line rounded-lg px-3 py-2 text-body-sm bg-surface">
                                <option value="">Model</option>
                                <template x-for="mo in models" :key="mo.id"><option :value="mo.id" x-text="mo.name"></option></template>
                            </select>
                            <input type="number" name="year_start" placeholder="Year from" min="1900" max="2100" class="border border-line rounded-lg px-3 py-2 text-body-sm bg-surface">
                            <input type="number" name="year_end" placeholder="Year to" min="1900" max="2100" class="border border-line rounded-lg px-3 py-2 text-body-sm bg-surface">
                            <x-button type="submit" variant="outline" size="sm" class="col-span-2">Add fitment</x-button>
                        </form>
                    </div>

                    {{-- Merge duplicate --}}
                    <div class="bg-surface border border-line rounded-xl shadow-e1 p-6">
                        <h2 class="text-h4 text-ink mb-1">Merge a duplicate into this part</h2>
                        <p class="text-caption text-muted mb-3">The duplicate's offers, OEM numbers &amp; fitments move here; it's then removed.</p>
                        <form method="POST" action="{{ route('admin.parts.merge') }}" class="flex items-end gap-2"
                              onsubmit="return confirm('Merge the selected duplicate into this part? This cannot be undone.')">
                            @csrf
                            <input type="hidden" name="keeper_id" value="{{ $part->id }}">
                            <input type="text" name="duplicate_id" placeholder="Duplicate part UUID" required
                                   class="flex-1 border border-line rounded-lg px-3 py-2 text-body-sm bg-surface font-mono">
                            <x-button type="submit" variant="danger" size="sm">Merge</x-button>
                        </form>
                    </div>
                </div>
            </div>
        </div>
      </div>
    </div>
</x-layouts.app>
