@props([
    'action',   // the report POST route
])

<div x-data="{ open: false }" class="inline-block">
    <button type="button" @click="open = true"
            class="text-xs text-neutral-400 hover:text-red-500 transition-colors">
        ⚑ Report this listing
    </button>

    {{-- Modal --}}
    <div x-show="open" x-cloak @keydown.escape.window="open = false"
         class="fixed inset-0 z-50 flex items-center justify-center p-4">
        <div class="absolute inset-0 bg-black/50" @click="open = false"></div>
        <div class="relative bg-white rounded-xl shadow-xl max-w-md w-full p-6">
            <h3 class="text-lg font-semibold text-neutral-900 mb-1">Report this listing</h3>
            <p class="text-sm text-neutral-500 mb-4">Tell us what's wrong. Our team reviews every report.</p>

            <form method="POST" action="{{ $action }}" class="space-y-3">
                @csrf
                <div>
                    <label class="block text-sm font-medium text-neutral-700 mb-1">Reason</label>
                    <select name="reason" required
                            class="w-full border border-neutral-200 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-[#F0A820]/40">
                        @foreach (config('moderation.reasons', []) as $key => $label)
                            <option value="{{ $key }}">{{ $label }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="block text-sm font-medium text-neutral-700 mb-1">Details (optional)</label>
                    <textarea name="note" rows="3" maxlength="1000"
                              class="w-full border border-neutral-200 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-[#F0A820]/40"
                              placeholder="Anything that helps us review faster…"></textarea>
                </div>
                <div class="flex items-center justify-end gap-3 pt-1">
                    <button type="button" @click="open = false" class="text-sm text-neutral-500 hover:text-neutral-700">Cancel</button>
                    <button type="submit" class="bg-red-600 hover:bg-red-700 text-white font-semibold px-4 py-2 rounded-lg text-sm transition-colors">Submit report</button>
                </div>
            </form>
        </div>
    </div>
</div>
