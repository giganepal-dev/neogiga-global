<?php

namespace App\Services;

use App\Models\EmailMarketing\EmailCampaign;
use App\Models\EmailMarketing\EmailCampaignRecipient;
use App\Models\EmailMarketing\EmailSubscriber;
use App\Models\EmailMarketing\EmailProviderConfig;
use App\Models\EmailMarketing\EmailSenderIdentity;
use App\Models\EmailMarketing\EmailGroup;
use App\Models\EmailMarketing\EmailSegment;
use App\Models\EmailMarketing\EmailTemplate;
use App\Models\EmailSuppression;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Config;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Mail\Mailable;
use Exception;

class CampaignSendingService
{
    protected array $providerCache = [];
    protected int $chunkSize = 500;
    
    /**
     * Prepare campaign recipients based on groups, segments, and exclusions
     */
    public function prepareRecipients(EmailCampaign $campaign): int
    {
        DB::transaction(function () use ($campaign) {
            // Clear existing recipients
            $campaign->recipients()->delete();
            
            $recipientQuery = EmailSubscriber::query()
                ->whereIn('status', ['subscribed', 'pending']);
            
            // Apply group filters
            if ($campaign->groups()->exists()) {
                $groupIds = $campaign->groups()->pluck('email_groups.id');
                $recipientQuery->whereHas('groups', function ($q) use ($groupIds) {
                    $q->whereIn('email_groups.id', $groupIds);
                });
            }
            
            // Apply segment filters
            if ($campaign->segments()->exists()) {
                $segmentConditions = $campaign->segments()->get();
                foreach ($segmentConditions as $segment) {
                    $this->applySegmentFilter($recipientQuery, $segment);
                }
            }
            
            // Apply exclusions
            if ($campaign->excludedGroups()->exists()) {
                $excludedGroupIds = $campaign->excludedGroups()->pluck('email_groups.id');
                $recipientQuery->whereDoesntHave('groups', function ($q) use ($excludedGroupIds) {
                    $q->whereIn('email_groups.id', $excludedGroupIds);
                });
            }
            
            // Exclude specific subscribers
            if ($campaign->excluded_subscribers) {
                $excludedIds = json_decode($campaign->excluded_subscribers, true) ?? [];
                if (!empty($excludedIds)) {
                    $recipientQuery->whereNotIn('id', $excludedIds);
                }
            }
            
            // Exclude previously sent recipients if configured
            if ($campaign->exclude_previous_recipients) {
                $recipientQuery->whereDoesntHave('sentCampaigns', function ($q) use ($campaign) {
                    $q->where('email_campaign_id', $campaign->id);
                });
            }
            
            // Exclude suppressed emails
            $suppressedEmails = EmailSuppression::whereIn('status', ['bounced', 'complained', 'unsubscribed', 'suppressed'])
                ->pluck('email')
                ->toArray();
            
            if (!empty($suppressedEmails)) {
                $recipientQuery->whereNotIn('email_normalized', array_map('strtolower', $suppressedEmails));
            }
            
            // Insert recipients in chunks
            $recipientQuery->chunk($this->chunkSize, function ($subscribers) use ($campaign) {
                $recipients = [];
                foreach ($subscribers as $subscriber) {
                    $recipients[] = [
                        'email_campaign_id' => $campaign->id,
                        'email_subscriber_id' => $subscriber->id,
                        'email' => $subscriber->email,
                        'status' => 'pending',
                        'created_at' => now(),
                        'updated_at' => now(),
                    ];
                }
                
                if (!empty($recipients)) {
                    EmailCampaignRecipient::insert($recipients);
                }
            });
        });
        
        return $campaign->recipients()->count();
    }
    
