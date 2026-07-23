<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>NeoGiga - Offer Approved</title>
    <style>
        body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; }
        .container { max-width: 600px; margin: 0 auto; padding: 20px; }
        .header { background: #4F46E5; color: white; padding: 20px; text-align: center; }
        .content { padding: 30px 20px; background: #f9fafb; }
        .success-box { background: white; padding: 20px; border-left: 4px solid #10B981; margin: 20px 0; }
        .details { background: white; padding: 20px; margin: 20px 0; }
        .detail-row { display: flex; justify-content: space-between; padding: 10px 0; border-bottom: 1px solid #e5e7eb; }
        .footer { text-align: center; padding: 20px; color: #666; font-size: 12px; }
        .button { display: inline-block; padding: 12px 24px; background: #4F46E5; color: white; text-decoration: none; border-radius: 6px; margin-top: 20px; }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>✓ Offer Approved</h1>
        </div>
        
        <div class="content">
            <h2>Congratulations!</h2>
            
            <p>Your product offer has been approved and is now live on NeoGiga.</p>
            
            <div class="success-box">
                <h3>{{ $productName }}</h3>
                <p><strong>MPN:</strong> {{ $mpn }}</p>
            </div>
            
            <div class="details">
                <h3>Offer Details:</h3>
                <div class="detail-row">
                    <span><strong>Price:</strong></span>
                    <span>{{ number_format($price, 2) }} {{ $currency }}</span>
                </div>
                <div class="detail-row">
                    <span><strong>Quantity:</strong></span>
                    <span>{{ $quantity }} units</span>
                </div>
                <div class="detail-row">
                    <span><strong>Marketplace:</strong></span>
                    <span>{{ $marketplace }}</span>
                </div>
                <div class="detail-row">
                    <span><strong>Approved:</strong></span>
                    <span>{{ $approvedAt->format('M d, Y g:i A') }}</span>
                </div>
            </div>
            
            <p>Your offer is now visible to customers and can receive orders immediately.</p>
            
            <a href="{{ config('app.url') }}/seller/offers" class="button">View All Offers</a>
        </div>
        
        <div class="footer">
            <p>&copy; {{ date('Y') }} NeoGiga. All rights reserved.</p>
            <p>This is an automated message. Please do not reply.</p>
        </div>
    </div>
</body>
</html>
