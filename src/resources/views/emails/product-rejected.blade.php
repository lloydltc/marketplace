<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Product Not Approved</title>
    <style>
        body { font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif; background: #F4F6F8; margin: 0; padding: 32px 16px; color: #1A1A24; }
        .card { max-width: 520px; margin: 0 auto; background: #fff; border-radius: 12px; border: 1px solid #e5e7eb; overflow: hidden; }
        .header { background: #1A1A24; padding: 28px 32px; }
        .header-title { color: #F0A820; font-size: 20px; font-weight: 700; margin: 0; }
        .body { padding: 32px; }
        h1 { font-size: 18px; font-weight: 600; margin: 0 0 16px; }
        p { font-size: 14px; line-height: 1.6; color: #4b5563; margin: 0 0 12px; }
        .badge { display: inline-block; background: #fee2e2; color: #dc2626; font-size: 12px; font-weight: 600; padding: 4px 12px; border-radius: 20px; margin-bottom: 20px; }
        .product-box { background: #f9fafb; border: 1px solid #e5e7eb; border-radius: 8px; padding: 16px; margin: 20px 0; font-size: 14px; }
        .product-box .label { color: #9ca3af; font-size: 11px; text-transform: uppercase; letter-spacing: 0.05em; }
        .product-box .value { font-weight: 600; color: #111827; }
        .reason-box { background: #fef2f2; border: 1px solid #fecaca; border-radius: 8px; padding: 16px; margin: 20px 0; font-size: 14px; color: #b91c1c; }
        .footer { padding: 20px 32px; border-top: 1px solid #f3f4f6; font-size: 12px; color: #9ca3af; }
    </style>
</head>
<body>
    <div class="card">
        <div class="header">
            <p class="header-title">SalmaDrive</p>
        </div>
        <div class="body">
            <span class="badge">Not Approved</span>
            <h1>Your product listing was not approved</h1>
            <p>After reviewing your product listing, our team was unable to approve it at this time. Please see the reason below.</p>

            <div class="product-box">
                <div class="label">Product</div>
                <div class="value">{{ $product->title }}</div>
            </div>

            <div class="reason-box">
                <strong>Reason:</strong><br>
                {{ $reason }}
            </div>

            <p>You can update your product listing and resubmit it for review from your vendor dashboard. If you believe this decision was made in error, please contact support.</p>
        </div>
        <div class="footer">
            This is an automated notification from SalmaDrive. Please do not reply to this email.
        </div>
    </div>
</body>
</html>
