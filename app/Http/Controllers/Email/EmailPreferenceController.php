<?php

namespace App\Http\Controllers\Email;

use App\Http\Controllers\Controller;
use App\Models\EmailSubscriber;
use App\Models\EmailSuppression;
use App\Models\EmailConsent;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\DB;

class EmailPreferenceController extends Controller
{
    public function show(Request $request, string $token)
    {
        try {
            // Decrypt token to get subscriber info
            $decrypted = Crypt::decryptString($token);
            $parts = explode(':', $decrypted);
            
            if (count($parts) !== 2) {
                abort(404, 'Invalid preference link');
            }

            [$subscriberId, $expiresAt] = $parts;

            // Check if token has expired
            if ($expiresAt && now()->timestamp > (int)$expiresAt) {
                abort(404, 'This preference link has expired');
            }

            $subscriber = EmailSubscriber::findOrFail($subscriberId);

            return view('email.preferences.show', compact('subscriber', 'token'));
            
        } catch (\Exception $e) {
            abort(404, 'Invalid or expired preference link');
        }
    }

    public function update(Request $request, string $token)
    {
        try {
            $decrypted = Crypt::decryptString($token);
            $parts = explode(':', $decrypted);
            
            if (count($parts) !== 2) {
                return redirect()->back()->with('error', 'Invalid preference link');
            }

            [$subscriberId] = $parts;
            $subscriber = EmailSubscriber::findOrFail($subscriberId);

            $validated = $request->validate([
                'first_name' => 'nullable|string|max:255',
                'last_name' => 'nullable|string|max:255',
                'company_name' => 'nullable|string|max:255',
                'preferred_language' => 'nullable|string|in:en,np,hi,bn,ta,te',
                'consent_transactional' => 'boolean',
                'consent_promotional' => 'boolean',
                'consent_newsletter' => 'boolean',
                'consent_product_updates' => 'boolean',
                'consent_regional_offers' => 'boolean',
            ]);

            DB::transaction(function () use ($subscriber, $validated, $request) {
                // Update personal info
                $subscriber->update(array_filter([
                    'first_name' => $validated['first_name'] ?? null,
                    'last_name' => $validated['last_name'] ?? null,
                    'company_name' => $validated['company_name'] ?? null,
                    'preferred_language' => $validated['preferred_language'] ?? null,
                ]));

                // Update consents
                $consentTypes = [
                    'transactional' => 'consent_transactional',
                    'promotional' => 'consent_promotional',
                    'newsletter' => 'consent_newsletter',
                    'product_updates' => 'consent_product_updates',
                    'regional_offers' => 'consent_regional_offers',
                ];

                foreach ($consentTypes as $type => $field) {
                    if (isset($validated[$field])) {
                        EmailConsent::updateOrCreate(
                            [
                                'subscriber_id' => $subscriber->id,
                                'consent_type' => $type,
                            ],
                            [
                                'status' => $validated[$field] ? 'granted' : 'denied',
                                'source' => 'preference_centre',
                                'ip_address' => $request->ip(),
                                'user_agent' => $request->userAgent(),
                            ]
                        );
                    }
                }
            });

            return redirect()->route('email.preferences.show', $token)
                ->with('success', 'Your preferences have been updated successfully.');

        } catch (\Exception $e) {
            return redirect()->back()->with('error', 'Failed to update preferences. Please try again.');
        }
    }

    public function unsubscribe(Request $request, string $token)
    {
        try {
            $decrypted = Crypt::decryptString($token);
            $parts = explode(':', $decrypted);
            
            if (count($parts) !== 2) {
                abort(404, 'Invalid unsubscribe link');
            }

            [$subscriberId] = $parts;
            $subscriber = EmailSubscriber::findOrFail($subscriberId);

            DB::transaction(function () use ($subscriber, $request) {
                // Update subscriber status
                $subscriber->update([
                    'status' => EmailSubscriber::STATUS_UNSUBSCRIBED,
                    'unsubscribed_at' => now(),
                ]);

                // Deny promotional consent
                EmailConsent::updateOrCreate(
                    [
                        'subscriber_id' => $subscriber->id,
                        'consent_type' => 'promotional',
                    ],
                    [
                        'status' => 'denied',
                        'source' => 'unsubscribe_link',
                        'ip_address' => $request->ip(),
                        'user_agent' => $request->userAgent(),
                    ]
                );

                // Add to suppression list
                EmailSuppression::firstOrCreate(
                    ['email' => $subscriber->email],
                    [
                        'reason' => 'user_unsubscribe',
                        'status' => 'active',
                        'source' => 'preference_centre',
                    ]
                );
            });

            return view('email.preferences.unsubscribed', compact('subscriber'));

        } catch (\Exception $e) {
            abort(500, 'Failed to process unsubscribe request');
        }
    }

    public function resubscribe(Request $request, string $token)
    {
        try {
            $decrypted = Crypt::decryptString($token);
            $parts = explode(':', $decrypted);
            
            if (count($parts) !== 2) {
                abort(404, 'Invalid resubscribe link');
            }

            [$subscriberId] = $parts;
            $subscriber = EmailSubscriber::findOrFail($subscriberId);

            // Only allow resubscribe if not hard bounced or complained
            if (in_array($subscriber->status, [EmailSubscriber::STATUS_BOUNCED, EmailSubscriber::STATUS_COMPLAINED])) {
                abort(403, 'Cannot resubscribe due to previous delivery issues');
            }

            DB::transaction(function () use ($subscriber, $request) {
                // Update subscriber status
                $subscriber->update([
                    'status' => EmailSubscriber::STATUS_SUBSCRIBED,
                    'subscribed_at' => now(),
                    'unsubscribed_at' => null,
                ]);

                // Grant promotional consent
                EmailConsent::updateOrCreate(
                    [
                        'subscriber_id' => $subscriber->id,
                        'consent_type' => 'promotional',
                    ],
                    [
                        'status' => 'granted',
                        'source' => 'resubscribe_link',
                        'ip_address' => $request->ip(),
                        'user_agent' => $request->userAgent(),
                    ]
                );

                // Remove from suppression list if present
                EmailSuppression::where('email', $subscriber->email)
                    ->where('reason', 'user_unsubscribe')
                    ->update(['status' => 'removed']);
            });

            return view('email.preferences.resubscribed', compact('subscriber'));

        } catch (\Exception $e) {
            abort(500, 'Failed to process resubscribe request');
        }
    }

    public static function generateToken(EmailSubscriber $subscriber, int $expiresInHours = 72): string
    {
        $expiresAt = now()->addHours($expiresInHours)->timestamp;
        $data = "{$subscriber->id}:{$expiresAt}";
        
        return Crypt::encryptString($data);
    }
}
