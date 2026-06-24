@props([
    'vehicles',      // listings needing attention (expired + expiring soon)
    'renewRoute',    // route name for the renew action, e.g. 'seller.vehicles.renew'
])

@if ($vehicles->isNotEmpty())
    <div class="bg-amber-50 border border-amber-200 rounded-xl p-5 mb-6">
        <div class="flex items-start gap-3">
            <span class="text-xl leading-none">⏳</span>
            <div class="flex-1 min-w-0">
                <h3 class="text-sm font-semibold text-amber-900">
                    {{ $vehicles->count() }} {{ Str::plural('listing', $vehicles->count()) }} need your attention
                </h3>
                <p class="text-xs text-amber-700 mt-0.5">Renew to keep them visible to buyers — it’s free.</p>

                <ul class="mt-3 space-y-2">
                    @foreach ($vehicles as $vehicle)
                        <li class="flex items-center justify-between gap-3 bg-white border border-amber-100 rounded-lg px-3 py-2">
                            <div class="min-w-0">
                                <span class="text-sm font-medium text-neutral-900 truncate">{{ $vehicle->displayTitle() }}</span>
                                <span class="ml-2 text-xs font-semibold {{ $vehicle->isExpired() ? 'text-red-600' : 'text-amber-700' }}">
                                    {{ $vehicle->expiryCountdownLabel() }}
                                </span>
                            </div>
                            <form method="POST" action="{{ route($renewRoute, $vehicle) }}">
                                @csrf
                                <button type="submit"
                                        class="shrink-0 text-xs font-semibold bg-[#F0A820] hover:bg-[#F0A820]/90 text-[#1A1A24] px-3 py-1.5 rounded-lg transition-colors">
                                    Renew
                                </button>
                            </form>
                        </li>
                    @endforeach
                </ul>
            </div>
        </div>
    </div>
@endif
