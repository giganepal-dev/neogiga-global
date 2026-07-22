<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

/**
 * Feature coverage for the three modules shipped in the 2026-07-09/10 cycles:
 * public RFQ intake, customer support/chat API, and product reviews
 * (customer submit + moderation gate). All run against the existing live
 * schemas — no module-private fixtures.
 */
class RfqSupportReviewsTest extends TestCase
{
    use RefreshDatabase;

    // ---- RFQ ----------------------------------------------------------------

    public function test_public_rfq_form_creates_request_items_and_history(): void
    {
        $response = $this->post('/rfq', [
            'contact_name' => 'QA Buyer',
            'contact_email' => 'qa-buyer@example.com',
            'company_name' => 'QA Labs',
            'country' => 'Nepal',
            'item_name' => 'LM358 OpAmp',
            'mpn' => 'LM358P',
            'quantity' => 250,
            'target_price' => 0.15,
            'message' => 'feature test',
        ]);

        $response->assertRedirect();

        $rfq = DB::table('rfq_requests')->orderByDesc('id')->first();
        $this->assertNotNull($rfq);
        $this->assertSame('open', $rfq->status);
        $this->assertSame('qa-buyer@example.com', $rfq->contact_email);
        $this->assertSame('Nepal', json_decode($rfq->meta)->country);
        $this->assertSame(1, DB::table('rfq_items')->where('rfq_request_id', $rfq->id)->count());
        $this->assertSame(1, DB::table('rfq_status_histories')->where('rfq_request_id', $rfq->id)->where('status', 'open')->count());
    }

    public function test_public_rfq_form_validates_required_fields(): void
    {
        $this->from('/rfq')->post('/rfq', ['contact_name' => 'No Email'])
            ->assertRedirect('/rfq')
            ->assertSessionHasErrors(['contact_email', 'item_name', 'quantity']);

        $this->assertSame(0, DB::table('rfq_requests')->count());
    }

