{{--
    Listing limit banner.
    Props:
      $remaining  — int|null  (null = unlimited)
      $limit      — int|null
      $type       — 'vehicle'|'product'
      $upgradeMsg — string (optional custom message)
--}}
@php
    $type       ??= 'listing';
    $upgradeMsg ??= 'Contact support to upgrade to Premium for unlimited listings.';
@endphp

@if ($remaining !== null)
    @if ($remaining === 0)
        <div class="mb-5 flex items-start gap-3 bg-red-50 border border-red-200 rounded-xl px-4 py-3 text-sm text-red-700">
            <span class="text-lg leading-none mt-0.5">⚠</span>
            <div>
                <strong>Listing limit reached.</strong>
                You've used all {{ $limit }} {{ $type }} listing slots on your current tier.
                {{ $upgradeMsg }}
            </div>
        </div>
    @elseif ($remaining <= 2)
        <div class="mb-5 flex items-start gap-3 bg-amber-50 border border-amber-200 rounded-xl px-4 py-3 text-sm text-amber-700">
            <span class="text-lg leading-none mt-0.5">!</span>
            <div>
                <strong>Almost at your limit.</strong>
                You have <strong>{{ $remaining }}</strong> {{ $type }} slot(s) remaining out of {{ $limit }}.
                {{ $upgradeMsg }}
            </div>
        </div>
    @else
        <div class="mb-5 flex items-center gap-3 bg-neutral-50 border border-neutral-200 rounded-xl px-4 py-3 text-sm text-neutral-600">
            <span>{{ $remaining }} of {{ $limit }} {{ $type }} slot(s) remaining.</span>
        </div>
    @endif
@endif
