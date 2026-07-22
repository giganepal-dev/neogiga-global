@extends('layouts.admin')

@section('title', __('Create Campaign'))
@section('page-title', __('Create New Email Campaign'))

@section('content')
<div class="container-fluid">
    <form action="{{ route('admin.email.campaigns.store') }}" method="POST" id="campaignForm">
        @csrf
        
        <!-- Progress Steps -->
        <div class="row mb-4">
            <div class="col-12">
                <div class="wizard-steps">
                    <div class="step active" data-step="1">
                        <div class="step-number">1</div>
                        <div class="step-label">{{ __('Basics') }}</div>
                    </div>
                    <div class="step" data-step="2">
                        <div class="step-number">2</div>
                        <div class="step-label">{{ __('Recipients') }}</div>
                    </div>
                    <div class="step" data-step="3">
                        <div class="step-number">3</div>
                        <div class="step-label">{{ __('Template') }}</div>
                    </div>
                    <div class="step" data-step="4">
                        <div class="step-number">4</div>
                        <div class="step-label">{{ __('Sender') }}</div>
                    </div>
                    <div class="step" data-step="5">
                        <div class="step-number">5</div>
                        <div class="step-label">{{ __('Schedule') }}</div>
                    </div>
                    <div class="step" data-step="6">
                        <div class="step-number">6</div>
                        <div class="step-label">{{ __('Review') }}</div>
                    </div>
                </div>
            </div>
        </div>

        <div class="card">
            <div class="card-body">
                <!-- Step 1: Basics -->
                <div class="wizard-content" data-step="1">
                    <h5>{{ __('Campaign Basics') }}</h5>
                    <hr>
                    
                    <div class="row">
                        <div class="col-md-8">
                            <div class="form-group">
                                <label for="name" class="required">{{ __('Campaign Name') }}</label>
                                <input type="text" name="name" id="name" class="form-control @error('name') is-invalid @enderror" 
                                       value="{{ old('name') }}" required placeholder="{{ __('e.g., Summer Sale 2024') }}">
                                @error('name')
                                <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                                <small class="form-text text-muted">{{ __('Internal name for your reference') }}</small>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="form-group">
                                <label for="category">{{ __('Category') }}</label>
                                <select name="category" id="category" class="form-control @error('category') is-invalid @enderror">
                                    <option value="newsletter" {{ old('category') == 'newsletter' ? 'selected' : '' }}>
                                        {{ __('Newsletter') }}
                                    </option>
                                    <option value="promotion" {{ old('category') == 'promotion' ? 'selected' : '' }}>
                                        {{ __('Promotion') }}
                                    </option>
                                    <option value="product_update" {{ old('category') == 'product_update' ? 'selected' : '' }}>
                                        {{ __('Product Update') }}
                                    </option>
                                    <option value="event" {{ old('category') == 'event' ? 'selected' : '' }}>
                                        {{ __('Event') }}
                                    </option>
                                    <option value="announcement" {{ old('category') == 'announcement' ? 'selected' : '' }}>
                                        {{ __('Announcement') }}
                                    </option>
                                    <option value="other" {{ old('category') == 'other' ? 'selected' : '' }}>
                                        {{ __('Other') }}
                                    </option>
                                </select>
                                @error('category')
                                <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-md-12">
                            <div class="form-group">
                                <label for="description">{{ __('Description') }}</label>
                                <textarea name="description" id="description" rows="3" 
                                          class="form-control @error('description') is-invalid @enderror" 
                                          placeholder="{{ __('Describe the purpose of this campaign...') }}">{{ old('description') }}</textarea>
                                @error('description')
                                <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="tags">{{ __('Tags') }}</label>
                                <input type="text" name="tags" id="tags" class="form-control tag-input" 
                                       value="{{ old('tags') }}" placeholder="{{ __('Add tags separated by commas') }}">
                                <small class="form-text text-muted">{{ __('For organizing and filtering campaigns') }}</small>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="language">{{ __('Language') }}</label>
                                <select name="language" id="language" class="form-control">
                                    <option value="en" {{ old('language') == 'en' ? 'selected' : '' }}>English</option>
                                    <option value="ne" {{ old('language') == 'ne' ? 'selected' : '' }}>Nepali</option>
                                    <option value="hi" {{ old('language') == 'hi' ? 'selected' : '' }}>Hindi</option>
                                    <option value="bn" {{ old('language') == 'bn' ? 'selected' : '' }}>Bengali</option>
                                    <option value="si" {{ old('language') == 'si' ? 'selected' : '' }}>Sinhala</option>
                                </select>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Step 2: Recipients -->
                <div class="wizard-content d-none" data-step="2">
                    <h5>{{ __('Select Recipients') }}</h5>
                    <hr>
                    
                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label>{{ __('Include Country Groups') }}</label>
                                <div class="border rounded p-3" style="max-height: 200px; overflow-y: auto;">
                                    @foreach($countryGroups as $group)
                                    <div class="form-check">
                                        <input type="checkbox" name="country_groups[]" value="{{ $group->id }}" 
                                               id="cg_{{ $group->id }}" class="form-check-input recipient-selector"
                                               {{ in_array($group->id, old('country_groups', [])) ? 'checked' : '' }}>
                                        <label for="cg_{{ $group->id }}" class="form-check-label">
                                            {{ $group->name }} ({{ number_format($group->subscribers_count) }})
                                        </label>
                                    </div>
                                    @endforeach
                                </div>
                                <small class="form-text text-muted">
                                    {{ __('Total selected: ') }}<span id="countryCount">0</span>
                                </small>
                            </div>
                        </div>

                        <div class="col-md-6">
                            <div class="form-group">
                                <label>{{ __('Include Custom Groups') }}</label>
                                <div class="border rounded p-3" style="max-height: 200px; overflow-y: auto;">
                                    @foreach($customGroups as $group)
                                    <div class="form-check">
                                        <input type="checkbox" name="custom_groups[]" value="{{ $group->id }}" 
                                               id="ug_{{ $group->id }}" class="form-check-input recipient-selector"
                                               {{ in_array($group->id, old('custom_groups', [])) ? 'checked' : '' }}>
                                        <label for="ug_{{ $group->id }}" class="form-check-label">
                                            {{ $group->name }} ({{ number_format($group->subscribers_count) }})
                                        </label>
                                    </div>
                                    @endforeach
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="row mt-3">
                        <div class="col-md-12">
                            <div class="form-group">
                                <label>{{ __('Include Segments') }}</label>
                                <select name="segments[]" id="segments" class="form-control select2-multiple" multiple>
                                    @foreach($segments as $segment)
                                    <option value="{{ $segment->id }}" 
                                            {{ in_array($segment->id, old('segments', [])) ? 'selected' : '' }}>
                                        {{ $segment->name }} ({{ number_format($segment->subscribers_count) }})
                                    </option>
                                    @endforeach
                                </select>
                                <small class="form-text text-muted">{{ __('Dynamic segments will be calculated at send time') }}</small>
                            </div>
                        </div>
                    </div>

                    <div class="row mt-3">
                        <div class="col-md-12">
                            <div class="alert alert-info">
                                <i class="fas fa-info-circle"></i>
                                <strong>{{ __('Estimated Recipients:') }}</strong>
                                <span id="estimatedRecipients" class="font-weight-bold">0</span>
                                <br>
                                <small>{{ __('This is an estimate. Final count will be calculated before sending.') }}</small>
                            </div>
                        </div>
                    </div>

                    <hr>

                    <h6>{{ __('Exclusions') }}</h6>
                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label>{{ __('Exclude Groups') }}</label>
                                <select name="exclude_groups[]" class="form-control select2-multiple" multiple>
                                    @foreach($allGroups as $group)
                                    <option value="{{ $group->id }}" 
                                            {{ in_array($group->id, old('exclude_groups', [])) ? 'selected' : '' }}>
                                        {{ $group->name }}
                                    </option>
                                    @endforeach
                                </select>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <div class="form-check mt-4">
                                    <input type="checkbox" name="exclude_previous_recipients" id="exclude_previous" 
                                           value="1" class="form-check-input" 
                                           {{ old('exclude_previous_recipients') ? 'checked' : '' }}>
                                    <label for="exclude_previous" class="form-check-label">
                                        {{ __('Exclude people who received previous campaigns') }}
                                    </label>
                                </div>
                                <div class="form-check">
                                    <input type="checkbox" name="exclude_unsubscribed" id="exclude_unsubscribed" 
                                           value="1" class="form-check-input" checked disabled>
                                    <label for="exclude_unsubscribed" class="form-check-label">
                                        {{ __('Always exclude unsubscribed contacts') }}
                                    </label>
                                </div>
                                <div class="form-check">
                                    <input type="checkbox" name="exclude_bounced" id="exclude_bounced" 
                                           value="1" class="form-check-input" checked disabled>
                                    <label for="exclude_bounced" class="form-check-label">
                                        {{ __('Always exclude bounced addresses') }}
                                    </label>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Step 3: Template -->
                <div class="wizard-content d-none" data-step="3">
                    <h5>{{ __('Email Content') }}</h5>
                    <hr>
                    
                    <div class="row">
                        <div class="col-md-4">
                            <div class="form-group">
                                <label for="template_id">{{ __('Use Template') }}</label>
                                <select name="template_id" id="template_id" class="form-control">
                                    <option value="">{{ __('Start from scratch') }}</option>
                                    @foreach($templates as $template)
                                    <option value="{{ $template->id }}" 
                                            {{ old('template_id') == $template->id ? 'selected' : '' }}>
                                        {{ $template->name }}
                                    </option>
                                    @endforeach
                                </select>
                                <small class="form-text text-muted">
                                    <a href="#" data-toggle="modal" data-target="#templatePreviewModal">
                                        {{ __('Preview templates') }}
                                    </a>
                                </small>
                            </div>
                        </div>
                        <div class="col-md-8">
                            <div class="form-group">
                                <label>{{ __('Or paste HTML') }}</label>
                                <textarea name="html_content" id="htmlContent" rows="10" 
                                          class="form-control font-monospace @error('html_content') is-invalid @enderror" 
                                          placeholder="{{ __('Paste your HTML email content here...') }}">{{ old('html_content') }}</textarea>
                                @error('html_content')
                                <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-md-12">
                            <div class="form-group">
                                <label for="plain_text">{{ __('Plain Text Version') }}</label>
                                <textarea name="plain_text" id="plainText" rows="5" 
                                          class="form-control @error('plain_text') is-invalid @enderror" 
                                          placeholder="{{ __('Plain text fallback for email clients that don\'t support HTML...') }}">{{ old('plain_text') }}</textarea>
                                @error('plain_text')
                                <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                                <small class="form-text text-muted">
                                    {{ __('Recommended for better deliverability') }}
                                </small>
                            </div>
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-md-12">
                            <div class="alert alert-info">
                                <strong>{{ __('Available Merge Tags:') }}</strong>
                                <code>{{ '{{first_name}}' }}</code>
                                <code>{{ '{{last_name}}' }}</code>
                                <code>{{ '{{full_name}}' }}</code>
                                <code>{{ '{{email}}' }}</code>
                                <code>{{ '{{company_name}}' }}</code>
                                <code>{{ '{{country}}' }}</code>
                                <code>{{ '{{unsubscribe_url}}' }}</code>
                                <code>{{ '{{preference_center_url}}' }}</code>
                                <code>{{ '{{browser_view_url}}' }}</code>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Step 4: Sender -->
                <div class="wizard-content d-none" data-step="4">
                    <h5>{{ __('Sender Configuration') }}</h5>
                    <hr>
                    
                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="sender_name" class="required">{{ __('Sender Name') }}</label>
                                <input type="text" name="sender_name" id="sender_name" 
                                       class="form-control @error('sender_name') is-invalid @enderror" 
                                       value="{{ old('sender_name', config('mail.from.name')) }}" required>
                                @error('sender_name')
                                <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="sender_email" class="required">{{ __('Sender Email') }}</label>
                                <input type="email" name="sender_email" id="sender_email" 
                                       class="form-control @error('sender_email') is-invalid @enderror" 
                                       value="{{ old('sender_email', config('mail.from.address')) }}" required>
                                @error('sender_email')
                                <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                                <small class="form-text text-muted">
                                    {{ __('Must be a verified sender identity') }}
                                </small>
                            </div>
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="reply_to">{{ __('Reply-To Email') }}</label>
                                <input type="email" name="reply_to" id="reply_to" 
                                       class="form-control @error('reply_to') is-invalid @enderror" 
                                       value="{{ old('reply_to') }}" 
                                       placeholder="{{ __('Defaults to sender email') }}">
                                @error('reply_to')
                                <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="provider">{{ __('Email Provider') }}</label>
                                <select name="provider" id="provider" class="form-control">
                                    <option value="" {{ old('provider') == '' ? 'selected' : '' }}>
                                        {{ __('Auto (use default)') }}
                                    </option>
                                    <option value="resend" {{ old('provider') == 'resend' ? 'selected' : '' }}>
                                        Resend
                                    </option>
                                    <option value="ses" {{ old('provider') == 'ses' ? 'selected' : '' }}>
                                        Amazon SES
                                    </option>
                                    <option value="smtp" {{ old('provider') == 'smtp' ? 'selected' : '' }}>
                                        SMTP
                                    </option>
                                </select>
                                <small class="form-text text-muted">
                                    {{ __('Leave empty to use regional defaults') }}
                                </small>
                            </div>
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-md-12">
                            <div class="form-group">
                                <label for="subject" class="required">{{ __('Email Subject') }}</label>
                                <input type="text" name="subject" id="subject" 
                                       class="form-control @error('subject') is-invalid @enderror" 
                                       value="{{ old('subject') }}" required 
                                       placeholder="{{ __('Enter your email subject line...') }}">
                                @error('subject')
                                <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                                <small class="form-text text-muted">
                                    {{ __('Character count: ') }}<span id="subjectCount">0</span>/100 
                                    {{ __('(recommended)') }}
                                </small>
                            </div>
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-md-12">
                            <div class="form-group">
                                <label for="preview_text">{{ __('Preview Text') }}</label>
                                <input type="text" name="preview_text" id="preview_text" 
                                       class="form-control @error('preview_text') is-invalid @enderror" 
                                       value="{{ old('preview_text') }}" maxlength="200"
                                       placeholder="{{ __('Short summary shown in inbox preview...') }}">
                                @error('preview_text')
                                <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                                <small class="form-text text-muted">
                                    {{ __('The snippet text shown after subject in email clients') }}
                                </small>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Step 5: Schedule -->
                <div class="wizard-content d-none" data-step="5">
                    <h5>{{ __('Schedule & Settings') }}</h5>
                    <hr>
                    
                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label>{{ __('Send Time') }}</label>
                                <div class="btn-group btn-group-toggle w-100" data-toggle="buttons">
                                    <label class="btn btn-outline-primary active">
                                        <input type="radio" name="send_type" value="now" autocomplete="off" checked>
                                        {{ __('Send Immediately') }}
                                    </label>
                                    <label class="btn btn-outline-primary">
                                        <input type="radio" name="send_type" value="schedule" autocomplete="off">
                                        {{ __('Schedule') }}
                                    </label>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group d-none" id="scheduleDateTime">
                                <label for="scheduled_at">{{ __('Scheduled Date & Time') }}</label>
                                <input type="datetime-local" name="scheduled_at" id="scheduled_at" 
                                       class="form-control @error('scheduled_at') is-invalid @enderror" 
                                       value="{{ old('scheduled_at') }}" min="{{ now()->addMinutes(5)->format('Y-m-d\TH:i') }}">
                                @error('scheduled_at')
                                <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                                <small class="form-text text-muted">
                                    {{ __('Minimum 5 minutes from now') }}
                                </small>
                            </div>
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="timezone">{{ __('Timezone') }}</label>
                                <select name="timezone" id="timezone" class="form-control">
                                    @foreach($timezones as $tz)
                                    <option value="{{ $tz }}" {{ old('timezone') == $tz ? 'selected' : '' }}>
                                        {{ $tz }}
                                    </option>
                                    @endforeach
                                </select>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label>{{ __('Tracking Options') }}</label>
                                <div class="form-check">
                                    <input type="checkbox" name="track_opens" id="track_opens" value="1" 
                                           class="form-check-input" {{ old('track_opens', true) ? 'checked' : '' }}>
                                    <label for="track_opens" class="form-check-label">
                                        {{ __('Track opens') }}
                                    </label>
                                </div>
                                <div class="form-check">
                                    <input type="checkbox" name="track_clicks" id="track_clicks" value="1" 
                                           class="form-check-input" {{ old('track_clicks', true) ? 'checked' : '' }}>
                                    <label for="track_clicks" class="form-check-label">
                                        {{ __('Track clicks') }}
                                    </label>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label>{{ __('UTM Parameters') }}</label>
                                <div class="form-check">
                                    <input type="checkbox" name="add_utm_params" id="add_utm" value="1" 
                                           class="form-check-input" {{ old('add_utm_params') ? 'checked' : '' }}>
                                    <label for="add_utm" class="form-check-label">
                                        {{ __('Add UTM parameters to links') }}
                                    </label>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-6" id="utmFields" style="display: none;">
                            <div class="row">
                                <div class="col-md-6">
                                    <input type="text" name="utm_source" class="form-control form-control-sm mb-2" 
                                           placeholder="UTM Source" value="{{ old('utm_source', 'neogiga') }}">
                                </div>
                                <div class="col-md-6">
                                    <input type="text" name="utm_medium" class="form-control form-control-sm mb-2" 
                                           placeholder="UTM Medium" value="{{ old('utm_medium', 'email') }}">
                                </div>
                                <div class="col-md-12">
                                    <input type="text" name="utm_campaign" class="form-control form-control-sm" 
                                           placeholder="UTM Campaign" value="{{ old('utm_campaign') }}">
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Step 6: Review -->
                <div class="wizard-content d-none" data-step="6">
                    <h5>{{ __('Review & Launch') }}</h5>
                    <hr>
                    
                    <div class="review-summary">
                        <div class="row">
                            <div class="col-md-6">
                                <h6>{{ __('Campaign Details') }}</h6>
                                <table class="table table-sm">
                                    <tr>
                                        <th width="40%">{{ __('Name:') }}</th>
                                        <td><span data-review="name"></span></td>
                                    </tr>
                                    <tr>
                                        <th>{{ __('Category:') }}</th>
                                        <td><span data-review="category"></span></td>
                                    </tr>
                                    <tr>
                                        <th>{{ __('Subject:') }}</th>
                                        <td><span data-review="subject"></span></td>
                                    </tr>
                                    <tr>
                                        <th>{{ __('From:') }}</th>
                                        <td><span data-review="sender_name"></span> &lt;<span data-review="sender_email"></span>&gt;</td>
                                    </tr>
                                </table>
                            </div>
                            <div class="col-md-6">
                                <h6>{{ __('Recipients') }}</h6>
                                <table class="table table-sm">
                                    <tr>
                                        <th width="40%">{{ __('Country Groups:') }}</th>
                                        <td><span data-review="country_groups"></span></td>
                                    </tr>
                                    <tr>
                                        <th>{{ __('Custom Groups:') }}</th>
                                        <td><span data-review="custom_groups"></span></td>
                                    </tr>
                                    <tr>
                                        <th>{{ __('Estimated Total:') }}</th>
                                        <td><strong class="text-primary"><span data-review="total_recipients"></span></strong></td>
                                    </tr>
                                </table>
                            </div>
                        </div>

                        <div class="alert alert-warning">
                            <i class="fas fa-exclamation-triangle"></i>
                            <strong>{{ __('Important:') }}</strong>
                            <ul class="mb-0 mt-2">
                                <li>{{ __('Unsubscribed, bounced, and complained contacts will be automatically excluded') }}</li>
                                <li>{{ __('Campaign cannot be cancelled once sending starts') }}</li>
                                <li>{{ __('Ensure your sender email is verified before launching') }}</li>
                                <li>{{ __('Test emails are recommended before sending to all recipients') }}</li>
                            </ul>
                        </div>

                        <div class="form-check mt-3">
                            <input type="checkbox" name="confirm_send" id="confirm_send" value="1" 
                                   class="form-check-input" required>
                            <label for="confirm_send" class="form-check-label">
                                {{ __('I confirm that I have reviewed all settings and want to launch this campaign') }}
                            </label>
                        </div>
                    </div>
                </div>
            </div>

            <div class="card-footer d-flex justify-content-between">
                <button type="button" class="btn btn-secondary" id="prevBtn" disabled>
                    <i class="fas fa-arrow-left"></i> {{ __('Previous') }}
                </button>
                <div>
                    <button type="button" class="btn btn-outline-primary" id="testEmailBtn">
                        <i class="fas fa-flask"></i> {{ __('Send Test Email') }}
                    </button>
                    <button type="submit" class="btn btn-success" id="launchBtn" disabled>
                        <i class="fas fa-rocket"></i> {{ __('Launch Campaign') }}
                    </button>
                </div>
            </div>
        </div>
    </form>
