@props(['vehicle'])

@php
    $soonDays = app(\App\Modules\Settings\Services\SettingsService::class)->getInt('listings.expiry_soon_days', 7);
@endphp

@if ($vehicle->isExpiringSoon($soonDays))
    <span {{ $attributes->merge(['class' => 'inline-flex items-center gap-1 text-xs font-semibold bg-amber-100 text-amber-700 px-2 py-0.5 rounded-full']) }}>
        ⏳ {{ $vehicle->expiryCountdownLabel() }}
    </span>
@endif
