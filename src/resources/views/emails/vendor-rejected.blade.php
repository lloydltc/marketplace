<x-emails.layout subject="Update on Your SalmaDrive Vendor Application">
    <h1>Application Update</h1>
    <p>Hi <strong>{{ $vendor->name }}</strong> team,</p>
    <p>
        Thank you for applying to become a vendor on SalmaDrive. After reviewing your application,
        our team was unable to approve it at this time.
    </p>

    <p style="background:#FFF3F5; border-left: 4px solid #D4295A; padding: 12px 16px; border-radius: 4px; color: #1A1A24;">
        <strong>Reason:</strong> {{ $reason }}
    </p>

    <p>
        You are welcome to address the above and reapply. If you believe this decision was made in error,
        please reach out to our support team.
    </p>

    <div class="cta-wrapper">
        <a href="mailto:support@salmadrive.co.zw" class="cta-button">Contact Support</a>
    </div>

    <hr class="divider">

    <p class="notice">
        You may resubmit your application once the noted issue has been resolved.
    </p>
</x-emails.layout>
