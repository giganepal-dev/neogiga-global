<?php

namespace Tests\Feature;

use App\Http\Controllers\Admin\CommerceOpsController;
use App\Http\Middleware\EnsureAdminWebPermission;
use App\Models\Bom\BomImport;
use App\Models\Marketplace\Product;
use App\Models\Role;
use App\Models\User;
use App\Services\Bom\BomImportService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Tests\TestCase;

class BomAdminReviewWorkflowTest extends TestCase
{
    use RefreshDatabase;

    public function test_catalog_admin_can_rematch_and_manually_assign_a_bom_line_with_an_audit_trail(): void
    {
        $admin = $this->adminWith(['catalog.manage']);
        $product = $this->publishedProduct('ADMIN-BOM-100');
        $import = BomImport::create([
            'name' => 'Admin BOM review',
            'status' => 'matched',
            'currency' => 'USD',
            'total_lines' => 1,
            'matched_lines' => 0,
            'unmatched_lines' => 1,
        ]);
        $line = $import->lines()->create([
            'line_no' => 1,
            'mpn' => 'ADMIN-BOM-100',
            'quantity' => 2,
            'match_status' => 'none',
            'match_confidence' => 0,
        ]);

        $response = app(CommerceOpsController::class)->rematchBomImport(
            $this->adminRequest($admin),
            $import,
            app(BomImportService::class),
        );
        $this->assertSame(302, $response->getStatusCode());

        $this->assertDatabaseHas('bom_import_lines', [
            'id' => $line->id,
            'matched_product_id' => $product->id,
            'match_status' => 'exact',
        ]);
        $this->assertDatabaseHas('audit_logs', [
            'action' => 'bom_import_rematched',
            'model_type' => 'bom_imports',
            'model_id' => $import->id,
        ]);

        $response = app(CommerceOpsController::class)->setBomImportLineMatch(
            $this->adminRequest($admin, ['matched_product_id' => null]),
            $import->fresh(),
            $line->fresh(),
            app(BomImportService::class),
        );
        $this->assertSame(302, $response->getStatusCode());

        $this->assertDatabaseHas('bom_import_lines', [
            'id' => $line->id,
            'matched_product_id' => null,
            'match_status' => 'none',
        ]);
        $this->assertDatabaseHas('audit_logs', [
            'action' => 'bom_import_line_match_set',
            'model_type' => 'bom_import_lines',
            'model_id' => $line->id,
        ]);
    }

    public function test_converted_imports_stay_locked_when_admin_actions_are_called(): void
    {
        $authorized = $this->adminWith(['catalog.manage']);
        $import = BomImport::create([
            'name' => 'Converted BOM',
            'status' => 'converted',
            'currency' => 'USD',
            'total_lines' => 1,
            'matched_lines' => 0,
            'unmatched_lines' => 1,
        ]);
        $line = $import->lines()->create(['line_no' => 1, 'quantity' => 1, 'match_status' => 'none']);

        $response = app(CommerceOpsController::class)->rematchBomImport(
            $this->adminRequest($authorized),
            $import,
            app(BomImportService::class),
        );
        $this->assertSame(302, $response->getStatusCode());
        $this->assertSame('Converted BOM imports are immutable so their RFQ history remains accurate.', session('error'));

        $response = app(CommerceOpsController::class)->setBomImportLineMatch(
            $this->adminRequest($authorized, ['matched_product_id' => null]),
            $import,
            $line,
            app(BomImportService::class),
        );
        $this->assertSame(302, $response->getStatusCode());
        $this->assertSame('Converted BOM imports are immutable so their RFQ history remains accurate.', session('error'));
    }

    public function test_catalog_manage_permission_middleware_denies_unprivileged_admins(): void
    {
        $middleware = app(EnsureAdminWebPermission::class);
        $denied = $this->adminWith([]);

        try {
            $middleware->handle($this->adminRequest($denied), static fn () => response('allowed'), 'catalog.manage');
            $this->fail('Expected catalog.manage middleware to reject the request.');
        } catch (HttpException $exception) {
            $this->assertSame(403, $exception->getStatusCode());
        }

        $allowed = $this->adminWith(['catalog.manage'], 'allowed-bom-admin@example.com');
        $response = $middleware->handle($this->adminRequest($allowed), static fn () => response('allowed'), 'catalog.manage');
        $this->assertSame(200, $response->getStatusCode());
    }

    private function adminWith(array $permissions, string $email = 'bom-admin@example.com'): User
    {
        $role = Role::firstOrCreate(
            ['name' => 'admin'],
            ['display_name' => 'Catalog admin', 'permissions' => $permissions, 'is_active' => true],
        );
        $role->forceFill(['permissions' => $permissions])->save();

        return User::create([
            'name' => 'BOM Admin',
            'email' => $email,
            'password' => bcrypt('secret'),
            'role_id' => $role->id,
        ]);
    }

    private function publishedProduct(string $mpn): Product
    {
        return Product::create([
            'name' => 'BOM '.$mpn,
            'slug' => Str::slug($mpn),
            'sku' => 'NG-'.str_replace('-', '', $mpn),
            'mpn' => $mpn,
            'status' => 'approved',
        ]);
    }

    private function adminRequest(User $user, array $data = []): Request
    {
        $request = Request::create('/admin/bom-imports', 'POST', $data);
        $request->setUserResolver(static fn (): User => $user);
        $session = app('session.store');
        $session->start();
        $request->setLaravelSession($session);

        return $request;
    }
}
