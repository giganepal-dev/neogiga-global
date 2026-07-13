<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BomUploaderPageTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_can_open_the_bom_uploader_and_is_prompted_to_sign_in(): void
    {
        $this->get('/en/bom-imports')
            ->assertOk()
            ->assertSee('Upload a bill of materials')
            ->assertSee('Sign in to upload a BOM');
    }

    public function test_authenticated_user_can_upload_pasted_bom_and_view_match_result(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->post('/en/bom-imports', [
            'name' => 'ESP32 prototype',
            'currency' => 'USD',
            'content' => "MPN,Manufacturer,Quantity\nESP32-WROOM-32,Espressif,2\nLM2596S-ADJ,Texas Instruments,5",
        ]);

        $response->assertRedirect('/en/bom-imports?import=1');
        $this->assertDatabaseHas('bom_imports', [
            'id' => 1,
            'user_id' => $user->id,
            'name' => 'ESP32 prototype',
            'total_lines' => 2,
        ]);

        $this->actingAs($user)->get('/en/bom-imports?import=1')
            ->assertOk()
            ->assertSee('ESP32 prototype')
            ->assertSee('Match result');
    }
}
