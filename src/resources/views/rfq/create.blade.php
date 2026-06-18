<x-layouts.app>
    <x-slot:title>Request a Part</x-slot:title>

    <div class="max-w-2xl mx-auto px-4 sm:px-6 lg:px-8 py-12">
        <div class="text-center mb-6">
            <h1 class="text-2xl font-semibold text-neutral-900">Can't find it? Request it.</h1>
            <p class="text-sm text-neutral-500 mt-1">Post what you need and verified vendors will send you quotes.</p>
        </div>

        @guest
            <div class="mb-5 bg-amber-50 border border-amber-200 rounded-lg px-4 py-3 text-sm text-amber-800 text-center">
                <a href="{{ route('login') }}" class="font-medium underline">Sign in</a> to post a request and track quotes.
            </div>
        @endguest

        @error('rfq')
            <div class="mb-5 bg-red-50 border border-red-200 text-red-700 text-sm rounded-lg px-4 py-3">{{ $message }}</div>
        @enderror

        <form method="POST" action="{{ route('rfq.store') }}" class="bg-white border border-neutral-200 rounded-xl shadow-sm p-6 space-y-4">
            @csrf

            <div class="space-y-1">
                <label class="block text-sm font-medium text-neutral-700">What part do you need? <span class="text-red-500">*</span></label>
                <textarea name="part_description" rows="3" required
                          class="block w-full border rounded-lg px-3 py-2.5 text-sm @error('part_description') border-red-500 @else border-neutral-300 @enderror"
                          placeholder="e.g. Front brake calliper for…">{{ old('part_description', $prefill) }}</textarea>
                @error('part_description') <p class="text-xs text-red-600">{{ $message }}</p> @enderror
            </div>

            <div class="grid grid-cols-2 gap-4">
                <div class="space-y-1">
                    <label class="block text-sm font-medium text-neutral-700">Make <span class="text-neutral-400">(optional)</span></label>
                    <select name="make_id" class="block w-full border border-neutral-300 rounded-lg px-3 py-2.5 text-sm">
                        <option value="">Any</option>
                        @foreach ($makes as $make)
                            <option value="{{ $make->id }}" @selected(old('make_id') === $make->id)>{{ $make->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="space-y-1">
                    <label class="block text-sm font-medium text-neutral-700">Year <span class="text-neutral-400">(optional)</span></label>
                    <input type="number" name="year" min="1900" max="2100" value="{{ old('year') }}"
                           class="block w-full border border-neutral-300 rounded-lg px-3 py-2.5 text-sm">
                </div>
            </div>

            <div class="grid grid-cols-2 gap-4">
                <div class="space-y-1">
                    <label class="block text-sm font-medium text-neutral-700">Budget from</label>
                    <input type="number" name="budget_min" step="0.01" min="0" value="{{ old('budget_min') }}"
                           class="block w-full border border-neutral-300 rounded-lg px-3 py-2.5 text-sm">
                </div>
                <div class="space-y-1">
                    <label class="block text-sm font-medium text-neutral-700">Budget to</label>
                    <input type="number" name="budget_max" step="0.01" min="0" value="{{ old('budget_max') }}"
                           class="block w-full border border-neutral-300 rounded-lg px-3 py-2.5 text-sm">
                </div>
            </div>

            <div class="space-y-1">
                <label class="block text-sm font-medium text-neutral-700">Your location <span class="text-red-500">*</span></label>
                <input name="location" required value="{{ old('location') }}" placeholder="City / suburb"
                       class="block w-full border rounded-lg px-3 py-2.5 text-sm @error('location') border-red-500 @else border-neutral-300 @enderror">
                @error('location') <p class="text-xs text-red-600">{{ $message }}</p> @enderror
            </div>

            <button type="submit"
                    class="w-full bg-[#F0A820] hover:bg-[#F0A820]/90 text-[#1A1A24] font-semibold py-2.5 rounded-lg text-sm transition-colors">
                Post request
            </button>
        </form>
    </div>
</x-layouts.app>
