<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>NeoGiga - Onboarding Step Completed</title>
    <style>
        body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; }
        .container { max-width: 600px; margin: 0 auto; padding: 20px; }
        .header { background: #4F46E5; color: white; padding: 20px; text-align: center; }
        .content { padding: 30px 20px; background: #f9fafb; }
        .step-box { background: white; padding: 20px; border-left: 4px solid #10B981; margin: 20px 0; }
        .next-steps { background: white; padding: 20px; margin: 20px 0; }
        .footer { text-align: center; padding: 20px; color: #666; font-size: 12px; }
        .button { display: inline-block; padding: 12px 24px; background: #4F46E5; color: white; text-decoration: none; border-radius: 6px; margin-top: 20px; }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>NeoGiga Seller Portal</h1>
        </div>
        
        <div class="content">
            <h2>Hello {{ $sellerName }},</h2>
            
            <p>Great news! Your onboarding step has been completed successfully.</p>
            
            <div class="step-box">
                <h3>✓ {{ $stepName }}</h3>
                <p>This step has been marked as complete in your seller profile.</p>
            </div>
            
            @if(count($nextSteps) > 0)
            <div class="next-steps">
                <h3>Next Steps:</h3>
                <ol>
                    @foreach($nextSteps as $step)
                        <li>{{ ucwords(str_replace('_', ' ', $step)) }}</li>
                    @endforeach
                </ol>
            </div>
            @endif
            
            <p>Continue your onboarding journey to start selling on NeoGiga marketplaces.</p>
            
            <a href="{{ config('app.url') }}/seller/onboarding" class="button">Continue Onboarding</a>
        </div>
        
        <div class="footer">
            <p>&copy; {{ date('Y') }} NeoGiga. All rights reserved.</p>
            <p>This is an automated message. Please do not reply.</p>
        </div>
    </div>
</body>
</html>
