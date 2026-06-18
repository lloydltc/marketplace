<x-layouts.app>
    <x-slot:title>Bank Accounts</x-slot:title>

    <div class="max-w-2xl mx-auto px-4 sm:px-6 lg:px-8 py-10">

        <div class="flex items-center justify-between mb-6">
            <h1 class="text-2xl font-semibold text-neutral-900">Bank Accounts</h1>
        </div>

        @if (session('status'))
            <div class="mb-5 bg-green-50 border border-green-200 text-green-700 text-sm rounded-lg px-4 py-3">
                {{ session('status') }}
            </div>
        @endif

        {{-- Existing accounts --}}
        @forelse ($vendor->bankAccounts as $account)
            <div class="bg-white border border-neutral-200 rounded-xl shadow-sm p-5 mb-4">
                <div class="flex items-center justify-between">
                    <div>
                        <div class="text-sm font-semibold text-neutral-900">{{ $account->bank_name }}</div>
                        <div class="text-sm text-neutral-600">{{ $account->account_holder }}</div>
                        <div class="text-xs text-neutral-400 mt-0.5 font-mono">{{ $account->maskedAccountNumber() }}</div>
                    </div>
                    <div class="flex items-center gap-3">
                        @if ($account->isVerified())
                            <span class="inline-flex items-center gap-1 text-xs bg-green-100 text-green-700 px-2.5 py-0.5 rounded-full font-medium">
                                <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                                </svg>
                                Verified
                            </span>
                        @else
                            <span class="text-xs bg-yellow-100 text-yellow-700 px-2.5 py-0.5 rounded-full font-medium">Pending verification</span>
                            <form method="POST" action="{{ route('vendor.bank-accounts.destroy', $account) }}">
                                @csrf @method('DELETE')
                                <button type="submit"
                                        onclick="return confirm('Remove this bank account?')"
                                        class="text-xs text-red-500 hover:underline">Remove</button>
                            </form>
                        @endif
                    </div>
                </div>
            </div>
        @empty
            <div class="bg-white border border-neutral-200 rounded-xl p-8 text-center mb-6">
                <p class="text-sm text-neutral-500">No bank accounts added yet. Add one below to receive payouts.</p>
            </div>
        @endforelse

        {{-- Add account form --}}
        <div class="bg-white border border-neutral-200 rounded-xl shadow-sm p-6">
            <h2 class="text-base font-semibold text-neutral-900 mb-5">Add Bank Account</h2>
            <form method="POST" action="{{ route('vendor.bank-accounts.store') }}" class="space-y-4">
                @csrf

                @if ($errors->any())
                    <div class="bg-red-50 border border-red-200 text-red-700 text-sm rounded-lg px-4 py-3">
                        {{ $errors->first() }}
                    </div>
                @endif

                @foreach ([
                    ['bank_name', 'Bank Name', 'e.g. FBC Bank', 'text'],
                    ['account_number', 'Account Number', 'e.g. 1234567890', 'text'],
                    ['account_holder', 'Account Holder Name', 'Full legal name on the account', 'text'],
                    ['branch_code', 'Branch Code (optional)', '', 'text'],
                ] as [$field, $label, $placeholder, $type])
                    <div class="space-y-1">
                        <label for="{{ $field }}" class="block text-sm font-medium text-neutral-700">{{ $label }}</label>
                        <input id="{{ $field }}" name="{{ $field }}" type="{{ $type }}"
                               value="{{ old($field) }}"
                               placeholder="{{ $placeholder }}"
                               class="block w-full border rounded-lg px-3 py-2.5 text-neutral-900 text-sm placeholder-neutral-400
                                      focus:outline-none focus:ring-2 focus:ring-[#F0A820] focus:border-[#F0A820]
                                      @error($field) border-red-500 @else border-neutral-300 @enderror">
                    </div>
                @endforeach

                <p class="text-xs text-neutral-500">An administrator will verify your bank account before payouts are enabled.</p>

                <button type="submit"
                        class="w-full bg-[#1A1A24] hover:bg-[#080810] text-white font-medium py-2.5 rounded-lg text-sm transition-colors">
                    Add bank account
                </button>
            </form>
        </div>

    </div>
</x-layouts.app>
