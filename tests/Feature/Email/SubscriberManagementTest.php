<?php

namespace Tests\Feature\Email;

use App\Models\EmailSubscriber;
use App\Models\EmailGroup;
use App\Models\EmailCampaign;
use App\Models\EmailSuppression;
use App\Models\EmailConsent;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;

class SubscriberManagementTest extends TestCase
{
    use RefreshDatabase;

    public function test_subscriber_email_normalization()
    {
        $subscriber = EmailSubscriber::create([
            'email' => '  TEST@Example.COM  ',
            'first_name' => 'John',
            'last_name' => 'Doe',
        ]);

        $this->assertEquals('test@example.com', $subscriber->email);
    }

    public function test_duplicate_email_prevention()
    {
        EmailSubscriber::create([
            'email' => 'test@example.com',
            'status' => 'subscribed',
        ]);

        $this->expectException(\Illuminate\Database\QueryException::class);

        EmailSubscriber::create([
            'email' => 'test@example.com',
            'status' => 'subscribed',
        ]);
    }

    public function test_subscriber_status_colors()
    {
        $subscribed = EmailSubscriber::make(['status' => 'subscribed']);
        $unsubscribed = EmailSubscriber::make(['status' => 'unsubscribed']);
        $bounced = EmailSubscriber::make(['status' => 'bounced']);

        $this->assertEquals('success', $subscribed->status_color);
        $this->assertEquals('danger', $unsubscribed->status_color);
        $this->assertEquals('warning', $bounced->status_color);
    }

    public function test_regional_assignment_by_country_code()
    {
        $nepalGroup = EmailGroup::create([
            'name' => 'Nepal',
            'country_code' => 'NP',
            'is_country_group' => true,
        ]);

        $subscriber = EmailSubscriber::create([
            'email' => 'nepali@example.com',
            'country_code' => 'NP',
        ]);

        $subscriber->groups()->attach($nepalGroup, [
            'assignment_source' => 'country_code',
            'is_primary' => true,
        ]);

        $this->assertTrue($subscriber->groups->contains($nepalGroup));
    }

    public function test_subscriber_cannot_be_added_to_multiple_country_groups_as_primary()
    {
        $nepalGroup = EmailGroup::create([
            'name' => 'Nepal',
            'country_code' => 'NP',
            'is_country_group' => true,
        ]);

        $indiaGroup = EmailGroup::create([
            'name' => 'India',
            'country_code' => 'IN',
            'is_country_group' => true,
        ]);

        $subscriber = EmailSubscriber::create([
            'email' => 'multi@example.com',
        ]);

        // Attach Nepal as primary
        $subscriber->groups()->attach($nepalGroup, [
            'assignment_source' => 'manual',
            'is_primary' => true,
        ]);

        // Try to attach India as primary - should update the previous
        $subscriber->groups()->attach($indiaGroup, [
            'assignment_source' => 'manual',
            'is_primary' => true,
        ]);

        // Reload and check
        $subscriber->refresh();
        $primaryGroups = $subscriber->groups()->wherePivot('is_primary', true)->get();
        
        $this->assertEquals(1, $primaryGroups->count());
    }

    public function test_engagement_score_calculation()
    {
        $subscriber = EmailSubscriber::create([
            'email' => 'engaged@example.com',
            'total_sent' => 100,
            'total_opened' => 80,
            'total_clicked' => 40,
            'last_opened_at' => now(),
            'last_clicked_at' => now(),
        ]);

        // Engagement score should be high for active subscriber
        $this->assertGreaterThanOrEqual(50, $subscriber->engagement_score);
    }

    public function test_unsubscribe_enforcement()
    {
        $subscriber = EmailSubscriber::create([
            'email' => 'unsubscribe@example.com',
            'status' => 'unsubscribed',
            'unsubscribed_at' => now(),
        ]);

        $this->assertFalse($subscriber->canReceiveMarketing());
    }

    public function test_suppression_enforcement()
    {
        $subscriber = EmailSubscriber::create([
            'email' => 'suppressed@example.com',
            'status' => 'suppressed',
        ]);

        EmailSuppression::create([
            'email' => 'suppressed@example.com',
            'reason' => 'complaint',
        ]);

        $this->assertFalse($subscriber->canReceiveMarketing());
    }

    public function test_consent_tracking()
    {
        $subscriber = EmailSubscriber::create([
            'email' => 'consent@example.com',
            'status' => 'subscribed',
        ]);

        EmailConsent::create([
            'subscriber_id' => $subscriber->id,
            'consent_type' => 'promotional',
            'status' => 'granted',
            'source' => 'web_form',
        ]);

        $this->assertTrue($subscriber->hasConsentFor('promotional'));
        $this->assertFalse($subscriber->hasConsentFor('newsletter'));
    }

    public function test_bulk_subscriber_creation()
    {
        $subscribers = [];
        for ($i = 0; $i < 100; $i++) {
            $subscribers[] = [
                'email' => "user{$i}@example.com",
                'first_name' => "User {$i}",
                'status' => 'subscribed',
                'created_at' => now(),
                'updated_at' => now(),
            ];
        }

        EmailSubscriber::insert($subscribers);

        $this->assertDatabaseCount('email_subscribers', 100);
    }

    public function test_subscriber_search()
    {
        EmailSubscriber::create([
            'email' => 'john.doe@example.com',
            'first_name' => 'John',
            'last_name' => 'Doe',
            'company_name' => 'Acme Corp',
        ]);

        EmailSubscriber::create([
            'email' => 'jane.smith@example.com',
            'first_name' => 'Jane',
            'last_name' => 'Smith',
            'company_name' => 'Tech Ltd',
        ]);

        // Search by email
        $results = EmailSubscriber::where('email', 'like', '%john%')->get();
        $this->assertEquals(1, $results->count());
        $this->assertEquals('john.doe@example.com', $results->first()->email);

        // Search by name
        $results = EmailSubscriber::where('first_name', 'Jane')->get();
        $this->assertEquals(1, $results->count());
    }

    public function test_country_group_assignment()
    {
        $group = EmailGroup::create([
            'name' => 'Bangladesh',
            'country_code' => 'BD',
            'is_country_group' => true,
        ]);

        $subscribers = [];
        for ($i = 0; $i < 10; $i++) {
            $subscribers[] = EmailSubscriber::create([
                'email' => "bd{$i}@example.com",
                'country_code' => 'BD',
            ]);
            
            $subscribers[$i]->groups()->attach($group, [
                'assignment_source' => 'country_code',
                'is_primary' => true,
            ]);
        }

        $this->assertEquals(10, $group->subscribers()->count());
    }

    public function test_subscriber_export_data()
    {
        $subscriber = EmailSubscriber::create([
            'email' => 'export@example.com',
            'first_name' => 'Export',
            'last_name' => 'Test',
            'company_name' => 'Export Corp',
            'country_code' => 'NP',
        ]);

        $data = $subscriber->toArray();

        $this->assertArrayHasKey('email', $data);
        $this->assertArrayHasKey('full_name', $data);
        $this->assertArrayHasKey('created_at', $data);
    }
}
