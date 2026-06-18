<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Vehicle Listing Approved</title>
    <style>
        body { margin: 0; padding: 0; background: #F4F6F8; font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif; color: #1A1A24; }
        .wrapper { max-width: 580px; margin: 40px auto; background: #ffffff; border-radius: 12px; overflow: hidden; border: 1px solid #E5E7EB; }
        .header { background: #1A1A24; padding: 28px 32px; }
        .header h1 { margin: 0; color: #F0A820; font-size: 20px; font-weight: 700; letter-spacing: -0.3px; }
        .header p { margin: 4px 0 0; color: #9CA3AF; font-size: 12px; }
        .body { padding: 32px; }
        .badge { display: inline-block; background: #D1FAE5; color: #065F46; font-size: 12px; font-weight: 600; padding: 4px 12px; border-radius: 99px; margin-bottom: 20px; }
        h2 { margin: 0 0 8px; font-size: 18px; font-weight: 700; }
        p { margin: 0 0 16px; font-size: 14px; line-height: 1.6; color: #4B5563; }
        .vehicle-card { background: #F9FAFB; border: 1px solid #E5E7EB; border-radius: 10px; padding: 18px 20px; margin: 20px 0; }
        .vehicle-card .title { font-size: 16px; font-weight: 700; color: #1A1A24; margin: 0 0 4px; }
        .vehicle-card .meta { font-size: 13px; color: #6B7280; }
        .vehicle-card .price { font-size: 15px; font-weight: 700; color: #1A1A24; margin-top: 10px; }
        .btn { display: inline-block; background: #2EBD7A; color: #ffffff; font-size: 14px; font-weight: 600; padding: 12px 24px; border-radius: 8px; text-decoration: none; margin: 8px 0 20px; }
        .footer { border-top: 1px solid #F3F4F6; padding: 20px 32px; font-size: 12px; color: #9CA3AF; }
    </style>
</head>
<body>
    <div class="wrapper">
        <div class="header">
            <h1>SalmaDrive</h1>
            <p>Automotive Marketplace &mdash; Zimbabwe</p>
        </div>
        <div class="body">
            <div class="badge">Listing Approved</div>
            <h2>Your vehicle listing is live!</h2>
            <p>Great news &mdash; your vehicle listing has been reviewed and approved by our team. It's now visible to buyers on the marketplace.</p>

            <div class="vehicle-card">
                <div class="title">{{ $vehicle->displayTitle() }}</div>
                <div class="meta">
                    {{ ucfirst($vehicle->condition) }} &bull;
                    {{ ucfirst($vehicle->body_type) }} &bull;
                    {{ ucfirst($vehicle->fuel_type) }} &bull;
                    {{ number_format($vehicle->mileage) }} km
                </div>
                <div class="price">ZWL {{ number_format($vehicle->price_zwl, 2) }}
                    @if ($vehicle->price_usd) &nbsp;/&nbsp; USD {{ number_format($vehicle->price_usd, 2) }}@endif
                </div>
            </div>

            <a href="{{ route('vehicles.show', $vehicle) }}" class="btn">View Your Listing</a>

            <p>If you need to make changes, sign in to your account and edit the listing at any time.</p>
        </div>
        <div class="footer">
            &copy; {{ date('Y') }} SalmaDrive. This is an automated notification.
        </div>
    </div>
</body>
</html>