</div>

<!-- Template Preview Modal -->
<div class="modal fade" id="templatePreviewModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">{{ __('Email Templates') }}</h5>
                <button type="button" class="close" data-dismiss="modal">&times;</button>
            </div>
            <div class="modal-body">
                <div class="row">
                    @foreach($templates as $template)
                    <div class="col-md-6 mb-3">
                        <div class="card template-card" onclick="selectTemplate({{ $template->id }})">
                            <div class="card-body text-center">
                                <h6>{{ $template->name }}</h6>
                                <small class="text-muted">{{ $template->description }}</small>
                            </div>
                        </div>
                    </div>
                    @endforeach
                </div>
            </div>
        </div>
    </div>
</div>

@push('styles')
<style>
.wizard-steps {
    display: flex;
    justify-content: space-between;
    margin-bottom: 2rem;
}
.step {
    flex: 1;
    text-align: center;
    position: relative;
    padding: 1rem;
}
.step:not(:last-child)::after {
    content: '';
    position: absolute;
    top: 30px;
    left: 60%;
    width: 80%;
    height: 2px;
    background: #e9ecef;
}
.step.active:not(:last-child)::after {
    background: #4e73df;
}
.step-number {
    width: 50px;
    height: 50px;
    border-radius: 50%;
    background: #e9ecef;
    color: #6c757d;
    display: flex;
    align-items: center;
    justify-content: center;
    margin: 0 auto 0.5rem;
    font-weight: bold;
    font-size: 1.2rem;
}
.step.active .step-number {
    background: #4e73df;
    color: white;
}
.step.completed .step-number {
    background: #1cc88a;
    color: white;
}
.wizard-content {
    animation: fadeIn 0.3s ease-in-out;
}
@keyframes fadeIn {
    from { opacity: 0; }
    to { opacity: 1; }
}
.template-card {
    cursor: pointer;
    transition: all 0.2s;
}
.template-card:hover {
    transform: translateY(-2px);
    box-shadow: 0 4px 8px rgba(0,0,0,0.1);
    border-color: #4e73df;
}
.required::after {
    content: ' *';
    color: red;
}
</style>
@endpush

