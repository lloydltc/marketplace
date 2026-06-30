<x-layouts.app>
    <x-slot:title>Notifications</x-slot:title>

    <div class="max-w-3xl mx-auto px-4 sm:px-6 lg:px-8 py-10">
        <div class="flex items-center justify-between mb-6">
            <h1 class="text-h1 text-ink">Notifications</h1>
            <div class="flex items-center gap-3">
                <a href="{{ route('notifications.preferences') }}" class="text-body-sm text-[rgb(var(--info))] hover:underline">Preferences</a>
                @if ($notifications->total() > 0)
                    <form method="POST" action="{{ route('notifications.read-all') }}">
                        @csrf
                        <button type="submit" class="text-body-sm text-muted hover:text-ink">Mark all read</button>
                    </form>
                @endif
            </div>
        </div>

        @if (session('status'))
            <div class="mb-5 bg-[rgb(var(--success)/0.12)] border border-[rgb(var(--success)/0.3)] text-[rgb(var(--success))] text-body-sm rounded-lg px-4 py-3">{{ session('status') }}</div>
        @endif

        @if ($notifications->isEmpty())
            <x-empty title="No notifications" message="Alerts about your saved searches, listings and account will show up here." />
        @else
            <div class="bg-surface border border-line rounded-xl shadow-e1 divide-y divide-line">
                @foreach ($notifications as $n)
                    @php $d = $n->data; @endphp
                    <a href="{{ route('notifications.read', $n->id) }}"
                       class="flex items-start gap-3 px-5 py-4 hover:bg-surface-2 transition-colors {{ $n->read_at ? '' : 'bg-[rgb(var(--brand)/0.04)]' }}">
                        @unless ($n->read_at)<span class="mt-1.5 size-2 rounded-full bg-brand shrink-0" aria-label="Unread"></span>@endunless
                        <div class="min-w-0 flex-1 {{ $n->read_at ? 'pl-5' : '' }}">
                            <p class="text-body-sm font-semibold text-ink">{{ $d['title'] ?? 'Notification' }}</p>
                            @if (! empty($d['body']))<p class="text-body-sm text-muted mt-0.5">{{ $d['body'] }}</p>@endif
                            <p class="text-caption text-muted mt-1">{{ $n->created_at->diffForHumans() }}</p>
                        </div>
                    </a>
                @endforeach
            </div>
            <div class="mt-6">{{ $notifications->links() }}</div>
        @endif
    </div>
</x-layouts.app>