    /**
     * Apply segment filter conditions
     */
    protected function applySegmentFilter($query, EmailSegment $segment)
    {
        $conditions = json_decode($segment->conditions, true) ?? [];
        
        foreach ($conditions as $condition) {
            $field = $condition['field'] ?? null;
            $operator = $condition['operator'] ?? '=';
            $value = $condition['value'] ?? null;
            
            if (!$field) continue;
            
            switch ($field) {
                case 'country_code':
                    $query->where('country_code', $operator, $value);
                    break;
                case 'region_id':
                    $query->where('region_id', $operator, $value);
                    break;
                case 'subscriber_type':
                    $query->where('subscriber_type', $operator, $value);
                    break;
                case 'customer_type':
                    $query->where('customer_type', $operator, $value);
                    break;
                case 'language':
                    $query->where('preferred_language', $operator, $value);
                    break;
                case 'total_orders':
                    $query->where('total_orders', $operator, $value);
                    break;
                case 'last_order_date':
                    $query->whereDate('last_order_date', $operator, $value);
                    break;
                case 'engagement_score':
                    $query->where('engagement_score', $operator, $value);
                    break;
                case 'opened_last_30_days':
                    if ($value === true) {
                        $query->where('last_opened_at', '>=', now()->subDays(30));
                    } else {
                        $query->where(function ($q) {
                            $q->whereNull('last_opened_at')
                              ->orWhere('last_opened_at', '<', now()->subDays(30));
                        });
                    }
                    break;
            }
        }
    }
    
    /**
     * Get provider configuration for campaign
     */
    public function getProviderForCampaign(EmailCampaign $campaign): ?EmailProviderConfig
    {
        // Priority 1: Campaign-specific provider
        if ($campaign->provider_config_id) {
            $provider = EmailProviderConfig::find($campaign->provider_config_id);
            if ($provider && $provider->is_active) {
                return $provider;
            }
        }
        
        // Priority 2: Country-group provider
        $groups = $campaign->groups()->get();
        foreach ($groups as $group) {
            if ($group->provider_config_id) {
                $provider = EmailProviderConfig::find($group->provider_config_id);
                if ($provider && $provider->is_active) {
                    return $provider;
                }
            }
        }
        
        // Priority 3: Regional store provider
        if ($campaign->region_id) {
            $provider = EmailProviderConfig::where('region_id', $campaign->region_id)
                ->where('is_active', true)
                ->first();
            if ($provider) {
                return $provider;
            }
        }
        
        // Priority 4: Global default provider
        $provider = EmailProviderConfig::where('is_global_default', true)
            ->where('is_active', true)
            ->first();
        if ($provider) {
            return $provider;
        }
        
        // Priority 5: First active provider as fallback
        return EmailProviderConfig::where('is_active', true)->first();
    }
    
    /**
     * Configure mail settings for provider
     */
    public function configureProvider(EmailProviderConfig $provider): void
    {
        $cacheKey = "provider_config_{$provider->id}";
        
        if (isset($this->providerCache[$cacheKey])) {
            return;
        }
        
        switch ($provider->provider_type) {
            case 'resend':
                Config::set('mail.mailers.resend.api_key', $provider->getDecryptedApiKey());
                Config::set('mail.default', 'resend');
                break;
                
            case 'ses':
                Config::set('mail.mailers.ses.key', $provider->getDecryptedApiKey());
                Config::set('mail.mailers.ses.secret', $provider->getDecryptedSecret());
                Config::set('mail.mailers.ses.region', $provider->region);
                Config::set('mail.default', 'ses');
                break;
                
            case 'smtp':
            default:
                Config::set('mail.mailers.smtp.host', $provider->smtp_host);
                Config::set('mail.mailers.smtp.port', $provider->smtp_port);
                Config::set('mail.mailers.smtp.username', $provider->smtp_username);
                Config::set('mail.mailers.smtp.password', $provider->getDecryptedPassword());
                Config::set('mail.mailers.smtp.encryption', $provider->smtp_encryption);
                Config::set('mail.default', 'smtp');
                break;
        }
        
        // Set from address
        if ($provider->default_from_email) {
            Config::set('mail.from.address', $provider->default_from_email);
            Config::set('mail.from.name', $provider->default_from_name ?? config('app.name'));
        }
        
        $this->providerCache[$cacheKey] = true;
        
        // Purge mail manager to apply new config
        app('mail.manager')->purge(Config::get('mail.default'));
    }
    
