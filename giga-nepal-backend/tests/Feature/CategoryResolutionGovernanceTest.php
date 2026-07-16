<?php

namespace Tests\Feature;

use App\Models\Marketplace\CategorySynonym;
use App\Models\Marketplace\ProductCategory;
use App\Services\Catalog\CategoryResolutionService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Cache;
use Tests\TestCase;

class CategoryResolutionGovernanceTest extends TestCase
{
    use RefreshDatabase;

    private ProductCategory $semiconductors;
    private ProductCategory $amplifiers;
    private ProductCategory $operational;

    protected function setUp(): void
    {
        parent::setUp();
        Cache::forget('categories:tree');
        $this->semiconductors = ProductCategory::create(['name' => 'Semiconductors', 'slug' => 'semiconductors', 'is_active' => true]);
        $this->amplifiers = ProductCategory::create(['name' => 'Amplifiers', 'slug' => '191-amplifiers', 'parent_id' => $this->semiconductors->id, 'is_active' => true]);
        $this->operational = ProductCategory::create(['name' => 'Operational Amplifiers', 'slug' => '266-operational-amplifiers', 'parent_id' => $this->amplifiers->id, 'is_active' => true]);
    }

    public function test_existing_category_is_reused_without_creating_a_root(): void
    {
        $result = app(CategoryResolutionService::class)->resolve('Operational Amplifiers', ['source_name' => 'test']);

        $this->assertSame($this->operational->id, $result['category_id']);
        $this->assertFalse($result['requires_review']);
        $this->assertDatabaseCount('product_categories', 3);
    }

    public function test_synonym_resolves_to_its_existing_canonical_category(): void
    {
        CategorySynonym::create([
            'category_id' => $this->operational->id,
            'synonym' => 'op amp',
            'normalized_synonym' => 'op amp',
            'source' => 'test',
            'confidence' => 1,
        ]);

        $result = app(CategoryResolutionService::class)->resolve('Op Amp', ['source_name' => 'test']);

        $this->assertSame($this->operational->id, $result['category_id']);
        $this->assertSame('synonym', $result['matched_by']);
    }

    public function test_unknown_import_category_enters_manual_review_without_creating_a_root(): void
    {
        $result = app(CategoryResolutionService::class)->resolve('Supplier-only mystery family', [
            'source_name' => 'Test supplier',
            'source_category_name' => 'Supplier-only mystery family',
            'source_category_path' => 'Supplier / Supplier-only mystery family',
            'source_key' => 'test-mystery-family',
        ]);

        $this->assertNull($result['category_id']);
        $this->assertTrue($result['requires_review']);
        $this->assertDatabaseCount('product_categories', 3);
        $this->assertDatabaseHas('category_import_reviews', ['source_key' => 'test-mystery-family', 'status' => 'pending_review']);
    }

    public function test_child_creation_requires_explicit_admin_context_and_never_creates_a_root(): void
    {
        $resolver = app(CategoryResolutionService::class);

        try {
            $resolver->createChildUnderExistingParent('Precision Op Amps', $this->operational->id);
            $this->fail('Importer context created a category child.');
        } catch (\LogicException) {
            $this->assertDatabaseCount('product_categories', 3);
        }

        $child = $resolver->createChildUnderExistingParent('Precision Op Amps', $this->operational->id, [
            'actor_type' => 'admin',
            'allow_create_child' => true,
            'source_name' => 'admin test',
        ]);

        $this->assertSame($this->operational->id, $child->parent_id);
        $this->assertDatabaseHas('category_creation_audits', ['category_id' => $child->id, 'parent_category_id' => $this->operational->id]);
    }

    public function test_public_category_tree_excludes_unapproved_technical_roots(): void
    {
        ProductCategory::create(['name' => 'Audio op amps', 'slug' => 'audio-op-amps', 'is_active' => true]);
        config(['category_resolution.intended_root_slugs' => ['semiconductors']]);
        Cache::forget('categories:tree');

        $response = $this->getJson('/api/v1/categories/tree');

        $response->assertOk();
        $this->assertSame(['semiconductors'], collect($response->json('data'))->pluck('slug')->all());
    }

    public function test_hierarchy_audit_writes_a_read_only_plan(): void
    {
        $misplaced = ProductCategory::create(['name' => 'Precision op amps', 'slug' => 'precision-op-amps', 'is_active' => true]);
        $productId = DB::table('products')->insertGetId([
            'name' => 'Test precision amplifier', 'slug' => 'test-precision-amplifier', 'sku' => 'NG-TEST-CAT-1',
            'category_id' => $misplaced->id, 'status' => 'draft', 'created_at' => now(), 'updated_at' => now(),
        ]);
        $directory = storage_path('framework/testing-category-audit');
        $before = ProductCategory::count();

        $this->artisan('catalog:audit-category-hierarchy', ['--output' => $directory])->assertSuccessful();

        $this->assertSame($before, ProductCategory::count());
        $this->assertSame($misplaced->id, (int) DB::table('products')->where('id', $productId)->value('category_id'));
        $this->assertFileExists($directory.'/CATEGORY_HIERARCHY_AUDIT.md');
        $this->assertFileExists($directory.'/PRODUCT_CATEGORY_REMAP.csv');
    }
}
