<x-layouts.auth>
    <x-slot:title>Verify Email</x-slot:title>

    <div class="text-center">
        {{-- Icon --}}
        <div class="mx-auto w-16 h-16 bg-[#F0A820]/10 rounded-full flex items-center justify-center mb-4">
            <svg class="w-8 h-8 text-[#F0A820]" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                      d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/>
            </svg>
        </div>

        <h1 class="text-2xl font-semibold text-neutral-900 mb-1">Check your email</h1>
        <p class="text-sm text-neutral-500 mb-6">
            We sent a 6-digit code to your email address.<br>
            Enter it below to verify your account.
        </p>

        {{-- Status messages --}}
        @if (session('status') === 'verification-link-sent')
            <div class="mb-4 bg-green-50 border border-green-200 text-green-700 text-sm rounded-lg px-4 py-3">
                A new verification code has been sent to your email.
            </div>
        @endif

        @if ($errors->has('otp'))
            <div class="mb-4 bg-red-50 border border-red-200 text-red-700 text-sm rounded-lg px-4 py-3">
                {{ $errors->first('otp') }}
            </div>
        @endif

        {{-- OTP Form --}}
        <form method="POST" action="{{ route('verification.verify') }}" id="otp-form" novalidate>
            @csrf
            <input type="hidden" name="otp" id="otp-hidden">

            {{-- Digit boxes — inline styles so they work before any Vite rebuild --}}
            <div style="display: flex; justify-content: center; gap: 10px; margin-bottom: 24px;" id="otp-inputs">
                @for ($i = 0; $i < 6; $i++)
                    <input
                        type="text"
                        inputmode="numeric"
                        maxlength="1"
                        pattern="[0-9]"
                        @if ($i === 0) autofocus @endif
                        class="otp-digit"
                        style="
                            width: 48px;
                            height: 60px;
                            text-align: center;
                            font-size: 26px;
                            font-weight: 700;
                            border: 2px solid #D1D5DB;
                            border-radius: 12px;
                            background: #FFFFFF;
                            color: #111827;
                            outline: none;
                            transition: border-color 0.15s;
                            caret-color: transparent;
                        "
                    >
                @endfor
            </div>

            <button
                type="submit"
                id="otp-submit"
                disabled
                style="
                    width: 100%;
                    background-color: #1E2D40;
                    color: white;
                    font-weight: 600;
                    font-size: 15px;
                    padding: 11px 0;
                    border-radius: 8px;
                    border: none;
                    cursor: pointer;
                    transition: opacity 0.15s;
                    margin-bottom: 12px;
                "
            >
                Verify Email
            </button>
        </form>

        {{-- Resend --}}
        <form method="POST" action="{{ route('verification.send') }}">
            @csrf
            <button type="submit"
                    class="w-full border border-neutral-300 hover:bg-neutral-50 text-neutral-700 font-medium py-2.5 rounded-lg transition-colors text-sm">
                Resend code
            </button>
        </form>

        <form method="POST" action="{{ route('logout') }}" class="mt-3">
            @csrf
            <button type="submit" class="text-sm text-neutral-400 hover:text-neutral-600 transition-colors">
                Sign out
            </button>
        </form>
    </div>

    <style>
        .otp-digit:focus { border-color: #1E2D40; }
        #otp-submit:disabled { opacity: 0.4; cursor: not-allowed; }
        #otp-submit:not(:disabled):hover { opacity: 0.88; }
    </style>

    <script>
        (function () {
            const inputs = Array.from(document.querySelectorAll('.otp-digit'));
            const hidden = document.getElementById('otp-hidden');
            const submit = document.getElementById('otp-submit');
            const form   = document.getElementById('otp-form');

            function sync() {
                const val    = inputs.map(i => i.value).join('');
                hidden.value = val;
                submit.disabled = val.length !== 6;
            }

            inputs.forEach((input, idx) => {
                input.addEventListener('input', () => {
                    input.value = input.value.replace(/\D/g, '').slice(-1);
                    if (input.value && idx < 5) inputs[idx + 1].focus();
                    sync();
                    // auto-submit when last digit filled
                    if (inputs.every(i => i.value)) form.requestSubmit();
                });

                input.addEventListener('keydown', e => {
                    if (e.key === 'Backspace' && !input.value && idx > 0) {
                        inputs[idx - 1].focus();
                    }
                });

                input.addEventListener('paste', e => {
                    e.preventDefault();
                    const digits = (e.clipboardData.getData('text') || '').replace(/\D/g, '');
                    digits.split('').slice(0, 6).forEach((ch, j) => {
                        if (inputs[j]) inputs[j].value = ch;
                    });
                    const focus = Math.min(digits.length, 5);
                    inputs[focus].focus();
                    sync();
                    if (inputs.every(i => i.value)) form.requestSubmit();
                });
            });
        })();
    </script>
</x-layouts.auth>
