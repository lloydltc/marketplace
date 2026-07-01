<x-layouts.app>
    <x-slot:title>Inspection — {{ $inspection->vehicleLabel() }}</x-slot:title>
    <div class="max-w-3xl mx-auto px-4 sm:px-6 lg:px-8 py-10">
        <div class="flex items-start justify-between gap-4 mb-6 flex-wrap print:mb-2">
            <div>
                <h1 class="text-h1 text-ink">Inspection</h1>
                <p class="text-body-sm text-muted mt-1">{{ $inspection->vehicleLabel() }} · {{ $inspection->inspector?->name }} · Status: {{ ucfirst(str_replace('_', ' ', $inspection->status)) }}</p>
            </div>
            <div class="flex items-center gap-3 print:hidden">
                <a href="{{ route('inspections.index') }}" class="text-body-sm text-muted hover:text-ink">My inspections</a>
                @if ($inspection->isCompleted())<button type="button" onclick="window.print()" class="text-body-sm text-[rgb(var(--info))] hover:underline">Download / print</button>@endif
            </div>
        </div>

        @if (session('status'))
            <div class="mb-5 bg-[rgb(var(--success)/0.12)] border border-[rgb(var(--success)/0.3)] text-[rgb(var(--success))] text-body-sm rounded-lg px-4 py-3 print:hidden">{{ session('status') }}</div>
        @endif

        @if ($inspection->isCompleted())
            <x-card padding="lg" class="mb-6">
                <div class="flex items-center justify-between mb-3">
                    <h2 class="text-h4 text-ink">Report</h2>
                    @php $vcol = ['pass' => 'success', 'pass_with_advisories' => 'warning', 'fail' => 'danger'][$inspection->verdict] ?? 'info'; @endphp
                    <span class="text-caption font-semibold px-2 py-0.5 rounded-full bg-[rgb(var(--{{ $vcol }})/0.15)] text-[rgb(var(--{{ $vcol }}))]">{{ config('inspection.verdicts.' . $inspection->verdict, ucfirst((string) $inspection->verdict)) }}</span>
                </div>
                <dl class="divide-y divide-line text-body-sm">
                    @foreach (($inspection->report['checklist'] ?? []) as $row)
                        <div class="flex items-start justify-between gap-3 py-2">
                            <dt class="text-[rgb(var(--text))]">{{ $row['item'] }}@if (! empty($row['note']))<span class="block text-caption text-muted">{{ $row['note'] }}</span>@endif</dt>
                            <dd class="font-medium uppercase text-caption {{ $row['status'] === 'pass' ? 'text-[rgb(var(--success))]' : ($row['status'] === 'fail' ? 'text-[rgb(var(--danger))]' : 'text-muted') }}">{{ $row['status'] }}</dd>
                        </div>
                    @endforeach
                </dl>
            </x-card>

            <x-card padding="lg" class="print:hidden">
                <h2 class="text-h4 text-ink mb-2">Rate your inspector</h2>
                @if ($inspection->rating_given)
                    <p class="text-body-sm text-muted">You rated {{ $inspection->rating_given }}/5. Thank you.</p>
                @else
                    <form method="POST" action="{{ route('inspections.rate', $inspection) }}" class="flex items-center gap-2">
                        @csrf
                        <select name="rating" class="border border-line rounded-lg px-3 py-2 text-body-sm bg-surface">
                            @for ($i = 5; $i >= 1; $i--)<option value="{{ $i }}">{{ $i }} ★</option>@endfor
                        </select>
                        <x-button type="submit" variant="primary" size="sm">Submit rating</x-button>
                    </form>
                @endif
            </x-card>
        @else
            <x-card padding="lg">
                <p class="text-body-sm text-[rgb(var(--text))]">
                    @if ($inspection->status === 'cancelled') This inspection was cancelled.
                    @elseif ($inspection->isPaid()) Paid — your inspector will complete the report soon.
                    @else Awaiting payment. @endif
                </p>
                @unless (in_array($inspection->status, ['completed', 'cancelled'], true))
                    <form method="POST" action="{{ route('inspections.cancel', $inspection) }}" class="mt-4" onsubmit="return confirm('Cancel this inspection?')">
                        @csrf <button type="submit" class="text-body-sm text-[rgb(var(--danger))] hover:underline">Cancel inspection</button>
                    </form>
                @endunless
            </x-card>
        @endif
    </div>
</x-layouts.app>
