<x-layouts.app>
    <x-slot:title>Moderation</x-slot:title>

    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
      <div class="lg:flex lg:gap-8">
        <x-admin-sidebar />
        <div class="flex-1 min-w-0 mt-6 lg:mt-0">

        @if (session('status'))
            <div class="mb-5 bg-green-50 border border-green-200 text-green-700 text-sm rounded-lg px-4 py-3">{{ session('status') }}</div>
        @endif

        <div class="mb-6">
            <h1 class="text-2xl font-semibold text-neutral-900">Moderation queue</h1>
            <p class="text-sm text-neutral-500 mt-1">{{ $openCount }} open {{ Str::plural('report', $openCount) }} awaiting review.</p>
        </div>

        {{-- Status filter --}}
        <div class="flex gap-2 mb-5">
            @foreach (['open' => 'Open', 'actioned' => 'Actioned', 'dismissed' => 'Dismissed'] as $key => $label)
                <a href="{{ route('admin.moderation.index', ['status' => $key]) }}"
                   class="px-3 py-1.5 rounded-full text-sm font-medium transition-colors {{ $status === $key ? 'bg-[#1A1A24] text-white' : 'bg-white border border-neutral-200 text-neutral-600 hover:border-neutral-400' }}">
                    {{ $label }}
                </a>
            @endforeach
        </div>

        @if ($reports->isEmpty())
            <div class="bg-white border border-neutral-200 rounded-xl py-16 text-center text-sm text-neutral-500">
                No {{ $status }} reports.
            </div>
        @else
            <div class="space-y-3">
                @foreach ($reports as $report)
                    @php
                        $listing = $report->reportable;
                        $isVehicle = $listing instanceof \App\Modules\Vehicles\Models\Vehicle;
                        $title = $listing === null ? '(listing removed)' : ($isVehicle ? $listing->displayTitle() : $listing->title);
                        $url = $listing === null ? null : ($isVehicle ? route('vehicles.show', $listing) : route('products.show', $listing));
                    @endphp
                    <div class="bg-white border border-neutral-200 rounded-xl shadow-sm p-5">
                        <div class="flex items-start justify-between gap-4 flex-wrap">
                            <div class="min-w-0">
                                <div class="flex items-center gap-2 flex-wrap">
                                    @if ($url)
                                        <a href="{{ $url }}" class="font-semibold text-neutral-900 hover:text-[#F0A820]">{{ $title }}</a>
                                    @else
                                        <span class="font-semibold text-neutral-500">{{ $title }}</span>
                                    @endif
                                    <span class="text-xs px-2 py-0.5 rounded-full {{ $isVehicle ? 'bg-blue-50 text-blue-600' : 'bg-purple-50 text-purple-600' }}">{{ $isVehicle ? 'Vehicle' : 'Part' }}</span>
                                    <span class="text-xs px-2 py-0.5 rounded-full {{ $report->isAuto() ? 'bg-amber-100 text-amber-700' : 'bg-neutral-100 text-neutral-600' }}">{{ $report->isAuto() ? '⚙ Auto' : '👤 User' }}</span>
                                </div>
                                <p class="text-sm font-medium text-neutral-800 mt-1">{{ $report->reasonLabel() }}</p>
                                @if ($report->note)
                                    <p class="text-sm text-neutral-500 mt-0.5">{{ $report->note }}</p>
                                @endif
                                <p class="text-xs text-neutral-400 mt-1">
                                    Reported {{ $report->created_at->diffForHumans() }}
                                    @if ($report->reporter) by {{ $report->reporter->name }} @endif
                                    @if ($report->status !== 'open') · {{ ucfirst($report->status) }} {{ $report->resolved_at?->diffForHumans() }} @endif
                                </p>
                            </div>

                            @if ($report->isOpen())
                                <div class="flex items-center gap-2 shrink-0">
                                    <form method="POST" action="{{ route('admin.moderation.resolve', $report) }}">
                                        @csrf
                                        <input type="hidden" name="action" value="dismiss">
                                        <button type="submit" class="border border-neutral-300 text-neutral-600 hover:bg-neutral-50 font-medium px-3 py-1.5 rounded-lg text-sm transition-colors">Dismiss</button>
                                    </form>
                                    <form method="POST" action="{{ route('admin.moderation.resolve', $report) }}"
                                          onsubmit="return confirm('Take this listing down? It will be hidden from buyers.')">
                                        @csrf
                                        <input type="hidden" name="action" value="takedown">
                                        <button type="submit" class="bg-red-600 hover:bg-red-700 text-white font-semibold px-3 py-1.5 rounded-lg text-sm transition-colors">Take down</button>
                                    </form>
                                </div>
                            @endif
                        </div>
                    </div>
                @endforeach
            </div>

            <div class="mt-6">{{ $reports->links() }}</div>
        @endif

        </div>
      </div>
    </div>
</x-layouts.app>
