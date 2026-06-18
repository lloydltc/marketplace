<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Product Approved</title>
    <style>
        body { font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif; background: #F4F6F8; margin: 0; padding: 32px 16px; color: #1A1A24; }
        .card { max-width: 520px; margin: 0 auto; background: #fff; border-radius: 12px; border: 1px solid #e5e7eb; overflow: hidden; }
        .header { background: #1A1A24; padding: 28px 32px; }
        .header-title { color: #F0A820; font-size: 20px; font-weight: 700; margin: 0; }
        .body { padding: 32px; }
        h1 { font-size: 18px; font-weight: 600; margin: 0 0 16px; }
        p { font-size: 14px; line-height: 1.6; color: #4b5563; margin: 0 0 12px; }
        .badge { display: inline-block; background: #dcfce7; color: #16a34a; font-size: 12px; font-weight: 600; padding: 4px 12px; border-radius: 20px; margin-bottom: 20px; }
        .product-box { background: #f9fafb; border: 1px solid #e5e7eb; border-radius: 8px; padding: 16px; margin: 20px 0; font-size: 14px; }
        .product-box .label { color: #9ca3af; font-size: 11px; text-transform: uppercase; letter-spacing: 0.05em; }
        .product-box .value { font-weight: 600; color: #111827; }
        .footer { padding: 20px 32px; border-top: 1px solid #f3f4f6; font-size: 12px; color: #9ca3af; }
    </style>
</head>
<body>
    <div class="card">
        <div class="header">
            <p class="header-title">SalmaDrive</p>
        </div>
        <div class="body">
            <span class="badge">✓ Approved</span>
            <h1>Your product is now live!</h1>
            <p>Great news — your product has been reviewed and approved. It is now visible to customers on the marketplace.</p>

            <div class="product-box">
                <div class="label">Product</div>
                <div class="value">{{ $product->title }}</div>
                @if ($product->sku)
                    <div class="label" style="margin-top:8px;">SKU</div>
                    <div class="value">{{ $product->sku }}</div>
                @endif
                <div class="label" style="margin-top:8px;">Price</div>
                <div class="value">ZWL {{ number_format($product->price_zwl, 2) }}</div>
            </div>

            <p>Customers can now discover and purchase your product. Make sure your stock levels are up to date.</p>
        </div>
        <div class="footer">
            This is an automated notification from SalmaDrive. Please do not reply to this email.
        </div>
    </div>
</body>
</html>
