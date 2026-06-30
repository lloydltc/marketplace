@props([
    'items',                 // Collection of things being compared
    'rows',                  // array<[string $label, Closure(item): string]>
    'title',                 // Closure(item): string  — column heading text
    'href' => null,          // Closure(item): string  — link the heading to the listing
    'image' => null,         // Closure(item): mixed   — cover model for x-listing-thumbnail
    'type' => 'vehicle',     // thumbnail type
    'removeAction' => null,  // Closure(item): string  — DELETE url to drop a column
])

{{-- AC3: reusable side-by-side comparison table — frozen attribute column +
     horizontal scroll (mobile), diff highlighting, print-friendly. --}}
<div class="overflow-x-auto bg-surface border border-line rounded-xl shadow-e1 print:border-0 print:shadow-none print:overflow-visible">
    <table class="w-full text-body-sm border-collapse">
        <thead>
            <tr class="border-b border-line">
                <th class="sticky left-0 z-10 bg-surface px-5 py-4 text-left text-overline uppercase text-muted w-36">Listing</th>
                @foreach ($items as $item)
                    <th class="px-5 py-4 text-left align-top min-w-[200px]">
                        @if ($href)
                            <a href="{{ $href($item) }}" class="block group">
                                @if ($image)
                                    <div class="aspect-video bg-surface-2 rounded-lg overflow-hidden mb-2 grid place-items-center print:hidden">
                                        <x-listing-thumbnail :cover="$image($item)" :alt="$title($item)" :type="$type" />
                                    </div>
                                @endif
                                <span class="font-semibold text-ink group-hover:text-brand transition-colors">{{ $title($item) }}</span>
                            </a>
                        @else
                            <span class="font-semibold text-ink">{{ $title($item) }}</span>
                        @endif
                        @if ($removeAction)
                            <form method="POST" action="{{ $removeAction($item) }}" class="mt-2 print:hidden">
                                @csrf @method('DELETE')
                                <button type="submit" class="text-caption text-muted hover:text-[rgb(var(--danger))] transition-colors">Remove</button>
                            </form>
                        @endif
                    </th>
                @endforeach
            </tr>
        </thead>
        <tbody>
            @foreach ($rows as [$label, $resolver])
                @php
                    $values = $items->map($resolver)->all();
                    $differs = count(array_unique($values)) > 1;
                @endphp
                <tr class="border-t border-line">
                    <td class="sticky left-0 z-10 bg-surface px-5 py-3 text-overline uppercase text-muted">{{ $label }}</td>
                    @foreach ($values as $value)
                        <td class="px-5 py-3 tabular-nums {{ $differs ? 'bg-[rgb(var(--brand)/0.08)] font-medium text-ink' : 'text-[rgb(var(--text))]' }}">{{ $value }}</td>
                    @endforeach
                </tr>
            @endforeach
        </tbody>
    </table>
</div>
