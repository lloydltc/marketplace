<x-layouts.app>
    <x-slot:title>{{ $heading }}</x-slot:title>

    <div class="max-w-6xl mx-auto px-4 sm:px-6 lg:px-8 py-10">
        <h1 class="text-2xl font-semibold text-neutral-900 mb-1">{{ $heading }}</h1>
        <p class="text-sm text-neutral-500 mb-6">Buyers who contacted you, and where each enquiry stands.</p>

        @if (session('status'))
            <div class="mb-4 bg-green-50 border border-green-200 text-green-700 text-sm rounded-lg px-4 py-2">{{ session('status') }}</div>
        @endif

        @isset($funnel)
            <div class="grid grid-cols-3 gap-4 mb-8">
                <div class="bg-white border border-neutral-200 rounded-xl p-5 shadow-sm">
                    <p class="text-sm font-medium text-neutral-500">Total leads</p>
                    <p class="mt-2 text-3xl font-semibold text-neutral-900 tabular-nums">{{ number_format($funnel['total']) }}</p>
                </div>
                <div class="bg-white border border-neutral-200 rounded-xl p-5 shadow-sm">
                    <p class="text-sm font-medium text-neutral-500">Contacted</p>
                    <p class="mt-2 text-3xl font-semibold text-neutral-900 tabular-nums">{{ number_format($funnel['contacted']) }}</p>
                </div>
                <div class="bg-white border border-neutral-200 rounded-xl p-5 shadow-sm">
                    <p class="text-sm font-medium text-neutral-500">Converted</p>
                    <p class="mt-2 text-3xl font-semibold text-[#2EBD7A] tabular-nums">{{ number_format($funnel['converted']) }}</p>
                </div>
            </div>
        @endisset

        <div class="bg-white border border-neutral-200 rounded-xl shadow-sm divide-y divide-neutral-100">
            @forelse ($leads as $lead)
                @php
                    $subjectLabel = match (true) {
                        str_contains((string) $lead->subject_type, 'Vehicle') => $lead->subject?->displayTitle() ?? 'Vehicle',
                        str_contains((string) $lead->subject_type, 'Product') => $lead->subject?->title ?? 'Product',
                        default => ucfirst(str_replace('_', ' ', $lead->type)),
                    };
                @endphp
                <div class="px-5 py-4">
                    <div class="flex flex-wrap items-start justify-between gap-3">
                        <div class="min-w-0">
                            <p class="text-sm font-medium text-neutral-900">
                                {{ $subjectLabel }}
                                <span class="ml-1 text-xs px-2 py-0.5 rounded-full bg-neutral-100 text-neutral-600">{{ str_replace('_', ' ', $lead->type) }}</span>
                            </p>
                            <p class="text-xs text-neutral-500 mt-1">
                                {{ $lead->buyer?->name ?? $lead->contact_name ?? 'Guest' }}
                                @if ($lead->contact_phone) · {{ $lead->contact_phone }}@endif
                                @if ($lead->contact_email) · {{ $lead->contact_email }}@endif
                                · {{ $lead->created_at->diffForHumans() }}
                            </p>
                            @if ($lead->message)
                                <p class="text-sm text-neutral-700 mt-1 line-clamp-2">{{ $lead->message }}</p>
                            @endif
                            @if ($lead->notes)
                                <p class="text-xs text-neutral-400 mt-1">Note: {{ $lead->notes }}</p>
                            @endif
                        </div>
                        <form method="POST" action="{{ route($updateRoute, $lead) }}" class="flex items-center gap-2 shrink-0">
                            @csrf
                            @method('PUT')
                            <input type="hidden" name="notes" value="{{ $lead->notes }}">
                            <select name="status" onchange="this.form.submit()"
                                    class="text-xs border border-neutral-200 rounded-lg px-2 py-1 focus:outline-none focus:ring-2 focus:ring-[#F0A820]/40">
                                @foreach (\App\Modules\Leads\Models\Lead::STATUSES as $s)
                                    <option value="{{ $s }}" @selected($lead->status === $s)>{{ ucfirst($s) }}</option>
                                @endforeach
                            </select>
                        </form>
                    </div>
                </div>
            @empty
                <div class="px-5 py-16 text-center">
                    <h3 class="text-base font-semibold text-neutral-700">No leads yet</h3>
                    <p class="mt-1 text-sm text-neutral-500">When buyers contact you about a listing, they'll appear here.</p>
                </div>
            @endforelse
        </div>

        <div class="mt-6">{{ $leads->links() }}</div>
    </div>
</x-layouts.app>
