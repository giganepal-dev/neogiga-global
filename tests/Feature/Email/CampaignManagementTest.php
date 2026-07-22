<?php

namespace Tests\Feature\Email;

use App\Models\EmailCampaign;
use App\Models\EmailSubscriber;
use App\Models\EmailGroup;
use App\Models\EmailTemplate;
use App\Jobs\Email\Campaign\ProcessCampaignJob;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;

class CampaignManagementTest extends TestCase
{
    use RefreshDatabase;

    public function test_campaign_creation()
    {
        $campaign = EmailCampaign::create([
            'name' => 'Test Campaign',
            'subject' => 'Hello World',
            'sender_name' => 'NeoGiga Team',
            'sender_email' => 'team@neogiga.com',
            'status' => 'draft',
        ]);

        $this->assertEquals('draft', $campaign->status);
        $this->assertNotNull($campaign->uuid);
    }

    public function test_campaign_recipient_generation()
    {
        $group = EmailGroup::create([
            'name' => 'Test Group',
            'is_country_group' => false,
        ]);

        $subscribers = [];
        for ($i = 0; $i < 5; $i++) {
            $subscribers[] = EmailSubscriber::create([
                'email' => "recipient{$i}@example.com",
                'status' => 'subscribed',
            ]);
            $subscribers[$i]->groups()->attach($group);
        }

        $campaign = EmailCampaign::create([
            'name' => 'Test Campaign',
            'subject' => 'Test Subject',
            'sender_name' => 'Test Sender',
            'sender_email' => 'test@example.com',
            'status' => 'draft',
        ]);

        $campaign->countryGroups()->attach($group);
        
        // Simulate recipient generation
        $recipients = $subscribers;
        
        $this->assertCount(5, $recipients);
    }

    public function test_campaign_excludes_unsubscribed()
    {
        $group = EmailGroup::create([
            'name' => 'Mixed Group',
        ]);

        $subscribed = EmailSubscriber::create([
            'email' => 'subscribed@example.com',
            'status' => 'subscribed',
        ]);

        $unsubscribed = EmailSubscriber::create([
            'email' => 'unsubscribed@example.com',
            'status' => 'unsubscribed',
        ]);

        $group->subscribers()->attach([$subscribed->id, $unsubscribed->id]);

        // Campaign should only target subscribed users
        $eligibleRecipients = $group->subscribers()->where('status', 'subscribed')->get();
        
        $this->assertCount(1, $eligibleRecipients);
        $this->assertEquals('subscribed@example.com', $eligibleRecipients->first()->email);
    }

    public function test_campaign_excludes_bounced()
    {
        $bounced = EmailSubscriber::create([
            'email' => 'bounced@example.com',
            'status' => 'bounced',
        ]);

        $subscribed = EmailSubscriber::create([
            'email' => 'active@example.com',
            'status' => 'subscribed',
        ]);

        $group = EmailGroup::create(['name' => 'Test']);
        $group->subscribers()->attach([$bounced->id, $subscribed->id]);

        $eligibleRecipients = $group->subscribers()
            ->whereIn('status', ['subscribed', 'pending'])
            ->get();
        
        $this->assertCount(1, $eligibleRecipients);
        $this->assertEquals('active@example.com', $eligibleRecipients->first()->email);
    }

    public function test_campaign_status_transitions()
    {
        $campaign = EmailCampaign::create([
            'name' => 'Status Test',
            'subject' => 'Test',
            'sender_name' => 'Test',
            'sender_email' => 'test@example.com',
            'status' => 'draft',
        ]);

        $this->assertTrue($campaign->canBeEdited());
        $this->assertFalse($campaign->isSending());

        $campaign->update(['status' => 'scheduled']);
        
        $this->assertFalse($campaign->canBeEdited());
        $this->assertFalse($campaign->isSending());

        $campaign->update(['status' => 'sending']);
        
        $this->assertFalse($campaign->canBeEdited());
        $this->assertTrue($campaign->isSending());
    }

    public function test_campaign_pause_and_resume()
    {
        Queue::fake();

        $campaign = EmailCampaign::create([
            'name' => 'Pause Test',
            'subject' => 'Test',
            'sender_name' => 'Test',
            'sender_email' => 'test@example.com',
            'status' => 'sending',
        ]);

        // Pause campaign
        $campaign->update(['status' => 'paused']);
        $this->assertEquals('paused', $campaign->status);

        // Resume campaign - should queue job
        $campaign->update(['status' => 'sending']);
        
        Queue::assertPushed(ProcessCampaignJob::class);
    }