    /**
     * Render email content with merge tags
     */
    public function renderContent(string $content, EmailSubscriber $subscriber, EmailCampaign $campaign): string
    {
        $replacements = [
            '{{first_name}}' => $subscriber->first_name ?? '',
            '{{last_name}}' => $subscriber->last_name ?? '',
            '{{full_name}}' => $subscriber->full_name ?? $subscriber->email,
            '{{company_name}}' => $subscriber->company_name ?? '',
            '{{email}}' => $subscriber->email,
            '{{country}}' => $subscriber->country?->name ?? '',
            '{{region}}' => $subscriber->region?->name ?? '',
            '{{preferred_language}}' => $subscriber->preferred_language ?? 'en',
            '{{campaign_name}}' => $campaign->name,
            '{{current_year}}' => date('Y'),
        ];
        
        // Generate secure unsubscribe URL
        $unsubscribeToken = bin2hex(random_bytes(32));
        $subscriber->update(['unsubscribe_token' => $unsubscribeToken]);
        $unsubscribeUrl = route('email.preferences.unsubscribe', [
            'token' => $unsubscribeToken,
            'email' => base64_encode($subscriber->email)
        ]);
        
        $preferenceUrl = route('email.preferences.show', [
            'token' => $unsubscribeToken,
            'email' => base64_encode($subscriber->email)
        ]);
        
        $replacements['{{unsubscribe_url}}'] = $unsubscribeUrl;
        $replacements['{{preference_center_url}}'] = $preferenceUrl;
        
        // Escape values to prevent HTML injection
        foreach ($replacements as &$value) {
            $value = htmlspecialchars($value, ENT_QUOTES | ENT_HTML5, 'UTF-8');
        }
        
        return str_replace(array_keys($replacements), array_values($replacements), $content);
    }
    
    /**
     * Send single email with retry logic
     */
    public function sendEmail(EmailCampaignRecipient $recipient, EmailCampaign $campaign): bool
    {
        try {
            $subscriber = $recipient->subscriber;
            
            // Recheck suppression before sending
            if ($this->isSuppressed($subscriber->email)) {
                $recipient->update(['status' => 'skipped', 'skipped_reason' => 'suppressed']);
                return false;
            }
            
            // Recheck subscription status
            if (!in_array($subscriber->status, ['subscribed', 'pending'])) {
                $recipient->update(['status' => 'skipped', 'skipped_reason' => 'not_subscribed']);
                return false;
            }
            
            // Recheck campaign status
            if (!in_array($campaign->status, ['sending', 'queued'])) {
                return false;
            }
            
            // Check if already sent
            if ($recipient->status === 'sent') {
                return true;
            }
            
            // Get provider
            $provider = $this->getProviderForCampaign($campaign);
            if (!$provider) {
                throw new Exception('No email provider configured');
            }
            
            // Configure provider
            $this->configureProvider($provider);
            
            // Get sender identity
            $senderIdentity = $campaign->senderIdentity ?: $provider->senderIdentity;
            $fromEmail = $senderIdentity?->email ?? $provider->default_from_email;
            $fromName = $senderIdentity?->name ?? $provider->default_from_name ?? config('app.name');
            
            // Render content
            $template = $campaign->template;
            $htmlContent = $template ? $template->body : $campaign->custom_html;
            $textContent = $campaign->plain_text_content;
            
            $htmlContent = $this->renderContent($htmlContent, $subscriber, $campaign);
            if ($textContent) {
                $textContent = $this->renderContent($textContent, $subscriber, $campaign);
            }
            
            // Build headers
            $headers = [
                'List-Unsubscribe' => '<' . route('email.preferences.unsubscribe', [
                    'token' => $subscriber->unsubscribe_token ?? bin2hex(random_bytes(32)),
                    'email' => base64_encode($subscriber->email)
                ]) . '>, <mailto:unsubscribe@' . parse_url(config('app.url'), PHP_URL_HOST) . '?subject=unsubscribe>',
                'List-Unsubscribe-Post' => 'List-Unsubscribe=One-Click',
                'X-Campaign-ID' => $campaign->id,
                'X-Subscriber-ID' => $subscriber->id,
                'X-Recipient-ID' => $recipient->id,
            ];
            
            // Send email
            Mail::send([], [], function ($message) use ($recipient, $campaign, $fromEmail, $fromName, $htmlContent, $textContent, $headers) {
                $message->to($recipient->email)
                    ->subject($campaign->subject)
                    ->from($fromEmail, $fromName)
                    ->html($htmlContent)
                    ->text($textContent ?: strip_tags($htmlContent))
                    ->setHeaders($headers);
                
                if ($campaign->reply_to_email) {
                    $message->replyTo($campaign->reply_to_email, $campaign->reply_to_name);
                }
            });
            
            // Update recipient status
            $recipient->update([
                'status' => 'sent',
                'sent_at' => now(),
            ]);
            
            // Update subscriber stats
            $subscriber->increment('total_sent');
            $subscriber->update(['last_email_sent_at' => now()]);
            
            // Update campaign stats
            $campaign->increment('emails_sent');
            
            Log::info('Campaign email sent', [
                'campaign_id' => $campaign->id,
                'recipient_id' => $recipient->id,
                'email' => $recipient->email,
            ]);
            
            return true;
            
        } catch (Exception $e) {
            Log::error('Campaign email failed', [
                'campaign_id' => $campaign->id,
                'recipient_id' => $recipient->id,
                'email' => $recipient->email,
                'error' => $e->getMessage(),
            ]);
            
            $recipient->update([
                'status' => 'failed',
                'failed_at' => now(),
                'failure_reason' => substr($e->getMessage(), 0, 500),
            ]);
            
            $campaign->increment('emails_failed');
            
            return false;
        }
    }
    
