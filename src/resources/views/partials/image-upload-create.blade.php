{{--
    Create-time multi-image picker. Submitted as images[] with the create form
    (the listing doesn't exist yet, so files ride along with the POST and are
    processed in the controller's store()). Server-side validation + the secure
    upload pipeline (ImageUploadService) are the source of truth; this is just UX.

    Params:
    $max  - int|null  max images allowed at create (null = no client hint)
--}}
<div class="bg-white border border-neutral-200 rounded-xl shadow-sm p-6" x-data="imageCreatePicker()">
    <div class="flex items-center justify-between mb-3">
        <h2 class="text-base font-semibold text-neutral-800">Photos</h2>
        @isset($max)
            <span class="text-xs text-neutral-500"><span x-text="files.length">0</span> / {{ $max }}</span>
        @endisset
    </div>

    @error('images')
        <div class="mb-3 bg-red-50 border border-red-200 text-red-700 text-sm rounded-lg px-4 py-2">{{ $message }}</div>
    @enderror
    @error('images.*')
        <div class="mb-3 bg-red-50 border border-red-200 text-red-700 text-sm rounded-lg px-4 py-2">{{ $message }}</div>
    @enderror

    <label class="block">
        <div class="flex items-center gap-3 border border-dashed border-neutral-300 rounded-lg px-4 py-3 cursor-pointer hover:bg-neutral-50 transition-colors">
            <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5 text-neutral-400 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
                <path stroke-linecap="round" stroke-linejoin="round" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-8l-4-4m0 0L8 8m4-4v12" />
            </svg>
            <span class="text-sm text-neutral-600">Choose images (JPEG, PNG, WebP · max 10 MB each)</span>
        </div>
        <input type="file" name="images[]" accept="image/jpeg,image/png,image/webp" multiple class="sr-only"
               x-ref="input" x-on:change="preview($event)">
    </label>

    {{-- Client preview thumbnails --}}
    <div class="grid grid-cols-3 sm:grid-cols-4 gap-3 mt-4" x-show="files.length" x-cloak>
        <template x-for="(f, i) in files" :key="i">
            <div class="relative rounded-lg overflow-hidden border border-neutral-200 bg-neutral-50 aspect-video">
                <img :src="f" class="w-full h-full object-cover" alt="">
                <span x-show="i === 0" class="absolute top-1 left-1 bg-[#F0A820] text-[#1A1A24] text-xs font-semibold px-2 py-0.5 rounded">Cover</span>
            </div>
        </template>
    </div>
    <p class="mt-2 text-xs text-neutral-400">The first photo is used as the cover. You can add, reorder, or remove photos after saving.</p>

    <script>
        function imageCreatePicker() {
            return {
                files: [],
                preview(e) {
                    this.files = Array.from(e.target.files).map(file => URL.createObjectURL(file));
                },
            };
        }
    </script>
</div>
