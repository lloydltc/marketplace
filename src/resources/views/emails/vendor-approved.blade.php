<x-emails.layout subject="Your SalmaDrive Vendor Account Has Been Approved!">
    <h1>Congratulations! You're approved.</h1>
    <p>Great news — your vendor account for <strong>{{ $vendor->name }}</strong> on SalmaDrive has been approved by our team.</p>
    <p>You can now log in to your vendor dashboard and start listing your products.</p>

    <div class="cta-wrapper">
        <a href="{{ url('/vendor/dashboard') }}" class="cta-button">Go to Vendor Dashboard</a>
    </div>

    <hr class="divider">

    <p class="notice">
        If you have any questions or need help getting started, our support team is ready to assist.
        Contact us at <a href="mailto:support@salmadrive.co.zw">support@salmadrive.co.zw</a>.
    </p>
</x-emails.layout>