    public function test_signed_in_rfq_prefills_profile_and_belongs_to_customer(): void
    {
        $user = User::factory()->create([
            'name' => 'Asha Engineer',
            'email' => 'asha@example.com',
        ]);
        DB::table('customer_profiles')->insert([
            'user_id' => $user->id,
            'email' => $user->email,
            'phone' => '+9779800000000',
            'company_name' => 'Asha Labs',
            'status' => 'active',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $this->actingAs($user)->get('/en/rfq')
            ->assertOk()
            ->assertSee('value="Asha Engineer"', false)
            ->assertSee('value="asha@example.com"', false)
            ->assertSee('value="+9779800000000"', false)
            ->assertSee('value="Asha Labs"', false);

        $this->actingAs($user)->post('/rfq', [
            'contact_name' => 'Asha Engineer',
            'contact_email' => 'asha@example.com',
            'contact_phone' => '+9779800000000',
            'company_name' => 'Asha Labs',
            'country' => 'Nepal',
            'item_name' => 'PCB assembly',
            'quantity' => 10,
        ])->assertRedirect();

        $this->assertDatabaseHas('rfq_requests', [
            'user_id' => $user->id,
            'contact_email' => 'asha@example.com',
        ]);
        $rfqId = DB::table('rfq_requests')->where('user_id', $user->id)->value('id');
        $this->assertDatabaseHas('rfq_status_histories', [
            'rfq_request_id' => $rfqId,
            'changed_by_user_id' => $user->id,
        ]);
    }

    // ---- Customer support / chat ---------------------------------------------

    public function test_support_ticket_lifecycle_create_reply_reopen_handoff(): void
    {
        [$user, $token] = $this->apiUser('support-qa@example.com');

        $create = $this->withToken($token)->postJson('/api/v1/support/tickets', [
            'subject' => 'Which battery fits?',
            'message' => 'Need an 18650 alternative',
            'category' => 'product_qa',
        ]);
        $create->assertCreated();
        $ticketId = DB::table('support_tickets')->where('user_id', $user->id)->value('id');
        $this->assertSame('open', DB::table('support_tickets')->where('id', $ticketId)->value('status'));

        // resolved thread reopens on customer reply
        DB::table('support_tickets')->where('id', $ticketId)->update(['status' => 'resolved']);
        $this->withToken($token)->postJson("/api/v1/support/tickets/{$ticketId}/messages", ['message' => 'Still stuck'])
            ->assertCreated();
        $this->assertSame('open', DB::table('support_tickets')->where('id', $ticketId)->value('status'));
        $this->assertSame(2, DB::table('support_ticket_messages')->where('support_ticket_id', $ticketId)->count());

        // AI-handoff placeholder escalates
        $this->withToken($token)->postJson("/api/v1/support/tickets/{$ticketId}/request-human")->assertOk();
        $row = DB::table('support_tickets')->where('id', $ticketId)->first();
        $this->assertTrue((bool) json_decode($row->metadata)->needs_human);
        $this->assertSame('high', $row->priority);
    }

    public function test_support_tickets_are_ownership_scoped(): void
    {
        [$owner, $ownerToken] = $this->apiUser('owner@example.com');
        [, $otherToken] = $this->apiUser('other@example.com');

        $this->withToken($ownerToken)->postJson('/api/v1/support/tickets', [
            'subject' => 'Private', 'message' => 'mine only',
        ])->assertCreated();
        $ticketId = DB::table('support_tickets')->where('user_id', $owner->id)->value('id');

        $this->withToken($otherToken)->getJson("/api/v1/support/tickets/{$ticketId}")->assertNotFound();
        $this->withToken($ownerToken)->getJson("/api/v1/support/tickets/{$ticketId}")->assertOk();
    }

    // ---- Product reviews -------------------------------------------------------

    public function test_review_submission_is_pending_until_moderated(): void
    {
        [, $token] = $this->apiUser('reviewer@example.com');
        $productId = $this->product();

        $this->withToken($token)->postJson("/api/v1/products/{$productId}/reviews", [
            'rating' => 5, 'title' => 'Solid part', 'body' => 'Works as specced.',
        ])->assertCreated();

        $this->assertSame('pending', DB::table('product_reviews')->where('product_id', $productId)->value('status'));

        // hidden from the public endpoint while pending
        $this->getJson("/api/v1/products/{$productId}/reviews")
            ->assertOk()->assertJsonPath('data.count', 0);

        // approve → public with aggregate
        DB::table('product_reviews')->where('product_id', $productId)->update(['status' => 'approved']);
        $this->getJson("/api/v1/products/{$productId}/reviews")
            ->assertOk()
            ->assertJsonPath('data.count', 1)
            ->assertJsonPath('data.reviews.0.rating', 5);
    }

    public function test_review_requires_auth_and_valid_rating(): void
    {
        $productId = $this->product();

        $this->postJson("/api/v1/products/{$productId}/reviews", ['rating' => 5, 'body' => 'x'])
            ->assertUnauthorized();

        [, $token] = $this->apiUser('reviewer2@example.com');
        $this->withToken($token)->postJson("/api/v1/products/{$productId}/reviews", ['rating' => 9, 'body' => 'x'])
            ->assertUnprocessable();
    }

    // ---- helpers ---------------------------------------------------------------

    private function apiUser(string $email): array
    {
        $token = bin2hex(random_bytes(32));
        $user = User::forceCreate([
            'name' => 'QA '.strtok($email, '@'),
            'email' => $email,
            'password' => bcrypt('secret-password'),
            'api_token_hash' => hash('sha256', $token),
        ]);

        return [$user, $token];
    }

    private function product(): int
    {
        return (int) DB::table('products')->insertGetId([
            'name' => 'QA Review Part',
            'slug' => 'qa-review-part-'.uniqid(),
            'sku' => 'QA-'.random_int(1000, 9999),
            'status' => 'approved',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }
}
