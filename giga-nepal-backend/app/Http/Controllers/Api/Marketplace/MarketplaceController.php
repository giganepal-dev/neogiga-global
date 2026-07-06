<?php

namespace App\Http\Controllers\Api\Marketplace;

use App\Http\Controllers\Controller;
use App\Models\Marketplace\Marketplace;
use App\Services\MarketplaceResolverService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class MarketplaceController extends Controller
{
    protected MarketplaceResolverService $resolver;

    public function __construct(MarketplaceResolverService $resolver)
    {
        $this->resolver = $resolver;
    }

    public function index(): JsonResponse
    {
        $marketplaces = Marketplace::with(['country', 'currency', 'domains'])
            ->where('is_active', true)
            ->get();

        return response()->json([
            'success' => true,
            'data' => $marketplaces
        ]);
    }

    public function current(Request $request): JsonResponse
    {
        $marketplace = $this->resolver->resolve($request);

        if (!$marketplace) {
            return response()->json([
                'success' => false,
                'message' => 'Marketplace not found'
            ], 404);
        }

        return response()->json([
            'success' => true,
            'data' => $marketplace->load(['country', 'currency', 'domains'])
        ]);
    }

    public function byDomain(Request $request): JsonResponse
    {
        $domain = $request->query('domain');

        if (!$domain) {
            return response()->json([
                'success' => false,
                'message' => 'Domain parameter required'
            ], 400);
        }

        $marketplace = $this->resolver->getByDomain($domain);

        if (!$marketplace) {
            return response()->json([
                'success' => false,
                'message' => 'Marketplace not found for domain'
            ], 404);
        }

        return response()->json([
            'success' => true,
            'data' => $marketplace->load(['country', 'currency', 'domains'])
        ]);
    }
}
