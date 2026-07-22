<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class BomAccountIntegrationTest extends TestCase
{
    use RefreshDatabase;

    public function test_signed_in_bom_match_is_saved_to_the_customer_account(): void
    {
        $user = User::factory()->create([
            'name' => 'BOM Buyer',
            'email' => 'bom-buyer@example.com',
        ]);

        $this->actingAs($user)
            ->post('/en/bom', [
                'bom' => "MPN,Quantity,Manufacturer\nTEST-PART-001,5,NeoGiga",
            ])
            ->assertOk()
            ->assertSee('bom-buyer@example.com')
            ->assertSee('Saved as BOM #');

        $this->assertDatabaseHas('bom_imports', [
            'user_id' => $user->id,
            'total_lines' => 1,
            'status' => 'matched',
        ]);
        $importId = DB::table('bom_imports')->where('user_id', $user->id)->value('id');
        $this->assertDatabaseHas('bom_import_lines', [
            'bom_import_id' => $importId,
            'mpn' => 'TEST-PART-001',
        ]);
    }
}
