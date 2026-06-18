<x-layouts.app>
    <x-slot:title>Concierge — We Find It For You</x-slot:title>

    <div class="max-w-2xl mx-auto px-4 sm:px-6 lg:px-8 py-12">
        <div class="text-center mb-6">
            <p class="text-[#F0A820] text-xs font-semibold tracking-[0.2em] uppercase mb-2">Concierge</p>
            <h1 class="text-2xl font-semibold text-neutral-900">We find it, verify it, deliver it.</h1>
            <p class="text-sm text-neutral-500 mt-1">Tell us what you need. Our team sources it, checks it, and brings it to you.</p>
        </div>

        @guest
            <div class="mb-5 bg-amber-50 border border-amber-200 rounded-lg px-4 py-3 text-sm text-amber-800 text-center">
                <a href="{{ route('login') }}" class="font-medium underline">Sign in</a> to start a concierge request.
            </div>
        @endguest

        <form method="POST" action="{{ route('concierge.store') }}" class="bg-white border border-neutral-200 rounded-xl shadow-sm p-6 space-y-4">
            @csrf

            <div class="space-y-1">
                <label class="block text-sm font-medium text-neutral-700">What do you need us to find? <span class="text-red-500">*</span></label>
                <textarea name="part_description" rows="3" required
                          class="block w-full border rounded-lg px-3 py-2.5 text-sm @error('part_description') border-red-500 @else border-neutral-300 @enderror"
                          placeholder="Describe the part — be as specific as you can.">{{ old('part_description') }}</textarea>
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

            <div class="space-y-1">
                <label class="block text-sm font-medium text-neutral-700">Your location <span class="text-red-500">*</span></label>
                <input name="location" required value="{{ old('location') }}" placeholder="City / suburb"
                       class="block w-full border rounded-lg px-3 py-2.5 text-sm @error('location') border-red-500 @else border-neutral-300 @enderror">
                @error('location') <p class="text-xs text-red-600">{{ $message }}</p> @enderror
            </div>

            <div class="space-y-1">
                <label class="block text-sm font-medium text-neutral-700">Anything else? <span class="text-neutral-400">(optional)</span></label>
                <textarea name="notes" rows="2" class="block w-full border border-neutral-300 rounded-lg px-3 py-2.5 text-sm">{{ old('notes') }}</textarea>
            </div>

            <button type="submit"
                    class="w-full bg-[#1A1A24] hover:bg-[#080810] text-white font-semibold py-2.5 rounded-lg text-sm transition-colors">
                Start concierge request
            </button>
            <p class="text-center text-xs text-neutral-400">No charge yet — we'll send you a quote first.</p>
        </form>
    </div>
</x-layouts.app>
