{{--
    Parameters:
    $images        - collection of ProductImage or VehicleImage
    $uploadRoute   - URL (POST) for uploading a new image
    $deleteRoute   - named route string for deleting, e.g. 'vendor.vehicles.images.destroy'
    $deleteParams  - array of route params BEFORE the image, e.g. ['vehicle' => $vehicle]
    $imageLimit    - int|null  (null = unlimited)
    $hasViewType   - bool, show view_type selector for vehicles
--}}
@php
    $remaining = $imageLimit !== null ? max(0, $imageLimit - $images->count()) : null;
@endphp

<div class="bg-white border border-neutral-200 rounded-xl shadow-sm p-6">
    <div class="flex items-center justify-between mb-4">
        <h2 class="text-base font-semibold text-neutral-800">Photos</h2>
        @if ($imageLimit !== null)
            <span class="text-xs text-neutral-500">{{ $images->count() }} / {{ $imageLimit }}</span>
        @else
            <span class="text-xs text-neutral-500">{{ $images->count() }} uploaded</span>
        @endif
    </div>

    @error('image')
        <div class="mb-4 bg-red-50 border border-red-200 text-red-700 text-sm rounded-lg px-4 py-3">
            {{ $message }}
        </div>
    @enderror

    @if ($images->isNotEmpty())
        <div class="grid grid-cols-2 sm:grid-cols-3 gap-3 mb-5">
            @foreach ($images as $img)
                <div class="relative group rounded-lg overflow-hidden border border-neutral-200 bg-neutral-50 aspect-video">
                    @if ($img->isProcessed())
                        <img src="{{ $img->thumbUrl() }}" alt="Vehicle photo"
                             loading="lazy" decoding="async"
                             class="w-full h-full object-cover">
                    @else
                        <div class="w-full h-full flex flex-col items-center justify-center text-neutral-400 text-xs gap-1">
                            <svg xmlns="http://www.w3.org/2000/svg" class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z" />
                            </svg>
                            <span>Processing…</span>
                        </div>
                    @endif

                    @if ($loop->first)
                        <span class="absolute top-1 left-1 bg-[#F0A820] text-[#1A1A24] text-xs font-semibold px-2 py-0.5 rounded">Cover</span>
                    @endif

                    @if (isset($img->view_type) && $img->view_type)
                        <span class="absolute bottom-1 left-1 bg-black/50 text-white text-xs px-1.5 py-0.5 rounded capitalize">{{ $img->view_type }}</span>
                    @endif

                    <form method="POST"
                          action="{{ route($deleteRoute, array_merge($deleteParams, ['image' => $img])) }}"
                          class="absolute top-1 right-1 opacity-0 group-hover:opacity-100 transition-opacity"
                          onsubmit="return confirm('Remove this photo?')">
                        @csrf
                        @method('DELETE')
                        <button type="submit"
                                class="bg-red-600 hover:bg-red-700 text-white w-6 h-6 rounded flex items-center justify-center shadow">
                            <svg xmlns="http://www.w3.org/2000/svg" class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
                            </svg>
                        </button>
                    </form>
                </div>
            @endforeach
        </div>
    @else
        <div class="mb-5 text-sm text-neutral-400 text-center py-6 border border-dashed border-neutral-200 rounded-lg">
            No photos yet. Upload at least one photo.
        </div>
    @endif

    @if ($remaining === null || $remaining > 0)
        <form method="POST" action="{{ $uploadRoute }}" enctype="multipart/form-data" class="space-y-3">
            @csrf

            @if (!empty($hasViewType))
                <div>
                    <label for="view_type" class="block text-xs font-medium text-neutral-600 mb-1">Photo angle</label>
                    <select id="view_type" name="view_type"
                            class="w-full border border-neutral-200 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-[#F0A820]/40">
                        <option value="">— any —</option>
                        <option value="front">Front</option>
                        <option value="side">Side</option>
                        <option value="back">Back</option>
                        <option value="interior">Interior</option>
                        <option value="other">Other</option>
                    </select>
                </div>
            @endif

            <div class="flex items-center gap-3">
                <label class="flex-1">
                    <div class="flex items-center gap-3 border border-neutral-200 rounded-lg px-3 py-2 cursor-pointer hover:bg-neutral-50 transition-colors">
                        <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5 text-neutral-400 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-8l-4-4m0 0L8 8m4-4v12" />
                        </svg>
                        <span class="text-sm text-neutral-600">Choose image (JPEG, PNG, WebP · max 10 MB)</span>
                    </div>
                    <input type="file" name="image" accept="image/jpeg,image/png,image/webp" class="sr-only" required
                           onchange="this.closest('label').querySelector('span').textContent = this.files[0]?.name ?? 'Choose image'">
                </label>
                <button type="submit"
                        class="shrink-0 bg-[#F0A820] hover:bg-[#F0A820]/90 text-[#1A1A24] font-semibold px-4 py-2 rounded-lg text-sm transition-colors">
                    Upload
                </button>
            </div>
        </form>
    @else
        <p class="text-xs text-amber-600 bg-amber-50 border border-amber-200 rounded-lg px-3 py-2">
            Image limit reached ({{ $imageLimit }}). Upgrade to Premium to upload more photos.
        </p>
    @endif
</div>
