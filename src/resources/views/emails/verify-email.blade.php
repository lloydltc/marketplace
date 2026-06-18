<x-emails.layout subject="Your SalmaDrive Verification Code">

    <h1 style="font-size: 22px; font-weight: 700; color: #1A1A24; margin-bottom: 8px;">Verify your email</h1>
    <p>Hi <strong>{{ $user->name }}</strong>,</p>
    <p>Enter the 6-digit code below to activate your SalmaDrive account. The code expires in <strong>10 minutes</strong>.</p>

    {{-- OTP digit blocks --}}
    <div style="margin: 36px 0; text-align: center;">
        <p style="font-size: 11px; color: #8A91A0; text-transform: uppercase; letter-spacing: 3px; margin-bottom: 18px;">Your verification code</p>
        <table cellpadding="0" cellspacing="0" role="presentation" style="margin: 0 auto;">
            <tr>
                @foreach (str_split($otp) as $digit)
                <td style="padding: 0 5px;">
                    <div style="
                        width: 52px;
                        height: 66px;
                        background-color: #1E2D40;
                        border-radius: 12px;
                        text-align: center;
                        line-height: 66px;
                        font-size: 32px;
                        font-weight: 700;
                        color: #F0A820;
                        font-family: 'Courier New', Courier, monospace;
                        letter-spacing: 0;
                    ">{{ $digit }}</div>
                </td>
                @endforeach
            </tr>
        </table>
        <p style="font-size: 13px; color: #8A91A0; margin-top: 18px;">
            Valid for <strong style="color: #1A1A24;">10 minutes</strong>
        </p>
    </div>

    <hr class="divider">

    <p class="notice">
        Didn't create a SalmaDrive account? You can safely ignore this email — your address will not be registered.
    </p>
    <p class="notice" style="margin-top: 8px;">
        Never share this code with anyone. SalmaDrive will never ask for your code over phone or chat.
    </p>

</x-emails.layout>
