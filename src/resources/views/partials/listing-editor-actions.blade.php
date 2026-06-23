{{--
    H1: persistent listing-editor action bar — Save draft / Publish / Cancel.
    Both buttons submit the same form with a different `action` value; the server
    relaxes validation for drafts and only published listings enter review.

    Params: $cancelUrl (string)
--}}
<div class="sticky bottom-0 -mx-4 sm:-mx-6 lg:-mx-8 px-4 sm:px-6 lg:px-8 py-3 mt-4
            bg-white/95 backdrop-blur border-t border-neutral-200 flex flex-wrap items-center gap-3 z-10">
    <button type="submit" name="action" value="publish"
            class="bg-[#F0A820] hover:bg-[#F0A820]/90 text-[#1A1A24] font-semibold px-6 py-2.5 rounded-lg text-sm transition-colors">
        Publish
    </button>
    <button type="submit" name="action" value="draft"
            class="border border-neutral-300 hover:bg-neutral-50 text-neutral-700 font-medium px-6 py-2.5 rounded-lg text-sm transition-colors">
        Save as draft
    </button>
    <a href="{{ $cancelUrl }}" class="text-sm text-neutral-500 hover:text-neutral-700">Cancel</a>
    <span class="text-xs text-neutral-400 ml-auto hidden sm:block">Drafts stay private until you publish.</span>
</div>
