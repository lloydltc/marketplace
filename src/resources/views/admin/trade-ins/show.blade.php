<x-layouts.app>
    <x-slot:title>Trade-in — {{ $tradeIn->title() }}</x-slot:title>
    <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
      <div class="lg:flex lg:gap-8">
        <x-admin-sidebar />
        <div class="flex-1 min-w-0 mt-6 lg:mt-0">
            <a href="{{ route('admin.trade-ins.index') }}" class="text-body-sm text-muted hover:text-ink">← Trade-ins</a>
            <h1 class="text-h1 text-ink mt-2 mb-1">{{ $tradeIn->title() }}</h1>
            <p class="text-body-sm text-muted mb-6">{{ number_format($tradeIn->mileage) }} km · {{ ucfirst($tradeIn->condition) }} · {{ $tradeIn->user?->name }} · Status: {{ ucfirst($tradeIn->status) }}</p>

            <x-card padding="lg" class="mb-6">
                <h2 class="text-h4 text-ink mb-2">Estimate</h2>
                <p class="text-body">{{ $tradeIn->estimateRange() ?? 'No estimate (insufficient comparables).' }}
                    <span class="text-caption text-muted">({{ $tradeIn->comparables_count }} comparables)</span></p>
                @if ($tradeIn->notes)<p class="text-body-sm text-muted mt-2">{{ $tradeIn->notes }}</p>@endif
            </x-card>

            <x-card padding="lg">
                <h2 class="text-h4 text-ink mb-3">Dealer offers ({{ $tradeIn->offers->count() }})</h2>
                @if ($tradeIn->offers->isEmpty())
                    <p class="text-body-sm text-muted">No offers yet.</p>
                @else
                    <table class="w-full text-body-sm">
                        <tbody class="[&_tr]:border-t [&_tr]:border-line [&_td]:py-2">
                            @foreach ($tradeIn->offers as $o)
                                <tr>
                                    <td class="font-medium text-ink">{{ $o->vendor?->name }}</td>
                                    <td class="tabular-nums">{{ $o->amount() }}</td>
                                    <td class="text-muted capitalize text-right">{{ $o->status }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                @endif
            </x-card>

            @if ($tradeIn->photos->isNotEmpty())
                <div class="grid grid-cols-3 sm:grid-cols-4 gap-3 mt-6">
                    @foreach ($tradeIn->photos as $p)
                        <img src="{{ $p->url() }}" alt="Trade-in photo" class="aspect-square object-cover rounded-lg border border-line">
                    @endforeach
                </div>
            @endif
        </div>
      </div>
    </div>
</x-layouts.app>
