<?php

namespace Tests\Unit;

use App\Models\User;
use App\Services\Marketplace\GlobalMarketplaceContextService;
use App\Services\Marketplace\UserMarketplaceScopeService;
use Illuminate\Validation\ValidationException;
use Mockery;
use Tests\TestCase;

class UserMarketplaceScopeServiceTest extends TestCase
{
    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    public function test_assert_can_purchase_allows_matching_home_marketplace(): void
    {
        $user = new User(['home_marketplace_id' => 5]);

        app(UserMarketplaceScopeService::class)->assertCanPurchase($user, 5);

        $this->assertTrue(true);
    }

    public function test_assert_can_purchase_blocks_foreign_marketplace(): void
    {
        $user = new User(['home_marketplace_id' => 5]);

        $this->expectException(ValidationException::class);
        app(UserMarketplaceScopeService::class)->assertCanPurchase($user, 9);
    }

    public function test_assert_can_purchase_allows_guest_and_legacy_users(): void
    {
        app(UserMarketplaceScopeService::class)->assertCanPurchase(null, 9);

        $legacy = new User(['home_marketplace_id' => null]);
        app(UserMarketplaceScopeService::class)->assertCanPurchase($legacy, 9);

        $this->assertTrue(true);
    }

    public function test_home_marketplace_id_comes_from_request_context(): void
    {
        $marketplace = (object) ['id' => 42];
        $context = Mockery::mock(GlobalMarketplaceContextService::class);
        $context->shouldReceive('context')->once()->andReturn(['current' => $marketplace]);

        $service = new UserMarketplaceScopeService($context);
        $request = request();

        $this->assertSame(42, $service->homeMarketplaceIdForRegistration($request));
    }
}
