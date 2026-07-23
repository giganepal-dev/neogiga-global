<?php

namespace App\Http\Controllers\Api\Product;

use App\Http\Controllers\Controller;
use App\Models\Product\MpnAlias;
use App\Services\Bom\BomComponentMatcher;
use App\Services\Product\AlternativePartsService;
use App\Services\Product\MpnAutocompleteService;
use App\Services\Product\MpnNormalizationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class MpnAutocompleteController extends Controller
{
    public function __construct(
        private MpnAutocompleteService $autocomplete,
        private MpnNormalizationService $normalization,
        private AlternativePartsService $alternatives,
        private BomComponentMatcher $matcher,
    ) {}

    /**
     * MPN Autocomplete search.
     *
     * GET /api/v1/products/autocomplete?q=STM32&limit=20
     */
    public function search(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'q' => ['required', 'string', 'min:1', 'max:200'],
            'marketplace_id' => ['nullable', 'integer'],
            'limit' => ['nullable', 'integer', 'min:1', 'max:50'],
            'offset' => ['nullable', 'integer', 'min:0'],
        ]);

        $result = $this->autocomplete->search(
            $validated['q'],
            $validated['marketplace_id'] ?? null,
            $validated['limit'] ?? 20,
            $validated['offset'] ?? 0,
        );

        return response()->json([
            'success' => true,
            'data' => $result,
        ]);
    }

    /**
     * Normalize an MPN.
     *
     * POST /api/v1/products/mpn/normalize
     */
    public function normalize(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'mpn' => ['required', 'string', 'max:200'],
        ]);

        $result = $this->normalization->normalize($validated['mpn']);
        $manufacturer = $this->normalization->detectManufacturer($validated['mpn']);
        $isPassive = $this->normalization->isPassiveComponent($validated['mpn']);
        $aliases = $this->normalization->lookupAliases($validated['mpn']);

        return response()->json([
            'success' => true,
            'data' => array_merge($result, [
                'detected_manufacturer' => $manufacturer,
                'is_passive_component' => $isPassive,
                'aliases' => $aliases,
            ]),
        ]);
    }

    /**
     * Batch normalize MPNs.
     *
     * POST /api/v1/products/mpn/normalize-batch
     */
    public function normalizeBatch(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'mpns' => ['required', 'array', 'min:1', 'max:100'],
            'mpns.*' => ['string', 'max:200'],
        ]);

        $results = $this->normalization->normalizeBatch($validated['mpns']);
        $stats = $this->normalization->getStats($validated['mpns']);

        return response()->json([
            'success' => true,
            'data' => [
                'results' => $results,
                'stats' => $stats,
            ],
        ]);
    }

    /**
     * Match a single MPN against catalog.
     *
     * POST /api/v1/products/mpn/match
     */
    public function matchMpn(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'mpn' => ['required', 'string', 'max:200'],
            'manufacturer' => ['nullable', 'string', 'max:200'],
        ]);

        $result = $this->matcher->matchSingle(
            $validated['mpn'],
            $validated['manufacturer'] ?? null,
        );

        return response()->json([
            'success' => true,
            'data' => $result,
        ]);
    }

    /**
     * Find alternatives for a product.
     *
     * GET /api/v1/products/{product}/alternatives
     */
    public function alternatives(Request $request, int $product): JsonResponse
    {
        $validated = $request->validate([
            'marketplace_id' => ['nullable', 'integer'],
            'limit' => ['nullable', 'integer', 'min:1', 'max:50'],
        ]);

        $result = $this->alternatives->findAlternatives(
            $product,
            $validated['marketplace_id'] ?? null,
            $validated['limit'] ?? 20,
        );

        return response()->json([
            'success' => true,
            'data' => $result,
        ]);
    }

    /**
     * Detect manufacturer from MPN.
     *
     * POST /api/v1/products/mpn/detect-manufacturer
     */
    public function detectManufacturer(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'mpn' => ['required', 'string', 'max:200'],
        ]);

        $manufacturer = $this->normalization->detectManufacturer($validated['mpn']);

        return response()->json([
            'success' => true,
            'data' => [
                'mpn' => $validated['mpn'],
                'manufacturer' => $manufacturer,
                'confidence' => $manufacturer ? 'high' : 'low',
            ],
        ]);
    }

    /**
     * Resolve manufacturer alias.
     *
     * POST /api/v1/products/mpn/resolve-manufacturer
     */
    public function resolveManufacturer(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'manufacturer' => ['required', 'string', 'max:200'],
        ]);

        $resolved = $this->normalization->resolveManufacturer($validated['manufacturer']);

        return response()->json([
            'success' => true,
            'data' => [
                'input' => $validated['manufacturer'],
                'resolved' => $resolved,
                'is_alias' => $resolved !== $validated['manufacturer'],
            ],
        ]);
    }

    /**
     * Store an MPN alias.
     *
     * POST /api/v1/products/mpn/alias
     */
    public function storeAlias(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'product_id' => ['required', 'integer', 'exists:products,id'],
            'alias_mpn' => ['required', 'string', 'max:200'],
            'alias_type' => ['nullable', 'string', 'in:cross_reference,replacement,upgrade,downgrade'],
            'source' => ['nullable', 'string', 'max:100'],
        ]);

        $alias = $this->normalization->storeAlias(
            $validated['product_id'],
            $validated['alias_mpn'],
            $validated['alias_type'] ?? 'cross_reference',
            $validated['source'] ?? null,
        );

        return response()->json([
            'success' => true,
            'data' => $alias,
        ], 201);
    }

    /**
     * Get MPN aliases for a product.
     *
     * GET /api/v1/products/{product}/aliases
     */
    public function getAliases(int $product): JsonResponse
    {
        $aliases = MpnAlias::where('product_id', $product)
            ->active()
            ->get();

        return response()->json([
            'success' => true,
            'data' => $aliases,
        ]);
    }

    /**
     * Parse passive component description.
     *
     * POST /api/v1/products/mpn/parse-passive
     */
    public function parsePassive(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'description' => ['required', 'string', 'max:500'],
        ]);

        $parsed = $this->normalization->parsePassiveDescription($validated['description']);

        return response()->json([
            'success' => true,
            'data' => $parsed,
        ]);
    }
}
