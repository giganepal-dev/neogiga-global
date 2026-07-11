<?php

namespace Tests\Feature;

use App\Models\Marketplace\Product;
use App\Models\User;
use App\Services\Bom\BomImportParser;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Tests\TestCase;

/**
 * Release A — BOM procurement import: CSV/paste parse -> catalog match by MPN ->
 * manual review -> convert to RFQ. Runs against the live schema (no fixtures).
 */
class BomImportFlowTest extends TestCase
{
    use RefreshDatabase;

    public function test_csv_with_header_parses_and_matches_by_mpn(): void
    {
        [, $token] = $this->apiUser('bom-owner@example.com');
        $this->makeProduct('RC0402FR-0710KL', 'Yageo', 'NG-R-0001');
        $this->makeProduct('CL05B104KO5NNNC', 'Samsung', 'NG-C-0001');

        $csv = "Comment,Designator,Footprint,MPN,Manufacturer,Quantity\n"
            ."10k Resistor,R1 R2,0402,RC0402FR-0710KL,Yageo,2\n"
            ."100nF Cap,C1,0402,CL05B104KO5NNNC,Samsung,1\n"
            ."Mystery Part,U9,QFN,ZZZ-NOTREAL-999,Nobody,1";

        $res = $this->withToken($token)->postJson('/api/v1/bom/imports', [
            'name' => 'PSU rev A',
            'content' => $csv,
        ]);

        $res->assertCreated();
        $res->assertJsonPath('data.total_lines', 3);
        $res->assertJsonPath('data.matched_lines', 2);
        $res->assertJsonPath('data.unmatched_lines', 1);

        $lines = collect($res->json('data.lines'))->keyBy('line_no');
        $this->assertSame('exact', $lines[1]['match_status']);
        $this->assertSame(100, $lines[1]['match_confidence']);
        $this->assertSame('2.000', (string) $lines[1]['quantity']);
        $this->assertSame('none', $lines[3]['match_status']);
        $this->assertNull($lines[3]['matched_product_id']);
    }

    public function test_normalized_mpn_matches_despite_case_and_spaces(): void
    {
        [, $token] = $this->apiUser('bom-norm@example.com');
        $this->makeProduct('STM32F103C8T6', 'ST', 'NG-U-0002');

        // Lower-cased with an inserted internal space — whitespace is stripped and
        // the value upper-cased to match the functional index (hyphens are preserved,
        // so this MPN is intentionally hyphen-free).
        $res = $this->withToken($token)->postJson('/api/v1/bom/imports', [
            'name' => 'norm test',
            'content' => "MPN,Qty\nstm32 f103 c8t6,5",
        ]);

        $res->assertCreated();
        $res->assertJsonPath('data.matched_lines', 1);
        $this->assertSame('exact', $res->json('data.lines.0.match_status'));
    }

    public function test_headerless_paste_uses_positional_fallback(): void
    {
        [, $token] = $this->apiUser('bom-pos@example.com');
        $this->makeProduct('LM358P', 'TI', 'NG-U-0001');

        $res = $this->withToken($token)->postJson('/api/v1/bom/imports', [
            'name' => 'headerless',
            'content' => "LM358P,3\nSOME-OTHER-PART,1",
        ]);

        $res->assertCreated();
        $res->assertJsonPath('data.total_lines', 2);
        $this->assertSame('3.000', (string) $res->json('data.lines.0.quantity'));
        $this->assertSame('exact', $res->json('data.lines.0.match_status'));
    }

    public function test_ambiguous_mpn_needs_review_then_manual_override(): void
    {
        [, $token] = $this->apiUser('bom-amb@example.com');
        $alpha = $this->makeProduct('DUP-100', 'Alpha', 'NG-DUP-A');
        $this->makeProduct('DUP-100', 'Beta', 'NG-DUP-B');

        $res = $this->withToken($token)->postJson('/api/v1/bom/imports', [
            'name' => 'ambiguous',
            'content' => "MPN,Quantity\nDUP-100,1", // no manufacturer -> cannot disambiguate
        ]);
        $res->assertCreated();
        $importId = $res->json('data.id');
        $line = $res->json('data.lines.0');

        $this->assertSame('multiple', $line['match_status']);
        $this->assertNull($line['matched_product_id']);
        $this->assertCount(2, $line['candidates']);
        $res->assertJsonPath('data.matched_lines', 0);

        // Reviewer picks Alpha.
        $patch = $this->withToken($token)->patchJson(
            "/api/v1/bom/imports/{$importId}/lines/{$line['id']}",
            ['matched_product_id' => $alpha->id],
        );
        $patch->assertOk();
        $patch->assertJsonPath('data.match_status', 'manual');
        $patch->assertJsonPath('data.match_confidence', 100);

        $this->assertSame(1, DB::table('bom_imports')->where('id', $importId)->value('matched_lines'));
    }

