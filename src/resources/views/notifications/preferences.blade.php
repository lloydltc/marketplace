<x-layouts.app>
    <x-slot:title>Notification Preferences</x-slot:title>

    <div class="max-w-3xl mx-auto px-4 sm:px-6 lg:px-8 py-10">
        <div class="mb-6">
            <a href="{{ route('notifications.index') }}" class="text-body-sm text-muted hover:text-ink">← Notifications</a>
            <h1 class="text-h1 text-ink mt-2">Notification preferences</h1>
            <p class="text-body-sm text-muted mt-1">Choose how you hear about each kind of update.</p>
        </div>

        @if (session('status'))
            <div class="mb-5 bg-[rgb(var(--success)/0.12)] border border-[rgb(var(--success)/0.3)] text-[rgb(var(--success))] text-body-sm rounded-lg px-4 py-3">{{ session('status') }}</div>
        @endif

        <form method="POST" action="{{ route('notifications.preferences.update') }}">
            @csrf @method('PUT')
            <div class="bg-surface border border-line rounded-xl shadow-e1 overflow-hidden">
                <table class="w-full text-body-sm">
                    <thead>
                        <tr class="text-left text-overline uppercase text-muted border-b border-line">
                            <th class="px-5 py-3 font-medium">Notify me about</th>
                            @foreach ($channels as $key => $channel)
                                <th class="px-4 py-3 font-medium text-center">{{ $channel['label'] }}</th>
                            @endforeach
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-line">
                        @foreach ($types as $type => $def)
                            @php $rows = $current[$type] ?? collect(); @endphp
                            <tr>
                                <td class="px-5 py-3 text-ink">{{ $def['label'] }}</td>
                                @foreach ($channels as $channelKey => $channel)
                                    @php
                                        $pref = $rows->firstWhere('channel', $channelKey);
                                        $on = $pref ? $pref->enabled : in_array($channelKey, $def['default'], true);
                                    @endphp
                                    <td class="px-4 py-3 text-center">
                                        <input type="checkbox" name="prefs[{{ $type }}][{{ $channelKey }}]" value="1" @checked($on)
                                               class="rounded border-line text-brand focus:ring-brand">
                                    </td>
                                @endforeach
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            <div class="mt-5 flex justify-end">
                <x-button type="submit" variant="primary">Save preferences</x-button>
            </div>
        </form>
    </div>
</x-layouts.app>
