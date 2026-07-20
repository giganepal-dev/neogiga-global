<?php

namespace App\Http\Controllers\Api\B2B;

use App\Http\Controllers\Concerns\ApiResponses;
use App\Http\Controllers\Controller;
use App\Http\Requests\B2B\B2BApplyRequest;
use App\Services\B2B\B2BAccountService;
use App\Services\B2B\B2BContextService;
use App\Services\Marketplace\UserMarketplaceScopeService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Schema;

class B2BAccountController extends Controller
{
    use ApiResponses;

    public function apply(
        B2BApplyRequest $request,
        B2BAccountService $service,
        UserMarketplaceScopeService $marketplaceScope,
    ): JsonResponse {
        if (! Schema::hasTable('b2b_accounts')) {
            return $this->error('B2B foundation migration is pending.', 503);
        }

        $data = $request->validated();
        if (empty($data['marketplace_id'])) {
            $data['marketplace_id'] = $marketplaceScope->homeMarketplaceIdForRegistration($request);
        }

        $account = $service->apply($data, $request, $request->user());

        return $this->success($account->only(['id', 'name', 'slug', 'status', 'type', 'email']), 201);
    }

    public function show(Request $request, B2BContextService $context): JsonResponse
    {
        if (! Schema::hasTable('b2b_accounts')) {
            return $this->error('B2B foundation migration is pending.', 503);
        }

        return $this->success($context->abortUnlessAccount($request->user())->load('users'));
    }

    public function update(Request $request, B2BContextService $context): JsonResponse
    {
        if (! Schema::hasTable('b2b_accounts')) {
            return $this->error('B2B foundation migration is pending.', 503);
        }

        $data = $request->validate([
            'phone' => ['sometimes', 'nullable', 'string', 'max:40'],
            'billing_address' => ['sometimes', 'array'],
            'shipping_address' => ['sometimes', 'array'],
        ]);

        $account = $context->abortUnlessAccount($request->user());
        $account->fill($data)->save();

        return $this->success($account->fresh());
    }
}
