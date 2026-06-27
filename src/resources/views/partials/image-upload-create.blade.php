{{--
    Create-time multi-image picker. Submitted as images[] with the create form.
    Server-side validation + the secure upload pipeline are the source of truth.
    Params: $max - int|null  max images allowed at create.
--}}
<x-card padding="lg" x-data="imageCreatePicker()">
    <div class="flex items-center justify-between mb-3">
        <h2 class="text-h4 text-ink">Photos</h2>
        @isset($max)
            <span class="text-caption text-muted"><span x-text="files.length">0</span> / {{ $max }}</span>
        @endisset
    </div>

    @error('images')<div class="mb-3 bg-[rgb(var(--danger)/0.12)] border border-[rgb(var(--danger)/0.3)] text-[rgb(var(--danger))] text-body-sm rounded-lg px-4 py-2">{{ $message }}</div>@enderror
    @error('images.*')<div class="mb-3 bg-[rgb(var(--danger)/0.12)] border border-[rgb(var(--danger)/0.3)] text-[rgb(var(--danger))] text-body-sm rounded-lg px-4 py-2">{{ $message }}</div>@enderror

    <label class="block">
        <div class="flex items-center gap-3 border border-dashed border-strong rounded-lg px-4 py-3 cursor-pointer hover:bg-surface-2 transition-colors">
            <svg class="size-5 text-muted shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5" aria-hidden="true">
                <path stroke-linecap="round" stroke-linejoin="round" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-8l-4-4m0 0L8 8m4-4v12" />
            </svg>
            <span class="text-body-sm text-[rgb(var(--text))]">Choose images (JPEG, PNG, WebP · max 10 MB each)</span>
        </div>
        <input type="file" name="images[]" accept="image/jpeg,image/png,image/webp" multiple class="sr-only" x-ref="input" x-on:change="preview($event)">
    </label>

    <div class="grid grid-cols-3 sm:grid-cols-4 gap-3 mt-4" x-show="files.length" x-cloak>
        <template x-for="(f, i) in files" :key="i">
            <div class="relative rounded-lg overflow-hidden border border-line bg-surface-2 aspect-video">
                <img :src="f" class="w-full h-full object-cover" alt="">
                <span x-show="i === 0" class="absolute top-1 left-1 bg-brand text-on-brand text-caption font-semibold px-2 py-0.5 rounded">Cover</span>
            </div>
        </template>
    </div>
    <p class="mt-2 text-caption text-muted">The first photo is used as the cover. You can add, reorder, or remove photos after saving.</p>

    @once
        <script>
            function imageCreatePicker() {
                return {
                    files: [],
                    preview(e) { this.files = Array.from(e.target.files).map((file) => URL.createObjectURL(file)); },
                };
            }
        </script>
    @endonce
</x-card>
