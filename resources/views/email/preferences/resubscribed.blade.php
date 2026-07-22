<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Resubscribed - NeoGiga</title>
    <style>
        body { font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif; line-height: 1.6; max-width: 600px; margin: 0 auto; padding: 20px; background: #f5f5f5; }
        .container { background: white; border-radius: 8px; padding: 30px; box-shadow: 0 2px 4px rgba(0,0,0,0.1); text-align: center; }
        h1 { color: #16a34a; margin-bottom: 10px; }
        p { color: #666; margin-bottom: 20px; }
        .icon { font-size: 48px; margin-bottom: 20px; }
        .btn { display: inline-block; padding: 12px 24px; background: #2563eb; color: white; text-decoration: none; border-radius: 6px; margin-top: 20px; }
    </style>
</head>
<body>
    <div class="container">
        <div class="icon">✅🎉</div>
        <h1>Welcome Back!</h1>
        <p>{{ $subscriber->email }}</p>
        <p>You have been successfully resubscribed to NeoGiga marketing emails.</p>
        <a href="{{ route('email.preferences.show', \App\Http\Controllers\Email\EmailPreferenceController::generateToken($subscriber)) }}" class="btn">Manage Preferences</a>
    </div>
</body>
</html>
