<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    
    <title>{{ __('Email Preferences') }} - {{ config('app.name') }}</title>
    
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@4.6.0/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
    
    <style>
        body {
            background: #f8f9fa;
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
        }
        .preference-container {
            max-width: 800px;
            margin: 50px auto;
            background: white;
            border-radius: 10px;
            box-shadow: 0 0 20px rgba(0,0,0,0.1);
        }
        .header {
            background: linear-gradient(135deg, #4e73df 0%, #224abe 100%);
            color: white;
            padding: 40px;
            border-radius: 10px 10px 0 0;
            text-align: center;
        }
        .header h1 {
            margin: 0;
            font-size: 2rem;
        }
        .header p {
            margin: 10px 0 0;
            opacity: 0.9;
        }
        .content {
            padding: 40px;
        }
        .section-title {
            font-size: 1.2rem;
            font-weight: 600;
            margin-bottom: 20px;
            color: #333;
            border-bottom: 2px solid #4e73df;
            padding-bottom: 10px;
        }
        .preference-item {
            display: flex;
            align-items: flex-start;
            padding: 15px 0;
            border-bottom: 1px solid #eee;
        }
        .preference-item:last-child {
            border-bottom: none;
        }
        .preference-item .checkbox-wrapper {
            margin-right: 15px;
            min-width: 30px;
        }
        .preference-item .checkbox-wrapper input[type="checkbox"] {
            width: 20px;
            height: 20px;
            cursor: pointer;
        }
        .preference-item .info {
            flex: 1;
        }
        .preference-item .info label {
            font-weight: 500;
            margin-bottom: 5px;
            cursor: pointer;
        }
        .preference-item .info small {
            color: #6c757d;
            display: block;
        }
        .frequency-selector {
            margin-top: 10px;
        }
        .frequency-selector select {
            width: 200px;
            display: inline-block;
        }
        .subscriber-info {
            background: #f8f9fa;
            padding: 20px;
            border-radius: 8px;
            margin-bottom: 30px;
        }
        .subscriber-info table {
            margin: 0;
        }
        .subscriber-info td {
            padding: 8px 0;
        }
        .subscriber-info td:first-child {
            font-weight: 500;
            width: 150px;
            color: #6c757d;
        }
        .btn-update {
            background: linear-gradient(135deg, #4e73df 0%, #224abe 100%);
            border: none;
            padding: 12px 40px;
            font-size: 1rem;
            font-weight: 500;
        }
        .btn-update:hover {
            background: linear-gradient(135deg, #224abe 0%, #4e73df 100%);
        }
        .alert-custom {
            border-radius: 8px;
            border: none;
        }
        .status-badge {
            padding: 5px 15px;
            border-radius: 20px;
            font-size: 0.85rem;
            font-weight: 500;
        }
        .status-subscribed {
            background: #d4edda;
            color: #155724;
        }
        .status-unsubscribed {
            background: #f8d7da;
            color: #721c24;
        }
        .footer {
            text-align: center;
            padding: 20px;
            color: #6c757d;
            font-size: 0.9rem;
            border-top: 1px solid #eee;
        }
        @media (max-width: 768px) {
            .preference-container {
                margin: 20px;
            }
            .header {
                padding: 30px 20px;
            }
            .content {
                padding: 20px;
            }
        }
    </style>
</head>
<body>
    <div class="preference-container">
        <!-- Header -->
        <div class="header">
            <img src="{{ config('app.logo_url') ?? 'https://via.placeholder.com/150x50?text=Logo' }}" 
                 alt="{{ config('app.name') }}" 
                 style="max-height: 50px; margin-bottom: 20px;">
            <h1>{{ __('Email Preferences') }}</h1>
            <p>{{ __('Manage your email subscription settings') }}</p>
        </div>

        <div class="content">
            @if(session('success'))
            <div class="alert alert-success alert-custom">
                <i class="fas fa-check-circle"></i>
                {{ session('success') }}
            </div>
            @endif

            @if(session('error'))
            <div class="alert alert-danger alert-custom">
                <i class="fas fa-exclamation-triangle"></i>
                {{ session('error') }}
            </div>
            @endif

            <!-- Subscriber Info -->
            <div class="subscriber-info">
                <table>
                    <tr>
                        <td>{{ __('Email') }}:</td>
                        <td><strong>{{ $subscriber->email }}</strong></td>
                    </tr>
                    <tr>
                        <td>{{ __('Name') }}:</td>
                        <td>{{ $subscriber->full_name ?: 'N/A' }}</td>
                    </tr>
                    <tr>
                        <td>{{ __('Status') }}:</td>
                        <td>
                            <span class="status-badge status-{{ $subscriber->status }}">
                                {{ ucfirst($subscriber->status) }}
                            </span>
                        </td>
                    </tr>
                    @if($subscriber->country_code)
                    <tr>
                        <td>{{ __('Country') }}:</td>
                        <td>{{ $subscriber->country_code }}</td>
                    </tr>
                    @endif
                </table>
            </div>

            <form action="{{ route('email.preferences.update', ['token' => $token]) }}" method="POST">
                @csrf
                @method('PATCH')

                <!-- Subscription Status -->
                <div class="mb-4">
                    <h4 class="section-title">{{ __('Subscription Status') }}</h4>
                    
                    @if($subscriber->status === 'unsubscribed')
                    <div class="alert alert-warning">
                        <i class="fas fa-info-circle"></i>
                        {{ __('You are currently unsubscribed from marketing emails.') }}
                    </div>
                    <div class="preference-item">
                        <div class="checkbox-wrapper">
                            <input type="checkbox" name="resubscribe" id="resubscribe" value="1">
                        </div>
                        <div class="info">
                            <label for="resubscribe">{{ __('Resubscribe to marketing emails') }}</label>
                            <small>{{ __('Check this box to start receiving promotional emails again') }}</small>
                        </div>
                    </div>
                    @else
                    <div class="preference-item">
                        <div class="checkbox-wrapper">
                            <input type="checkbox" name="unsubscribe_all" id="unsubscribe_all" value="1">
                        </div>
                        <div class="info">
                            <label for="unsubscribe_all">{{ __('Unsubscribe from all marketing emails') }}</label>
                            <small>{{ __('This will stop all promotional communications. Transactional emails will still be sent.') }}</small>
                        </div>
                    </div>
                    @endif
                </div>

                @if($subscriber->status !== 'unsubscribed')
                <!-- Email Categories -->
                <div class="mb-4">
                    <h4 class="section-title">{{ __('Email Categories') }}</h4>
                    <small class="text-muted d-block mb-3">
                        {{ __('Choose which types of emails you want to receive') }}
                    </small>

                    @foreach($consentTypes as $type => $label)
                    <div class="preference-item">
                        <div class="checkbox-wrapper">
                            <input type="checkbox" 
                                   name="consents[{{ $type }}]" 
                                   id="consent_{{ $type }}" 
                                   value="1"
                                   {{ ($preferences[$type] ?? false) ? 'checked' : '' }}>
                        </div>
                        <div class="info">
                            <label for="consent_{{ $type }}">{{ __($label) }}</label>
                            <small>{{ __($type === 'promotional' ? 'Special offers, discounts, and promotions' : '') }}
                                  {{ __($type === 'newsletter' ? 'Regular updates and news' : '') }}
                                  {{ __($type === 'product_updates' ? 'New products and features' : '') }}
                                  {{ __($type === 'transactional' ? 'Order confirmations and account notifications (required)' : '') }}
                            </small>
                            @if(in_array($type, ['promotional', 'newsletter']))
                            <div class="frequency-selector">
                                <label class="small text-muted">{{ __('Frequency') }}:</label>
                                <select name="frequency[{{ $type }}]" class="form-control form-control-sm">
                                    <option value="immediate" {{ ($frequencies[$type] ?? '') === 'immediate' ? 'selected' : '' }}>
                                        {{ __('Immediately') }}
                                    </option>
                                    <option value="daily" {{ ($frequencies[$type] ?? '') === 'daily' ? 'selected' : '' }}>
                                        {{ __('Daily digest') }}
                                    </option>
                                    <option value="weekly" {{ ($frequencies[$type] ?? '') === 'weekly' ? 'selected' : '' }}>
                                        {{ __('Weekly digest') }}
                                    </option>
                                </select>
                            </div>
                            @endif
                        </div>
                    </div>
                    @endforeach
                </div>

                <!-- Personal Information -->
                <div class="mb-4">
                    <h4 class="section-title">{{ __('Personal Information') }}</h4>
                    
                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="first_name">{{ __('First Name') }}</label>
                                <input type="text" name="first_name" id="first_name" 
                                       class="form-control" 
                                       value="{{ $subscriber->first_name }}">
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="last_name">{{ __('Last Name') }}</label>
                                <input type="text" name="last_name" id="last_name" 
                                       class="form-control" 
                                       value="{{ $subscriber->last_name }}">
                            </div>
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="company_name">{{ __('Company Name') }}</label>
                                <input type="text" name="company_name" id="company_name" 
                                       class="form-control" 
                                       value="{{ $subscriber->company_name }}">
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="job_title">{{ __('Job Title') }}</label>
                                <input type="text" name="job_title" id="job_title" 
                                       class="form-control" 
                                       value="{{ $subscriber->job_title }}">
                            </div>
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="preferred_language">{{ __('Preferred Language') }}</label>
                                <select name="preferred_language" id="preferred_language" class="form-control">
                                    <option value="en" {{ $subscriber->preferred_language === 'en' ? 'selected' : '' }}>
                                        English
                                    </option>
                                    <option value="ne" {{ $subscriber->preferred_language === 'ne' ? 'selected' : '' }}>
                                        Nepali
                                    </option>
                                    <option value="hi" {{ $subscriber->preferred_language === 'hi' ? 'selected' : '' }}>
                                        Hindi
                                    </option>
                                    <option value="bn" {{ $subscriber->preferred_language === 'bn' ? 'selected' : '' }}>
                                        Bengali
                                    </option>
                                    <option value="si" {{ $subscriber->preferred_language === 'si' ? 'selected' : '' }}>
                                        Sinhala
                                    </option>
                                </select>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="timezone">{{ __('Timezone') }}</label>
                                <select name="timezone" id="timezone" class="form-control">
                                    @foreach($timezones as $tz)
                                    <option value="{{ $tz }}" {{ $subscriber->timezone === $tz ? 'selected' : '' }}>
                                        {{ $tz }}
                                    </option>
                                    @endforeach
                                </select>
                            </div>
                        </div>
                    </div>
                </div>
                @endif

                <!-- Submit Buttons -->
                <div class="text-center mt-5">
                    <button type="submit" class="btn btn-primary btn-update">
                        <i class="fas fa-save"></i> {{ __('Update Preferences') }}
                    </button>
                    
                    @if($subscriber->status !== 'unsubscribed')
                    <a href="{{ route('email.unsubscribe.execute', ['token' => $token]) }}" 
                       class="btn btn-outline-danger ml-2"
                       onclick="return confirm('{{ __("Are you sure you want to unsubscribe from all marketing emails?") }}')">
                        <i class="fas fa-ban"></i> {{ __('Unsubscribe All') }}
                    </a>
                    @endif
                </div>
            </form>
        </div>

        <!-- Footer -->
        <div class="footer">
            <p>
                {{ __('Why am I seeing this?') }} 
                {{ __('You received this because you subscribed to emails from') }} 
                <strong>{{ config('app.name') }}</strong>.
            </p>
            <p>
                {{ __('Your privacy is important to us. We never share your information with third parties.') }}
            </p>
            <p class="mt-3">
                <a href="{{ url('/') }}" class="text-muted">{{ __('Visit our website') }}</a>
                ·
                <a href="{{ route('email.preferences.show', ['token' => $token]) }}" class="text-muted">
                    {{ __('Refresh page') }}
                </a>
            </p>
        </div>
    </div>

    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@4.6.0/dist/js/bootstrap.bundle.min.js"></script>
    
    <script>
        // Toggle frequency selectors based on consent
        document.querySelectorAll('input[name^="consents["]').forEach(checkbox => {
            checkbox.addEventListener('change', function() {
                const type = this.id.replace('consent_', '');
                const freqSelector = document.querySelector(`select[name="frequency[${type}]"]`);
                if (freqSelector) {
                    freqSelector.parentElement.style.display = this.checked ? 'block' : 'none';
                }
            });
            
            // Trigger initial state
            checkbox.dispatchEvent(new Event('change'));
        });

        // Confirm before unsubscribe all
        document.querySelector('#unsubscribe_all')?.addEventListener('change', function() {
            if (this.checked && !confirm('{{ __("Are you sure you want to unsubscribe from all marketing emails? This cannot be undone immediately.") }}')) {
                this.checked = false;
            }
        });
    </script>
</body>
</html>
