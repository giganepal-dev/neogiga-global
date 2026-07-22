<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Email Preferences - NeoGiga</title>
    <style>
        body { font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif; line-height: 1.6; max-width: 600px; margin: 0 auto; padding: 20px; background: #f5f5f5; }
        .container { background: white; border-radius: 8px; padding: 30px; box-shadow: 0 2px 4px rgba(0,0,0,0.1); }
        h1 { color: #1a1a1a; margin-bottom: 10px; }
        .subtitle { color: #666; margin-bottom: 30px; }
        .form-group { margin-bottom: 20px; }
        label { display: block; font-weight: 500; margin-bottom: 8px; color: #333; }
        input[type="text"], input[type="email"], select { width: 100%; padding: 10px; border: 1px solid #ddd; border-radius: 4px; font-size: 14px; }
        .checkbox-group { display: flex; align-items: center; gap: 10px; margin-bottom: 12px; }
        .checkbox-group input[type="checkbox"] { width: 18px; height: 18px; }
        .btn { display: inline-block; padding: 12px 24px; background: #2563eb; color: white; text-decoration: none; border-radius: 6px; border: none; cursor: pointer; font-size: 14px; }
        .btn:hover { background: #1d4ed8; }
        .btn-danger { background: #dc2626; }
        .btn-danger:hover { background: #b91c1c; }
        .alert { padding: 12px 16px; border-radius: 6px; margin-bottom: 20px; }
        .alert-success { background: #dcfce7; color: #166534; border: 1px solid #86efac; }
        .alert-error { background: #fee2e2; color: #991b1b; border: 1px solid #fca5a5; }
        .section { border-top: 1px solid #eee; padding-top: 20px; margin-top: 20px; }
        .actions { margin-top: 30px; display: flex; gap: 10px; flex-wrap: wrap; }
    </style>
</head>
<body>
    <div class="container">
        <h1>Email Preferences</h1>
        <p class="subtitle">Manage your email subscription settings for {{ $subscriber->email }}</p>

        @if(session('success'))
            <div class="alert alert-success">{{ session('success') }}</div>
        @endif

        @if(session('error'))
            <div class="alert alert-error">{{ session('error') }}</div>
        @endif

        <form method="POST" action="{{ route('email.preferences.update', $token) }}">
            @csrf
            
            <div class="section">
                <h3>Personal Information</h3>
                
                <div class="form-group">
                    <label for="first_name">First Name</label>
                    <input type="text" id="first_name" name="first_name" value="{{ $subscriber->first_name ?? '' }}">
                </div>

                <div class="form-group">
                    <label for="last_name">Last Name</label>
                    <input type="text" id="last_name" name="last_name" value="{{ $subscriber->last_name ?? '' }}">
                </div>

                <div class="form-group">
                    <label for="company_name">Company Name</label>
                    <input type="text" id="company_name" name="company_name" value="{{ $subscriber->company_name ?? '' }}">
                </div>

                <div class="form-group">
                    <label for="preferred_language">Preferred Language</label>
                    <select id="preferred_language" name="preferred_language">
                        <option value="en" {{ $subscriber->preferred_language === 'en' ? 'selected' : '' }}>English</option>
                        <option value="np" {{ $subscriber->preferred_language === 'np' ? 'selected' : '' }}>नेपाली (Nepali)</option>
                        <option value="hi" {{ $subscriber->preferred_language === 'hi' ? 'selected' : '' }}>हिन्दी (Hindi)</option>
                        <option value="bn" {{ $subscriber->preferred_language === 'bn' ? 'selected' : '' }}>বাংলা (Bengali)</option>
                    </select>
                </div>
            </div>

            <div class="section">
                <h3>Email Categories</h3>
                <p style="color: #666; font-size: 14px;">Choose which types of emails you'd like to receive:</p>
                
                <div class="checkbox-group">
                    <input type="checkbox" id="consent_transactional" name="consent_transactional" checked disabled>
                    <label for="consent_transactional">Transactional Emails (required for account communications)</label>
                </div>

                <div class="checkbox-group">
                    <input type="checkbox" id="consent_promotional" name="consent_promotional" {{ $subscriber->hasConsent('promotional') ? 'checked' : '' }}>
                    <label for="consent_promotional">Promotional Emails (special offers, discounts)</label>
                </div>

                <div class="checkbox-group">
                    <input type="checkbox" id="consent_newsletter" name="consent_newsletter" {{ $subscriber->hasConsent('newsletter') ? 'checked' : '' }}>
                    <label for="consent_newsletter">Newsletter (industry updates, news)</label>
                </div>

                <div class="checkbox-group">
                    <input type="checkbox" id="consent_product_updates" name="consent_product_updates" {{ $subscriber->hasConsent('product_updates') ? 'checked' : '' }}>
                    <label for="consent_product_updates">Product Updates (new features, announcements)</label>
                </div>

                <div class="checkbox-group">
                    <input type="checkbox" id="consent_regional_offers" name="consent_regional_offers" {{ $subscriber->hasConsent('regional_offers') ? 'checked' : '' }}>
                    <label for="consent_regional_offers">Regional Offers (location-specific deals)</label>
                </div>
            </div>

            <div class="actions">
                <button type="submit" class="btn">Save Preferences</button>
                <a href="{{ route('email.unsubscribe', $token) }}" class="btn btn-danger" onclick="return confirm('Are you sure you want to unsubscribe from all marketing emails?')">Unsubscribe All</a>
            </div>
        </form>
    </div>
</body>
</html>
