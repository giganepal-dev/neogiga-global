<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>NeoGiga - New Order Received</title>
    <style>
        body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; }
        .container { max-width: 600px; margin: 0 auto; padding: 20px; }
        .header { background: #4F46E5; color: white; padding: 20px; text-align: center; }
        .content { padding: 30px 20px; background: #f9fafb; }
        .order-box { background: white; padding: 20px; border-left: 4px solid #F59E0B; margin: 20px 0; }
        .details { background: white; padding: 20px; margin: 20px 0; }
        .detail-row { display: flex; justify-content: space-between; padding: 10px 0; border-bottom: 1px solid #e5e7eb; }
        .footer { text-align: center; padding: 20px; color: #666; font-size: 12px; }
        .button { display: inline-block; padding: 12px 24px; background: #4F46E5; color: white; text-decoration: none; border-radius: 6px; margin-top: 20px; }
        .urgent { color: #DC2626; font-weight: bold; }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>📦 New Order Received</h1>
        </div>
        
        <div class="content">
            <h2>Hello Seller,</h2>
            
            <p>You have received a new order on NeoGiga!</p>
            
            <div class="order-box">
                <h3>Order #{{ $orderNumber }}</h3>
                <p class="urgent">⏰ Dispatch Deadline: {{ $dispatchDeadline->format('M d, Y g:i A') }}</p>
            </div>
            
            <div class="details">
                <h3>Order Details:</h3>
                <div class="detail-row">
                    <span><strong>Order Date:</strong></span>
                    <span>{{ $orderDate->format('M d, Y g:i A') }}</span>
                </div>
                <div class="detail-row">
                    <span><strong>Total Amount:</strong></span>
                    <span>{{ number_format($totalAmount, 2) }} {{ $currency }}</span>
                </div>
                <div class="detail-row">
                    <span><strong>Items:</strong></span>
                    <span>{{ $itemCount }} product(s)</span>
                </div>
                <div class="detail-row">
                    <span><strong>Customer:</strong></span>
                    <span>{{ $customerName }}</span>
                </div>
                @if($shippingAddress)
                <div class="detail-row" style="display: block;">
                    <span><strong>Shipping Address:</strong></span><br>
                    <span style="white-space: pre-line;">{{ $shippingAddress }}</span>
                </div>
                @endif
            </div>
            
            <p>Please log in to your seller dashboard to review and process this order.</p>
            
            <a href="{{ config('app.url') }}/seller/orders/{{ $orderNumber }}" class="button">View Order</a>
        </div>
        
        <div class="footer">
            <p>&copy; {{ date('Y') }} NeoGiga. All rights reserved.</p>
            <p>This is an automated message. Please do not reply.</p>
        </div>
    </div>
</body>
</html>
