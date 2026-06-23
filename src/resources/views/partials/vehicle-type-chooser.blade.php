{{-- H0: pick a listing type before the editor opens. Reloads the same create
     page with ?type=…, which then renders the type-adapted form. --}}
<div class="max-w-3xl mx-auto text-center py-6">
    <h1 class="text-2xl font-semibold text-neutral-900 mb-2">What are you listing?</h1>
    <p class="text-sm text-neutral-500 mb-8">Choose a type — the form adapts to it.</p>

    <div class="grid grid-cols-2 sm:grid-cols-4 gap-4">
        @foreach (config('vehicle_types.types') as $key => $cfg)
            <a href="{{ request()->fullUrlWithQuery(['type' => $key]) }}"
               class="bg-white border border-neutral-200 rounded-xl p-6 shadow-sm hover:border-[#F0A820] hover:shadow-md transition-all flex flex-col items-center gap-3">
                <span class="text-4xl" aria-hidden="true">{{ $cfg['icon'] }}</span>
                <span class="text-sm font-semibold text-neutral-800">{{ $cfg['plural'] }}</span>
            </a>
        @endforeach
    </div>
</div>