@push('scripts')
<script>
let currentStep = 1;
const totalSteps = 6;

// Navigation
$('#prevBtn').click(function() {
    if (currentStep > 1) {
        goToStep(currentStep - 1);
    }
});

function goToStep(step) {
    // Validate current step
    if (step > currentStep && !validateStep(currentStep)) return;
    
    // Update steps UI
    $('.wizard-content').addClass('d-none');
    $(`.wizard-content[data-step="${step}"]`).removeClass('d-none');
    
    $('.step').removeClass('active completed');
    for (let i = 1; i < step; i++) {
        $(`.step[data-step="${i}"]`).addClass('completed');
    }
    $(`.step[data-step="${step}"]`).addClass('active');
    
    currentStep = step;
    $('#prevBtn').prop('disabled', step === 1);
    
    if (step === 6) {
        updateReview();
    }
}

function validateStep(step) {
    let valid = true;
    const content = $(`.wizard-content[data-step="${step}"]`);
    
    content.find('[required]').each(function() {
        if (!$(this).val()) {
            $(this).addClass('is-invalid');
            valid = false;
        } else {
            $(this).removeClass('is-invalid');
        }
    });
    
    return valid;
}

// Update review
function updateReview() {
    $('[data-review="name"]').text($('#name').val());
    $('[data-review="category"]').text($('#category option:selected').text());
    $('[data-review="subject"]').text($('#subject').val());
    $('[data-review="sender_name"]').text($('#sender_name').val());
    $('[data-review="sender_email"]').text($('#sender_email').val());
    
    const countryGroups = $('input[name="country_groups[]"]:checked').length;
    const customGroups = $('input[name="custom_groups[]"]:checked').length;
    $('[data-review="country_groups"]').text(countryGroups + ' selected');
    $('[data-review="custom_groups"]').text(customGroups + ' selected');
    $('[data-review="total_recipients"]').text($('#estimatedRecipients').text());
}

