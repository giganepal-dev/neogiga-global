<?php

namespace App\Services\Marketing;

use App\Models\EmailMarketing\EmailSubscriber;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class ContactToCustomerConversionService
{
    /**
     * Convert a campaign contact to a customer account.
     * This is an explicit action, not automatic.
     */
    public function convertToCustomer(int $subscriberId, array $options = []): array
    {
        $subscriber = EmailSubscriber::findOrFail($subscriberId);

        // Check if already linked to a user
        if ($subscriber->user_id) {
            return [
                'success' => false,
                'action' => 'already_linked',
                'message' => 'This contact is already linked to customer account #' . $subscriber->user_id,
                'user_id' => $subscriber->user_id,
            ];
        }

        // Check if user exists with this email
        $existingUser = DB::table('users')
            ->where('email', $subscriber->email)
            ->first();

        if ($existingUser) {
            // Link existing user
            return $this->linkExistingUser($subscriber, $existingUser);
        }

        // Create new customer account
        return $this->createNewCustomer($subscriber, $options);
    }

    /**
     * Link an existing user to a campaign contact.
     */
    private function linkExistingUser(EmailSubscriber $subscriber, object $user): array
    {
        // Check if user already has a customer profile
        $customerId = DB::table('customer_profiles')
            ->where('user_id', $user->id)
            ->value('id');

        if (!$customerId) {
            // Create customer profile
            $customerId = DB::table('customer_profiles')->insertGetId([
                'user_id' => $user->id,
                'status' => 'active',
                'customer_type' => $subscriber->subscriber_type ?? 'personal_customer',
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        // Link subscriber to user
        $subscriber->update([
            'user_id' => $user->id,
            'status' => 'subscribed',
            'metadata' => array_merge($subscriber->metadata ?? [], [
                'conversion_type' => 'linked_existing_user',
                'converted_at' => now()->toISOString(),
                'converted_user_id' => $user->id,
            ]),
        ]);

        // Log the conversion
        $this->logConversion($subscriber->id, 'linked_existing', $user->id, $customerId);

        return [
            'success' => true,
            'action' => 'linked',
            'message' => 'Contact linked to existing customer account.',
            'user_id' => $user->id,
            'customer_id' => $customerId,
        ];
    }

    /**
     * Create a new customer account from a campaign contact.
     */
    private function createNewCustomer(EmailSubscriber $subscriber, array $options): array
    {
        // Generate a secure password
        $password = Str::random(16);

        // Create user account
        $userId = DB::table('users')->insertGetId([
            'name' => $subscriber->full_name ?? trim(($subscriber->first_name ?? '') . ' ' . ($subscriber->last_name ?? '')),
            'email' => $subscriber->email,
            'password' => Hash::make($password),
            'email_verified_at' => now(),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        // Create customer profile
        $customerId = DB::table('customer_profiles')->insertGetId([
            'user_id' => $userId,
            'status' => 'active',
            'customer_type' => $subscriber->subscriber_type ?? 'personal_customer',
            'company_name' => $subscriber->company_name,
            'phone' => $subscriber->phone,
            'country_id' => $subscriber->country_id,
            'marketplace_id' => $subscriber->marketplace_id,
            'preferred_language' => $subscriber->preferred_language,
            'preferred_currency' => $subscriber->preferred_currency,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        // Link subscriber to user
        $subscriber->update([
            'user_id' => $userId,
            'status' => 'subscribed',
            'metadata' => array_merge($subscriber->metadata ?? [], [
                'conversion_type' => 'created_new_customer',
                'converted_at' => now()->toISOString(),
                'converted_user_id' => $userId,
            ]),
        ]);

        // Log the conversion
        $this->logConversion($subscriber->id, 'created_new', $userId, $customerId);

        return [
            'success' => true,
            'action' => 'created',
            'message' => 'New customer account created and linked.',
            'user_id' => $userId,
            'customer_id' => $customerId,
            'password' => $password, // Return for admin to share securely
        ];
    }

    /**
     * Send account activation invitation to a campaign contact.
     */
    public function sendInvitation(int $subscriberId, array $options = []): array
    {
        $subscriber = EmailSubscriber::findOrFail($subscriberId);

        if ($subscriber->user_id) {
            return [
                'success' => false,
                'message' => 'This contact already has an account.',
            ];
        }

        // Generate invitation token
        $token = Str::random(60);
        $expiresAt = now()->addDays(7);

        DB::table('customer_invitations')->insert([
            'email' => $subscriber->email,
            'token' => Hash::make($token),
            'subscriber_id' => $subscriber->id,
            'invited_by' => $options['invited_by'] ?? null,
            'expires_at' => $expiresAt,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        // Update subscriber status
        $subscriber->update([
            'status' => 'pending',
            'metadata' => array_merge($subscriber->metadata ?? [], [
                'invitation_sent_at' => now()->toISOString(),
                'invitation_token_expires' => $expiresAt->toISOString(),
            ]),
        ]);

        return [
            'success' => true,
            'message' => 'Invitation sent.',
            'invitation_url' => url('/register?invite=' . $token),
            'expires_at' => $expiresAt,
        ];
    }

    /**
     * Complete invitation when recipient registers.
     */
    public function completeInvitation(string $token, int $userId): array
    {
        $invitation = DB::table('customer_invitations')
            ->where('token', $token)
            ->where('expires_at', '>', now())
            ->whereNull('used_at')
            ->first();

        if (!$invitation) {
            return [
                'success' => false,
                'message' => 'Invalid or expired invitation.',
            ];
        }

        // Find the subscriber
        $subscriber = EmailSubscriber::find($invitation->subscriber_id);

        if ($subscriber) {
            // Link subscriber to new user
            $subscriber->update([
                'user_id' => $userId,
                'status' => 'subscribed',
                'email_verified_at' => now(),
                'metadata' => array_merge($subscriber->metadata ?? [], [
                    'conversion_type' => 'invitation_completed',
                    'converted_at' => now()->toISOString(),
                    'invitation_token' => $invitation->token,
                ]),
            ]);
        }

        // Mark invitation as used
        DB::table('customer_invitations')
            ->where('id', $invitation->id)
            ->update([
                'used_at' => now(),
                'user_id' => $userId,
            ]);

        // Log the conversion
        $this->logConversion($subscriber?->id, 'invitation_completed', $userId, null);

        return [
            'success' => true,
            'message' => 'Account created and linked to campaign contact.',
        ];
    }

    /**
     * Get conversion status for a subscriber.
     */
    public function getConversionStatus(int $subscriberId): array
    {
        $subscriber = EmailSubscriber::findOrFail($subscriberId);

        $conversionLogs = DB::table('contact_conversion_logs')
            ->where('subscriber_id', $subscriberId)
            ->orderByDesc('created_at')
            ->get();

        return [
            'subscriber_id' => $subscriber->id,
            'email' => $subscriber->email,
            'status' => $subscriber->status,
            'user_id' => $subscriber->user_id,
            'has_account' => $subscriber->user_id !== null,
            'conversion_type' => $subscriber->metadata['conversion_type'] ?? null,
            'converted_at' => $subscriber->metadata['converted_at'] ?? null,
            'conversion_logs' => $conversionLogs,
        ];
    }

    private function logConversion(?int $subscriberId, string $type, ?int $userId, ?int $customerId): void
    {
        DB::table('contact_conversion_logs')->insert([
            'subscriber_id' => $subscriberId,
            'conversion_type' => $type,
            'user_id' => $userId,
            'customer_id' => $customerId,
            'converted_at' => now(),
            'created_at' => now(),
        ]);
    }
}
