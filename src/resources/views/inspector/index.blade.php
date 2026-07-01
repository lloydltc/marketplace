<x-layouts.app>
    <x-slot:title>Inspector portal</x-slot:title>
    <div class="max-w-3xl mx-auto px-4 sm:px-6 lg:px-8 py-10">
        <h1 class="text-h1 text-ink mb-1">Inspector portal</h1>
        <p class="text-body-sm text-muted mb-6">{{ $inspector->name }} · ★ {{ $inspector->review_count ? $inspector->rating : 'no ratings yet' }}</p>

        @if (session('status'))
            <div class="mb-5 bg-[rgb(var(--success)/0.12)] border border-[rgb(var(--success)/0.3)] text-[rgb(var(--success))] text-body-sm rounded-lg px-4 py-3">{{ session('status') }}</div>
        @endif

        @if ($inspections->isEmpty())
            <x-empty title="No assigned inspections" message="Paid bookings assigned to you appear here." />
        @else
            <div class="space-y-4">
                @foreach ($inspections as $inspection)
                    <x-card padding="lg">
                        <div class="flex items-center justify-between gap-3 mb-2">
                            <h2 class="text-h4 text-ink">{{ $inspection->vehicleLabel() }}</h2>
                            <span class="text-caption capitalize text-muted">{{ str_replace('_', ' ', $inspection->status) }}</span>
                        </div>

                        @if ($inspection->isCompleted())
                            <p class="text-body-sm text-muted">Report submitted — verdict: {{ config('inspection.verdicts.' . $inspection->verdict, $inspection->verdict) }}.</p>
                        @else
                            <form method="POST" action="{{ route('inspector.report', $inspection) }}" class="space-y-2 mt-3">
                                @csrf
                                @foreach (config('inspection.checklist', []) as $item)
                                    <div class="flex items-center justify-between gap-3 border-b border-line py-2">
                                        <span class="text-body-sm text-ink">{{ $item }}</span>
                                        <div class="flex items-center gap-2">
                                            <select name="items[{{ $item }}]" class="border border-line rounded-lg px-2 py-1 text-caption bg-surface">
                                                <option value="pass">Pass</option><option value="fail">Fail</option><option value="na">N/A</option>
                                            </select>
                                            <input type="text" name="notes[{{ $item }}]" placeholder="Note" class="w-40 border border-line rounded-lg px-2 py-1 text-caption bg-surface">
                                        </div>
                                    </div>
                                @endforeach
                                <div class="flex items-center justify-between pt-3">
                                    <select name="verdict" required class="border border-line rounded-lg px-3 py-2 text-body-sm bg-surface">
                                        <option value="">Overall verdict…</option>
                                        @foreach (config('inspection.verdicts', []) as $k => $label)<option value="{{ $k }}">{{ $label }}</option>@endforeach
                                    </select>
                                    <x-button type="submit" variant="primary">Submit report</x-button>
                                </div>
                            </form>
                        @endif
                    </x-card>
                @endforeach
            </div>
            <div class="mt-6">{{ $inspections->links() }}</div>
        @endif
    </div>
</x-layouts.app>
