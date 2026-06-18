<x-emails.layout subject="Reset Your SalmaDrive Password">
    <h1>Reset your password</h1>
    <p>You requested a password reset for your SalmaDrive account associated with <strong>{{ $recipientEmail }}</strong>.</p>
    <p>Click the button below to set a new password. This link expires in <strong>60 minutes</strong>.</p>

    <div class="cta-wrapper">
        <a href="{{ $resetUrl }}" class="cta-button">Reset Password</a>
    </div>

    <hr class="divider">

    <p class="notice">
        If you did not request a password reset, please ignore this email or contact support immediately if you believe your account has been compromised.
    </p>
    <p class="notice">
        If the button doesn't work, copy and paste this URL:<br>
        <a href="{{ $resetUrl }}" style="color:#1E2D40; word-break: break-all;">{{ $resetUrl }}</a>
    </p>
</x-emails.layout>
