<?php

namespace Tests\Feature;

use App\Models\Bom\BomImport;
use App\Models\Bom\BomImportLine;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Tests\TestCase;

class BomProcessControllerTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    protected function setUp(): void
    {
        parent::setUp();
        $this->user = User::factory()->create();
    }

    public function test_process_text_requires_auth(): void
    {
        $response = $this->postJson('/api/v1/bom/process-text', [
            'content' => 'MPN,Quantity\nSTM32F103,10',
        ]);

        $response->assertStatus(401);
    }

    public function test_process_text(): void
    {
        $response = $this->actingAs($this->user)
            ->postJson('/api/v1/bom/process-text', [
                'content' => "MPN,Quantity\nSTM32F103,10\nLM358,5",
                'name' => 'Test BOM',
            ]);

        $response->assertCreated()
            ->assertJson([
                'success' => true,
                'data' => [
                    'status' => 'completed',
                ],
            ]);
    }

    public function test_process_text_validates_content(): void
    {
        $response = $this->actingAs($this->user)
            ->postJson('/api/v1/bom/process-text', []);

        $response->assertStatus(422);
    }

    public function test_list_imports(): void
    {
        // Create some imports
        BomImport::create([
            'user_id' => $this->user->id,
            'name' => 'Test BOM 1',
            'status' => 'completed',
        ]);

        $response = $this->actingAs($this->user)
            ->getJson('/api/v1/bom/imports');

        $response->assertOk()
            ->assertJson([
                'success' => true,
            ]);
    }

    public function test_get_status(): void
    {
        $import = BomImport::create([
            'user_id' => $this->user->id,
            'name' => 'Test BOM',
            'status' => 'completed',
            'total_lines' => 10,
            'matched_lines' => 5,
            'unmatched_lines' => 5,
        ]);

        $response = $this->actingAs($this->user)
            ->getJson("/api/v1/bom/{$import->id}/status");

        $response->assertOk()
            ->assertJson([
                'success' => true,
                'data' => [
                    'import_id' => $import->id,
                    'status' => 'completed',
                    'total_lines' => 10,
                ],
            ]);
    }

    public function test_get_status_not_found(): void
    {
        $response = $this->actingAs($this->user)
            ->getJson('/api/v1/bom/999999/status');

        $response->assertStatus(404);
    }

    public function test_get_status_unauthorized(): void
    {
        $otherUser = User::factory()->create();
        $import = BomImport::create([
            'user_id' => $this->user->id,
            'name' => 'Test BOM',
            'status' => 'completed',
        ]);

        $response = $this->actingAs($otherUser)
            ->getJson("/api/v1/bom/{$import->id}/status");

        $response->assertStatus(403);
    }

    public function test_get_results(): void
    {
        $import = BomImport::create([
            'user_id' => $this->user->id,
            'name' => 'Test BOM',
            'status' => 'completed',
        ]);

        BomImportLine::create([
            'bom_import_id' => $import->id,
            'line_no' => 1,
            'mpn' => 'STM32F103',
            'quantity' => 10,
            'match_status' => 'none',
        ]);

        $response = $this->actingAs($this->user)
            ->getJson("/api/v1/bom/{$import->id}/results");

        $response->assertOk()
            ->assertJson([
                'success' => true,
                'data' => [
                    'import' => [
                        'id' => $import->id,
                    ],
                ],
            ]);
    }

    public function test_get_rfq_ready_lines(): void
    {
        $import = BomImport::create([
            'user_id' => $this->user->id,
            'name' => 'Test BOM',
            'status' => 'completed',
        ]);

        BomImportLine::create([
            'bom_import_id' => $import->id,
            'line_no' => 1,
            'mpn' => 'UNKNOWN_PART',
            'quantity' => 10,
            'match_status' => 'none',
        ]);

        $response = $this->actingAs($this->user)
            ->getJson("/api/v1/bom/{$import->id}/rfq-ready");

        $response->assertOk()
            ->assertJson([
                'success' => true,
                'data' => [
                    'count' => 1,
                ],
            ]);
    }

    public function test_get_cart_ready_lines(): void
    {
        $import = BomImport::create([
            'user_id' => $this->user->id,
            'name' => 'Test BOM',
            'status' => 'completed',
        ]);

        $response = $this->actingAs($this->user)
            ->getJson("/api/v1/bom/{$import->id}/cart-ready");

        $response->assertOk()
            ->assertJson([
                'success' => true,
                'data' => [
                    'count' => 0,
                ],
            ]);
    }

    public function test_add_comment(): void
    {
        $import = BomImport::create([
            'user_id' => $this->user->id,
            'name' => 'Test BOM',
            'status' => 'completed',
        ]);

        $response = $this->actingAs($this->user)
            ->postJson("/api/v1/bom/{$import->id}/comments", [
                'comment' => 'This BOM looks good',
                'comment_type' => 'general',
            ]);

        $response->assertCreated()
            ->assertJson([
                'success' => true,
                'data' => [
                    'comment' => 'This BOM looks good',
                ],
            ]);
    }

    public function test_get_comments(): void
    {
        $import = BomImport::create([
            'user_id' => $this->user->id,
            'name' => 'Test BOM',
            'status' => 'completed',
        ]);

        $response = $this->actingAs($this->user)
            ->getJson("/api/v1/bom/{$import->id}/comments");

        $response->assertOk()
            ->assertJson([
                'success' => true,
            ]);
    }

    public function test_delete_import(): void
    {
        $import = BomImport::create([
            'user_id' => $this->user->id,
            'name' => 'Test BOM',
            'status' => 'completed',
        ]);

        $response = $this->actingAs($this->user)
            ->deleteJson("/api/v1/bom/{$import->id}");

        $response->assertOk()
            ->assertJson([
                'success' => true,
            ]);

        $this->assertDatabaseMissing('bom_imports', ['id' => $import->id]);
    }

    public function test_delete_import_unauthorized(): void
    {
        $otherUser = User::factory()->create();
        $import = BomImport::create([
            'user_id' => $this->user->id,
            'name' => 'Test BOM',
            'status' => 'completed',
        ]);

        $response = $this->actingAs($otherUser)
            ->deleteJson("/api/v1/bom/{$import->id}");

        $response->assertStatus(403);
    }

    public function test_upload_file(): void
    {
        $file = UploadedFile::fake()->createWithContent(
            'test-bom.csv',
            "MPN,Quantity\nSTM32F103,10\nLM358,5"
        );

        $response = $this->actingAs($this->user)
            ->postJson('/api/v1/bom/upload', [
                'file' => $file,
                'name' => 'Uploaded BOM',
            ]);

        $response->assertCreated()
            ->assertJson([
                'success' => true,
                'data' => [
                    'status' => 'completed',
                ],
            ]);
    }
}
