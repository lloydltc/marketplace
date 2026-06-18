<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <title>{{ $subject ?? 'SalmaDrive' }}</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Helvetica, Arial, sans-serif; background-color: #F4F6F8; color: #1A1A24; }
        .wrapper { max-width: 600px; margin: 0 auto; background-color: #ffffff; }
        .header { background-color: #1E2D40; padding: 28px 40px; text-align: center; }
        .header-wordmark { color: #FFFFFF; font-size: 26px; font-weight: 700; letter-spacing: 1px; text-decoration: none; }
        .header-tagline { color: #C8CDD6; font-size: 11px; letter-spacing: 2px; text-transform: uppercase; margin-top: 4px; }
        .body { padding: 40px; background-color: #ffffff; }
        .body h1 { font-size: 22px; font-weight: 600; color: #1A1A24; margin-bottom: 16px; }
        .body p { font-size: 15px; line-height: 1.7; color: #5A6070; margin-bottom: 16px; }
        .body p strong { color: #1A1A24; }
        .cta-wrapper { text-align: center; margin: 32px 0; }
        .cta-button { display: inline-block; background-color: #1E2D40; color: #FFFFFF !important; text-decoration: none; font-size: 15px; font-weight: 600; padding: 14px 32px; border-radius: 8px; letter-spacing: 0.3px; }
        .divider { border: none; border-top: 1px solid #C8CDD6; margin: 28px 0; }
        .notice { font-size: 13px; color: #5A6070; line-height: 1.6; }
        .footer { background-color: #F4F6F8; padding: 28px 40px; text-align: center; border-top: 1px solid #C8CDD6; }
        .footer p { font-size: 12px; color: #5A6070; line-height: 1.8; margin: 0; }
        .footer a { color: #1E2D40; text-decoration: none; }
        @media (max-width: 600px) {
            .body, .footer { padding: 24px; }
            .header { padding: 20px 24px; }
        }
    </style>
</head>
<body>
<table width="100%" cellpadding="0" cellspacing="0" style="background-color:#F4F6F8; padding: 24px 0;">
<tr><td>
<div class="wrapper">

    {{-- Header --}}
    <div class="header">
        @if(file_exists(public_path('logo/logo_white_trans.png')))
            <img src="{{ asset('logo/logo_white_trans.png') }}" alt="SalmaDrive" height="40" style="margin-bottom: 8px; display: block; margin-left: auto; margin-right: auto;">
        @else
            <span class="header-wordmark">SalmaDrive</span>
        @endif
        <div class="header-tagline">Find It. Buy It. Drive It.</div>
    </div>

    {{-- Body --}}
    <div class="body">
        {{ $slot }}
    </div>

    {{-- Footer --}}
    <div class="footer">
        <p>
            &copy; 2026 SalmaDrive. All rights reserved.<br>
            Need help? <a href="mailto:info@salmadrive.co.zw">Contact info@salmadrive.co.zw</a>
        </p>
    </div>

</div>
</td></tr>
</table>
</body>
</html>