// Subject character count
$('#subject').on('input', function() {
    $('#subjectCount').text(this.value.length);
});

// Schedule toggle
$('input[name="send_type"]').change(function() {
    if (this.value === 'schedule') {
        $('#scheduleDateTime').removeClass('d-none');
    } else {
        $('#scheduleDateTime').addClass('d-none');
    }
});

// UTM toggle
$('#add_utm').change(function() {
    $('#utmFields').toggle(this.checked);
});

// Recipient count update
$('.recipient-selector').change(updateRecipientCount);
function updateRecipientCount() {
    const count = $('.recipient-selector:checked').length;
    $('#countryCount').text(count);
    // In production, AJAX call to get actual estimated count
    $('#estimatedRecipients').text('Calculating...');
}

// Confirm checkbox
$('#confirm_send').change(function() {
    $('#launchBtn').prop('disabled', !this.checked);
});

// Form submission
$('#campaignForm').submit(function(e) {
    if (!$('#confirm_send').is(':checked')) {
        e.preventDefault();
        alert('{{ __("Please confirm you want to launch this campaign") }}');
        return false;
    }
    
    if (!confirm('{{ __("Are you sure you want to launch this campaign? This action cannot be undone.") }}')) {
        e.preventDefault();
        return false;
    }
});

// Test email
$('#testEmailBtn').click(function() {
    const email = prompt('{{ __("Enter test email address:") }}');
    if (email) {
        // AJAX call to send test
        alert('{{ __("Test email sent!") }}');
    }
});

// Initialize
updateRecipientCount();
</script>
@endpush
@endsection
