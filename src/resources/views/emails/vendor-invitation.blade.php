<x-emails.layout subject="You've been invited to join SalmaDrive">
    <h1>You've been invited!</h1>
    <p>
        <strong>{{ $invitation->inviter->name }}</strong> has invited you to join
        <strong>{{ $invitation->vendor->name }}</strong> on SalmaDrive as a team member.
    </p>

    <p>Your temporary credentials:</p>
    <p>
        &nbsp;&nbsp;<strong>Email:</strong> {{ $invitation->email }}<br>
        &nbsp;&nbsp;<strong>Temporary Password:</strong> <code style="background:#F4F6F8; padding:2px 6px; border-radius:4px;">{{ $invitation->temp_password }}</code>
    </p>

    <p>Click the button below to accept the invitation and set up your account. This invite expires in <strong>48 hours</strong>.</p>

    <div class="cta-wrapper">
        <a href="{{ $acceptUrl }}" class="cta-button">Accept Invitation</a>
    </div>

    <hr class="divider">

    <p class="notice">
        You will be prompted to change your password immediately after accepting. Keep your credentials safe.
    </p>
    <p class="notice">
        If you were not expecting this invitation, you can safely ignore this email.
    </p>
</x-emails.layout>