    /**
     * Check if email is suppressed
     */
    protected function isSuppressed(string $email): bool
    {
        $emailNormalized = strtolower(trim($email));
        
        return EmailSuppression::where('email_normalized', $emailNormalized)
            ->whereIn('status', ['bounced', 'complained', 'unsubscribed', 'suppressed'])
            ->exists();
    }
    
    /**
     * Validate campaign before sending
     */
    public function validateCampaign(EmailCampaign $campaign): array
    {
        $errors = [];
        $warnings = [];
        
        // Check basic fields
        if (!$campaign->subject) {
            $errors[] = 'Campaign subject is required';
        }
        
        if (!$campaign->custom_html && !$campaign->template) {
            $errors[] = 'Campaign content or template is required';
        }
        
        // Check recipients
        $recipientCount = $campaign->recipients()->count();
        if ($recipientCount === 0) {
            $errors[] = 'No recipients matched the campaign criteria';
        } else {
            $warnings[] = "Campaign will be sent to {$recipientCount} recipients";
        }
        
        // Check provider
        $provider = $this->getProviderForCampaign($campaign);
        if (!$provider) {
            $errors[] = 'No email provider configured for this campaign';
        } elseif (!$provider->is_verified && $provider->requires_verification) {
            $warnings[] = 'Email provider is not verified';
        }
        
        // Check sender identity
        if ($campaign->sender_identity_id) {
            $identity = EmailSenderIdentity::find($campaign->sender_identity_id);
            if (!$identity || !$identity->is_verified) {
                $warnings[] = 'Sender identity is not verified';
            }
        }
        
        // Check rate limits
        if ($provider && $provider->daily_limit) {
            $todaySent = EmailCampaignRecipient::whereDate('sent_at', today())
                ->whereHas('campaign', function ($q) use ($provider) {
                    $q->where('provider_config_id', $provider->id);
                })
                ->count();
            
            if ($todaySent >= $provider->daily_limit * 0.9) {
                $warnings[] = 'Approaching daily sending limit (' . $todaySent . '/' . $provider->daily_limit . ')';
            }
        }
        
        return [
            'valid' => empty($errors),
            'errors' => $errors,
            'warnings' => $warnings,
            'recipient_count' => $recipientCount,
        ];
    }
}
