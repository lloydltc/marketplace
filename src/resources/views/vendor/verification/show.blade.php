<x-layouts.app>
    <x-slot:title>Verification</x-slot:title>

    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
      <div class="lg:flex lg:gap-8">
        <x-vendor-sidebar />
        <div class="flex-1 min-w-0 mt-6 lg:mt-0 max-w-3xl">

            <div class="mb-6">
                <h1 class="text-h1 text-ink">Verification &amp; badges</h1>
                <p class="text-body-sm text-muted mt-1">Build trust with buyers — verify each area to earn a higher badge tier.</p>
            </div>

            {{-- Current badge --}}
            <x-card padding="lg" class="mb-6">
                <div class="flex items-center justify-between gap-3 flex-wrap">
                    <div>
                        <div class="text-overline uppercase text-muted mb-1">Current badge</div>
                        @if ($vendor->verification_tier)
                            <x-trust-badge :vendor="$vendor" />
                        @else
                            <span class="text-body-sm text-muted">No trust badge yet</span>
                        @endif
                        @if ($vendor->isBadgeRevoked())
                            <span class="ml-2 text-caption font-semibold bg-[rgb(var(--danger)/0.15)] text-[rgb(var(--danger))] px-2 py-0.5 rounded-full">Revoked</span>
                        @endif
                    </div>
                    <div class="text-right">
                        <div class="text-overline uppercase text-muted mb-1">Reputation</div>
                        <div class="text-h3 font-bold text-ink tabular-nums">{{ $vendor->reputation_score }}<span class="text-body-sm text-muted">/100</span></div>
                    </div>
                </div>
            </x-card>

            {{-- Dimensions --}}
            <x-card padding="lg" class="mb-6">
                <h2 class="text-h4 text-ink mb-3">Verification areas</h2>
                <ul class="divide-y divide-line">
                    @foreach ($progress['dimensions'] as $dimension => $status)
                        @php
                            [$cls, $glyph, $text] = match ($status) {
                                'approved' => ['text-[rgb(var(--success))]', '✓', 'Verified'],
                                'pending'  => ['text-[rgb(var(--warning))]', '◷', 'Pending review'],
                                'rejected' => ['text-[rgb(var(--danger))]', '✕', 'Rejected'],
                                default    => ['text-muted', '○', 'Not submitted'],
                            };
                        @endphp
                        <li class="flex items-center justify-between py-3">
                            <span class="text-body-sm text-ink capitalize">{{ str_replace('_', ' ', $dimension) }}</span>
                            <span class="inline-flex items-center gap-1.5 text-body-sm font-medium {{ $cls }}">
                                <span aria-hidden="true">{{ $glyph }}</span> {{ $text }}
                            </span>
                        </li>
                    @endforeach
                </ul>
                <p class="text-caption text-muted mt-3">Submit evidence via your documents and bank details; our team reviews each area.</p>
            </x-card>

            {{-- Next tier --}}
            @if ($progress['next'])
                <x-card padding="lg">
                    <h2 class="text-h4 text-ink mb-1">Next: {{ $progress['next']['label'] }}</h2>
                    @if (empty($progress['next']['missing_dimensions']) && ! $progress['next']['needs_reputation'])
                        <p class="text-body-sm text-[rgb(var(--success))]">You meet the requirements — your badge updates automatically.</p>
                    @else
                        <p class="text-body-sm text-muted mb-2">To earn this badge:</p>
                        <ul class="list-disc list-inside text-body-sm text-ink space-y-1">
                            @foreach ($progress['next']['missing_dimensions'] as $dim)
                                <li>Verify <span class="capitalize">{{ str_replace('_', ' ', $dim) }}</span></li>
                            @endforeach
                            @if ($progress['next']['needs_reputation'])
                                <li>Reach a reputation of {{ $progress['next']['needs_reputation'] }} (currently {{ $vendor->reputation_score }})</li>
                            @endif
                        </ul>
                    @endif
                </x-card>
            @else
                <x-card padding="lg">
                    <p class="text-body-sm text-[rgb(var(--success))]">You've reached the highest automatic tier. 🎉</p>
                </x-card>
            @endif
        </div>
      </div>
    </div>
</x-layouts.app>