    public function test_manufacturer_disambiguates_duplicate_mpn(): void
    {
        [, $token] = $this->apiUser('bom-mfr@example.com');
        $this->makeProduct('DUP-200', 'Alpha', 'NG-D2-A');
        $this->makeProduct('DUP-200', 'Beta', 'NG-D2-B');

        $res = $this->withToken($token)->postJson('/api/v1/bom/imports', [
            'name' => 'mfr disambig',
            'content' => "MPN,Manufacturer,Quantity\nDUP-200,Beta,1",
        ]);

        $res->assertCreated();
        $res->assertJsonPath('data.matched_lines', 1);
        $line = $res->json('data.lines.0');
        $this->assertSame('exact', $line['match_status']);
        $this->assertSame(90, $line['match_confidence']);
    }

    public function test_convert_to_rfq_creates_request_with_all_lines(): void
    {
        [$user, $token] = $this->apiUser('bom-rfq@example.com');
        $this->makeProduct('RC0402FR-0710KL', 'Yageo', 'NG-R-9001');

        $create = $this->withToken($token)->postJson('/api/v1/bom/imports', [
            'name' => 'to rfq',
            'content' => "MPN,Quantity\nRC0402FR-0710KL,10\nUNMATCHED-XYZ,4",
        ]);
        $create->assertCreated();
        $importId = $create->json('data.id');

        $convert = $this->withToken($token)->postJson("/api/v1/bom/imports/{$importId}/convert-to-rfq", [
            'company_name' => 'QA Labs',
            'contact_email' => 'buyer@example.com',
        ]);
        $convert->assertCreated();
        $rfqId = $convert->json('data.id');

        // Both matched and unmatched lines become RFQ items.
        $this->assertSame(2, DB::table('rfq_items')->where('rfq_request_id', $rfqId)->count());
        $matchedItem = DB::table('rfq_items')->where('rfq_request_id', $rfqId)->whereNotNull('product_id')->first();
        $this->assertNotNull($matchedItem);
        $this->assertSame('NG-R-9001', $matchedItem->sku);

        $import = DB::table('bom_imports')->where('id', $importId)->first();
        $this->assertSame('converted', $import->status);
        $this->assertSame((int) $rfqId, (int) $import->rfq_request_id);
        $this->assertSame($user->id, (int) DB::table('rfq_requests')->where('id', $rfqId)->value('user_id'));

        // Second conversion is refused.
        $this->withToken($token)->postJson("/api/v1/bom/imports/{$importId}/convert-to-rfq")
            ->assertStatus(409);
    }

    public function test_imports_are_ownership_scoped(): void
    {
        [, $ownerToken] = $this->apiUser('owner-a@example.com');
        [, $otherToken] = $this->apiUser('other-b@example.com');

        $importId = $this->withToken($ownerToken)->postJson('/api/v1/bom/imports', [
            'name' => 'mine',
            'content' => "MPN,Quantity\nABC-1,1",
        ])->json('data.id');

        $this->withToken($otherToken)->getJson("/api/v1/bom/imports/{$importId}")->assertNotFound();
        $this->withToken($ownerToken)->getJson("/api/v1/bom/imports/{$importId}")->assertOk();
    }

    public function test_empty_content_is_rejected(): void
    {
        [, $token] = $this->apiUser('bom-empty@example.com');

        $this->withToken($token)->postJson('/api/v1/bom/imports', [
            'name' => 'empty',
            'content' => "MPN,Quantity\n",
        ])->assertStatus(422);
    }

    public function test_parser_maps_qty_alias_and_tab_delimiter(): void
    {
        $parser = new BomImportParser();
        $out = $parser->parse("MPN\tQty\nPART-1\t7");

        $this->assertSame("\t", $out['delimiter']);
        $this->assertTrue($out['has_header']);
        $this->assertSame('PART-1', $out['lines'][0]['mpn']);
        $this->assertSame(7.0, $out['lines'][0]['quantity']);
    }

    // ---- helpers ------------------------------------------------------------

    /** @return array{0:User,1:string} */
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

    private function makeProduct(string $mpn, string $brand, string $sku): Product
    {
        $brandId = DB::table('product_brands')->insertGetId([
            'name' => $brand,
            'slug' => Str::slug($brand.'-'.$sku),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        return Product::create([
            'name' => $brand.' '.$mpn,
            'slug' => Str::slug($sku),
            'sku' => $sku,
            'mpn' => $mpn,
            'brand_id' => $brandId,
            'status' => 'approved',
        ]);
    }
}
