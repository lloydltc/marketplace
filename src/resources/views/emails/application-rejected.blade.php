<x-emails.layout subject="Update on Your SalmaDrive Application">

    <h1 style="font-size: 22px; font-weight: 700; color: #1A1A24; margin-bottom: 8px;">Application update</h1>
    <p>Hi <strong>{{ $user->name }}</strong>,</p>
    <p>
        Thank you for applying to SalmaDrive. After reviewing your application, we're unable to approve
        your account at this time.
    </p>

    <div style="background: #F4F6F8; border-left: 4px solid #D1D5DB; border-radius: 4px; padding: 16px 20px; margin: 24px 0;">
        <p style="font-size: 13px; color: #5A6070; margin: 0;"><strong style="color:#1A1A24;">Reason:</strong> {{ $reason }}</p>
    </div>

    <p>
        If you believe this decision was made in error, or if you've addressed the above concern,
        you're welcome to re-apply or contact our support team.
    </p>

    <hr class="divider">

    <p class="notice">
        Questions? Email us at
        <a href="mailto:support@salmadrive.co.zw" style="color:#1E2D40;">support@salmadrive.co.zw</a>.
    </p>

</x-emails.layout>
