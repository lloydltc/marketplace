<x-layouts.app>
    <x-slot:title>Platform Settings</x-slot:title>

    <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 py-10">

        <div class="mb-6">
            <h1 class="text-2xl font-semibold text-neutral-900">Platform Settings</h1>
            <p class="text-sm text-neutral-500 mt-1">
                Fees, thresholds and limits. Every money rule in the platform reads from here —
                changes take effect immediately, no deploy required.
            </p>
        </div>

        @if (session('status'))
            <div class="mb-5 bg-green-50 border border-green-200 text-green-700 text-sm rounded-lg px-4 py-3">
                {{ session('status') }}
            </div>
        @endif

        @if ($errors->any())
            <div class="mb-5 bg-red-50 border border-red-200 text-red-700 text-sm rounded-lg px-4 py-3">
                Please correct the highlighted values.
            </div>
        @endif

        <form method="POST" action="{{ route('admin.settings.update') }}" class="space-y-6">
            @csrf
            @method('PUT')

            @foreach ($groups as $group => $settings)
                <div class="bg-white border border-neutral-200 rounded-xl shadow-sm overflow-hidden">
                    <div class="px-5 py-3 border-b border-neutral-100 bg-neutral-50">
                        <h2 class="text-sm font-semibold text-neutral-800 uppercase tracking-wide">{{ $group }}</h2>
                    </div>
                    <div class="divide-y divide-neutral-100">
                        @foreach ($settings as $setting)
                            <div class="px-5 py-4 flex flex-col sm:flex-row sm:items-center gap-3">
                                <div class="sm:w-1/2">
                                    <label for="setting-{{ $setting->key }}" class="block text-sm font-medium text-neutral-700 font-mono">{{ $setting->key }}</label>
                                    @if ($setting->description)
                                        <p class="text-xs text-neutral-500 mt-0.5">{{ $setting->description }}</p>
                                    @endif
                                </div>
                                <div class="sm:w-1/2">
                                    @php
                                        $field   = 'settings[' . $setting->key . ']';
                                        $oldAll  = old('settings', []);
                                        $current = array_key_exists($setting->key, $oldAll) ? $oldAll[$setting->key] : $setting->value;
                                        $hasError = $errors->has('settings.' . $setting->key);
                                    @endphp
                                    @if ($setting->type === 'boolean')
                                        <label class="inline-flex items-center gap-2">
                                            <input type="hidden" name="{{ $field }}" value="0">
                                            <input type="checkbox" id="setting-{{ $setting->key }}"
                                                   name="{{ $field }}" value="1"
                                                   @checked($current == '1' || $current === 'true' || $current === true)
                                                   class="w-4 h-4 rounded border-neutral-300 text-[#F0A820] focus:ring-[#F0A820]">
                                            <span class="text-sm text-neutral-600">Enabled</span>
                                        </label>
                                    @elseif ($setting->type === 'integer' || $setting->type === 'decimal')
                                        <input type="number" id="setting-{{ $setting->key }}"
                                               name="{{ $field }}"
                                               step="{{ $setting->type === 'integer' ? '1' : '0.01' }}" min="0"
                                               value="{{ $current }}"
                                               class="block w-full border rounded-lg px-3 py-2 text-neutral-900 text-sm
                                                      focus:outline-none focus:ring-2 focus:ring-[#F0A820] focus:border-[#F0A820]
                                                      {{ $hasError ? 'border-red-500' : 'border-neutral-300' }}">
                                    @else
                                        <input type="text" id="setting-{{ $setting->key }}"
                                               name="{{ $field }}"
                                               value="{{ $current }}"
                                               class="block w-full border rounded-lg px-3 py-2 text-neutral-900 text-sm
                                                      focus:outline-none focus:ring-2 focus:ring-[#F0A820] focus:border-[#F0A820]
                                                      {{ $hasError ? 'border-red-500' : 'border-neutral-300' }}">
                                    @endif
                                    @if ($hasError)
                                        <p class="text-xs text-red-600 mt-1">{{ $errors->first('settings.' . $setting->key) }}</p>
                                    @endif
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>
            @endforeach

            <div class="flex justify-end">
                <button type="submit"
                        class="bg-[#F0A820] hover:bg-[#F0A820]/90 text-[#1A1A24] font-semibold px-5 py-2.5 rounded-lg text-sm transition-colors">
                    Save settings
                </button>
            </div>
        </form>

    </div>
</x-layouts.app>
