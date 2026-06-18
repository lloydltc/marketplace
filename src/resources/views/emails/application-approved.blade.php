<x-emails.layout subject="Your SalmaDrive Application Has Been Approved">

    <h1 style="font-size: 22px; font-weight: 700; color: #1A1A24; margin-bottom: 8px;">You're approved!</h1>
    <p>Hi <strong>{{ $user->name }}</strong>,</p>
    <p>
        Great news — your SalmaDrive application has been reviewed and <strong>approved</strong>.
        Your account is now active and you can log in to get started.
    </p>

    <div class="cta-wrapper">
        <a href="{{ url('/login') }}" class="cta-button">Log In to SalmaDrive</a>
    </div>

    <hr class="divider">

    <p class="notice">
        If you have any questions, reach out to us at
        <a href="mailto:support@salmadrive.co.zw" style="color:#1E2D40;">support@salmadrive.co.zw</a>.
    </p>

</x-emails.layout>