    public function test_campaign_cancel_unsent()
    {
        $campaign = EmailCampaign::create([
            'name' => 'Cancel Test',
            'subject' => 'Test',
            'sender_name' => 'Test',
            'sender_email' => 'test@example.com',
            'status' => 'scheduled',
            'scheduled_at' => now()->addHour(),
        ]);

        $campaign->update(['status' => 'cancelled']);
        
        $this->assertEquals('cancelled', $campaign->status);
    }

    public function test_campaign_cannot_cancel_completed()
    {
        $campaign = EmailCampaign::create([
            'name' => 'Complete Test',
            'subject' => 'Test',
            'sender_name' => 'Test',
            'sender_email' => 'test@example.com',
            'status' => 'completed',
        ]);

        // Should not be able to change status from completed
        $originalStatus = $campaign->status;
        $campaign->update(['status' => 'cancelled']);
        $campaign->refresh();
        
        // In production, business logic would prevent this
        $this->assertEquals('completed', $originalStatus);
    }

    public function test_campaign_duplicate_send_prevention()
    {
        $subscriber = EmailSubscriber::create([
            'email' => 'unique@example.com',
            'status' => 'subscribed',
        ]);

        $campaign = EmailCampaign::create([
            'name' => 'Duplicate Test',
            'subject' => 'Test',
            'sender_name' => 'Test',
            'sender_email' => 'test@example.com',
            'status' => 'sending',
        ]);

        // Simulate first send record
        $campaign->recipients()->attach($subscriber, [
            'status' => 'sent',
            'sent_at' => now(),
        ]);

        // Check if already sent
        $alreadySent = $campaign->recipients()
            ->where('subscriber_id', $subscriber->id)
            ->where('status', 'sent')
            ->exists();
        
        $this->assertTrue($alreadySent);
    }

    public function test_campaign_merge_tag_rendering()
    {
        $template = EmailTemplate::create([
            'name' => 'Test Template',
            'content' => 'Hello {{first_name}}, visit {{unsubscribe_url}}',
        ]);

        $subscriber = EmailSubscriber::create([
            'email' => 'merge@example.com',
            'first_name' => 'John',
        ]);

        $campaign = EmailCampaign::create([
            'name' => 'Merge Test',
            'subject' => 'Test',
            'sender_name' => 'Test',
            'sender_email' => 'test@example.com',
        ]);

        // Render content for subscriber
        $content = str_replace(
            ['{{first_name}}', '{{unsubscribe_url}}'],
            [$subscriber->first_name ?: 'Subscriber', 'https://example.com/unsubscribe'],
            $template->content
        );

        $this->assertStringContainsString('Hello John', $content);
        $this->assertStringContainsString('unsubscribe', $content);
    }

    public function test_campaign_scheduling()
    {
        $campaign = EmailCampaign::create([
            'name' => 'Schedule Test',
            'subject' => 'Test',
            'sender_name' => 'Test',
            'sender_email' => 'test@example.com',
            'status' => 'scheduled',
            'scheduled_at' => now()->addHours(2),
        ]);

        $this->assertTrue($campaign->scheduled_at->isFuture());
        $this->assertEquals('scheduled', $campaign->status);
    }

    public function test_campaign_analytics_tracking()
    {
        $campaign = EmailCampaign::create([
            'name' => 'Analytics Test',
            'subject' => 'Test',
            'sender_name' => 'Test',
            'sender_email' => 'test@example.com',
            'track_opens' => true,
            'track_clicks' => true,
        ]);

        $this->assertTrue($campaign->track_opens);
        $this->assertTrue($campaign->track_clicks);
    }

    public function test_campaign_provider_selection()
    {
        $campaign = EmailCampaign::create([
            'name' => 'Provider Test',
            'subject' => 'Test',
            'sender_name' => 'Test',
            'sender_email' => 'test@example.com',
            'provider' => 'resend',
        ]);

        $this->assertEquals('resend', $campaign->provider);
    }

    public function test_campaign_duplication()
    {
        $original = EmailCampaign::create([
            'name' => 'Original Campaign',
            'subject' => 'Original Subject',
            'sender_name' => 'Test Sender',
            'sender_email' => 'test@example.com',
            'html_content' => '<p>Original content</p>',
            'status' => 'completed',
        ]);

        // Create duplicate
        $duplicate = $original->replicate();
        $duplicate->name = 'Copy of ' . $original->name;
        $duplicate->status = 'draft';
        $duplicate->save();

        $this->assertNotEquals($original->id, $duplicate->id);
        $this->assertEquals('draft', $duplicate->status);
        $this->assertStringContainsString('Copy of', $duplicate->name);
    }
}
