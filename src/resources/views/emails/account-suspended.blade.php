<x-emails.layout subject="Your SalmaDrive Account Has Been Suspended">
    <h1>Account Suspended</h1>
    <p>Hi <strong>{{ $user->name }}</strong>,</p>
    <p>Your SalmaDrive account has been suspended. Here is the reason provided:</p>

    <p style="background:#FFF3F5; border-left: 4px solid #D4295A; padding: 12px 16px; border-radius: 4px; color: #1A1A24;">
        {{ $reason }}
    </p>

    <p>If you believe this is a mistake or would like to appeal, please contact our support team.</p>

    <div class="cta-wrapper">
        <a href="mailto:support@salmadrive.co.zw" class="cta-button">Contact Support</a>
    </div>

    <hr class="divider">

    <p class="notice">
        Please do not attempt to create a new account while your current account is under review.
        Doing so may result in a permanent ban.
    </p>
</x-emails.layout>
