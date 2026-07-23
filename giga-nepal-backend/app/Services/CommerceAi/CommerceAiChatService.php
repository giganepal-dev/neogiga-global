<?php

namespace App\Services\CommerceAi;

use App\Models\Bom\BomImport;
use App\Models\Bom\BomImportLine;
use App\Services\Bom\BomComponentMatcher;
use App\Services\Bom\BomRfqService;
use App\Services\Product\AlternativePartsService;
use App\Services\Product\MpnAutocompleteService;
use App\Services\Product\MpnNormalizationService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * AI Commerce Chat Service.
 *
 * Handles conversational BOM processing and product queries.
 * Returns structured responses with interactive elements.
 *
 * Supported intents:
 * - BOM paste/upload
 * - Product search
 * - Alternative request
 * - Price/stock query
 * - RFQ creation
 * - Cart operations
 * - Technical questions
 */
class CommerceAiChatService
{
    public function __construct(
        private MpnNormalizationService $normalization,
        private MpnAutocompleteService $autocomplete,
        private AlternativePartsService $alternatives,
        private BomComponentMatcher $matcher,
        private BomRfqService $rfqService,
    ) {}

    /**
     * Process a chat message and return structured response.
     *
     * @return array{reply: string, structured: array, actions: array, confidence: float}
     */
    public function processMessage(string $message, ?int $userId = null, ?string $sessionKey = null): array
    {
        // Detect intent
        $intent = $this->detectIntent($message);

        // Process based on intent
        return match ($intent['type']) {
            'bom_paste' => $this->handleBomPaste($message, $userId, $intent),
            'product_search' => $this->handleProductSearch($message, $intent),
            'alternatives_request' => $this->handleAlternativesRequest($message, $intent),
            'price_query' => $this->handlePriceQuery($message, $intent),
            'stock_query' => $this->handleStockQuery($message, $intent),
            'rfq_request' => $this->handleRfqRequest($message, $userId, $intent),
            'cart_request' => $this->handleCartRequest($message, $userId, $intent),
            'technical_question' => $this->handleTechnicalQuestion($message, $intent),
            default => $this->handleGeneralQuery($message, $intent),
        };
    }

    /**
     * Detect the intent of a chat message.
     */
    private function detectIntent(string $message): array
    {
        $lower = strtolower($message);

        // BOM patterns
        if (preg_match('/(bom|bill of materials|parts list|component list)/i', $message) ||
            preg_match('/^[\s\S]*\t[\s\S]*\n[\s\S]*\t[\s\S]*/m', $message) || // Tab-separated
            preg_match('/^[\s\S]*,[\s\S]*\n[\s\S]*,[\s\S]*/m', $message) || // CSV-like
            preg_match('/\b\d+\s*[xX]\s*\w+/i', $message)) { // Quantity patterns
            return ['type' => 'bom_paste', 'confidence' => 0.8];
        }

        // Product search
        if (preg_match('/(search|find|look|show|get)\s+(for\s+)?/i', $message) ||
            preg_match('/^[A-Z0-9]{2,}[-\/][A-Z0-9]+/i', $message)) { // MPN pattern
            return ['type' => 'product_search', 'confidence' => 0.7];
        }

        // Alternatives
        if (preg_match('/(alternative|replacement|substitute|equivalent|replace|similar)/i', $message)) {
            return ['type' => 'alternatives_request', 'confidence' => 0.85];
        }

        // Price query
        if (preg_match('/(price|cost|how much|pricing)/i', $message)) {
            return ['type' => 'price_query', 'confidence' => 0.75];
        }

        // Stock query
        if (preg_match('/(stock|availability|available|inventory|in stock)/i', $message)) {
            return ['type' => 'stock_query', 'confidence' => 0.75];
        }

        // RFQ request
        if (preg_match('/(rfq|request for quote|quotation|quote request)/i', $message)) {
            return ['type' => 'rfq_request', 'confidence' => 0.85];
        }

        // Cart request
        if (preg_match('/(add to cart|cart|buy|purchase)/i', $message)) {
            return ['type' => 'cart_request', 'confidence' => 0.8];
        }

        // Technical question
        if (preg_match('/(spec|datasheet|technical|specification|pinout|package|voltage|current)/i', $message)) {
            return ['type' => 'technical_question', 'confidence' => 0.7];
        }

        return ['type' => 'general', 'confidence' => 0.5];
    }

    /**
     * Handle BOM paste input.
     */
    private function handleBomPaste(string $message, ?int $userId, array $intent): array
    {
        // Parse the BOM
        $parser = app(\App\Services\Bom\BomImportParser::class);
        $parsed = $parser->parse($message);

        if (empty($parsed['lines'])) {
            return [
                'reply' => "I couldn't parse any components from your input. Please ensure your BOM has part numbers and quantities.",
                'structured' => ['type' => 'error', 'message' => 'No parseable lines found'],
                'actions' => [],
                'confidence' => 0.3,
            ];
        }

        // Match against catalog
        $matcher = app(BomComponentMatcher::class);
        $matchResults = $matcher->match($parsed['lines']);

        // Build structured response
        $matched = [];
        $unmatched = [];
        $multipleMatches = [];

        foreach ($parsed['lines'] as $line) {
            $result = $matchResults[$line['line_no']] ?? null;

            if ($result && $result['match_status'] === 'exact') {
                $matched[] = [
                    'line_no' => $line['line_no'],
                    'mpn' => $line['mpn'],
                    'manufacturer' => $line['manufacturer'],
                    'quantity' => $line['quantity'],
                    'product_id' => $result['matched_product_id'],
                    'confidence' => $result['match_confidence'],
                ];
            } elseif ($result && $result['match_status'] === 'multiple') {
                $multipleMatches[] = [
                    'line_no' => $line['line_no'],
                    'mpn' => $line['mpn'],
                    'manufacturer' => $line['manufacturer'],
                    'quantity' => $line['quantity'],
                    'candidates' => $result['candidates'],
                ];
            } else {
                $unmatched[] = [
                    'line_no' => $line['line_no'],
                    'mpn' => $line['mpn'],
                    'manufacturer' => $line['manufacturer'],
                    'quantity' => $line['quantity'],
                ];
            }
        }

        $reply = $this->buildBomReply($matched, $unmatched, $multipleMatches);

        return [
            'reply' => $reply,
            'structured' => [
                'type' => 'bom_results',
                'total_lines' => count($parsed['lines']),
                'matched' => $matched,
                'unmatched' => $unmatched,
                'multiple_matches' => $multipleMatches,
                'match_rate' => count($parsed['lines']) > 0
                    ? round((count($matched) / count($parsed['lines'])) * 100)
                    : 0,
            ],
            'actions' => [
                ['type' => 'add_matched_to_cart', 'label' => 'Add matched to cart'],
                ['type' => 'create_rfq', 'label' => 'Create RFQ for unmatched'],
                ['type' => 'view_details', 'label' => 'View detailed results'],
            ],
            'confidence' => 0.85,
        ];
    }

    /**
     * Build a human-readable reply for BOM results.
     */
    private function buildBomReply(array $matched, array $unmatched, array $multipleMatches): string
    {
        $total = count($matched) + count($unmatched) + count($multipleMatches);
        $lines = [];

        $lines[] = "I processed your BOM with **{$total} lines**.";

        if (count($matched) > 0) {
            $lines[] = count($matched) . " exact matches found in our catalog.";
        }

        if (count($multipleMatches) > 0) {
            $lines[] = count($multipleMatches) . " lines have multiple possible matches - please review.";
        }

        if (count($unmatched) > 0) {
            $lines[] = count($unmatched) . " lines could not be matched - these can go to RFQ.";
        }

        if (count($matched) > 0) {
            $lines[] = "";
            $lines[] = "Would you like me to:";
            $lines[] = "1. Add all matched items to your cart";
            $lines[] = "2. Create an RFQ for the unmatched lines";
            $lines[] = "3. Show detailed comparison for each match";
        }

        return implode("\n", $lines);
    }

    /**
     * Handle product search request.
     */
    private function handleProductSearch(string $message, array $intent): array
    {
        // Extract search term
        $searchTerm = preg_replace('/^(search|find|look|show|get)\s+(for\s+)?/i', '', $message);
        $searchTerm = trim($searchTerm);

        if (empty($searchTerm)) {
            return [
                'reply' => "What product are you looking for? You can search by MPN, SKU, or product name.",
                'structured' => ['type' => 'prompt'],
                'actions' => [],
                'confidence' => 0.5,
            ];
        }

        $results = $this->autocomplete->search($searchTerm, null, 10);

        if (empty($results['results'])) {
            return [
                'reply' => "I couldn't find any products matching \"{$searchTerm}\". Try a different search term or check the spelling.",
                'structured' => ['type' => 'no_results', 'query' => $searchTerm],
                'actions' => [],
                'confidence' => 0.6,
            ];
        }

        $productList = array_map(fn ($r) => "- **{$r['name']}** ({$r['mpn']}) by {$r['brand']}", $results['results']);
        $reply = "Found {$results['total']} products matching \"{$searchTerm}\":\n\n" . implode("\n", $productList);

        return [
            'reply' => $reply,
            'structured' => [
                'type' => 'product_results',
                'query' => $searchTerm,
                'total' => $results['total'],
                'products' => $results['results'],
            ],
            'actions' => [
                ['type' => 'view_product', 'label' => 'View product details'],
                ['type' => 'find_alternatives', 'label' => 'Find alternatives'],
            ],
            'confidence' => 0.8,
        ];
    }

    /**
     * Handle alternatives request.
     */
    private function handleAlternativesRequest(string $message, array $intent): array
    {
        // Extract MPN from message
        preg_match('/([A-Z0-9][A-Z0-9\-\/\.]+[A-Z0-9])/i', $message, $matches);
        $mpn = $matches[1] ?? null;

        if (! $mpn) {
            return [
                'reply' => "Which part do you need alternatives for? Please provide the MPN.",
                'structured' => ['type' => 'prompt'],
                'actions' => [],
                'confidence' => 0.5,
            ];
        }

        // Find the product
        $normalized = $this->normalization->normalize($mpn)['normalized'];
        $product = \App\Models\Marketplace\Product::published()
            ->whereRaw("upper(replace(coalesce(mpn, ''), ' ', '')) = ?", [$normalized])
            ->first();

        if (! $product) {
            return [
                'reply' => "I couldn't find a product with MPN \"{$mpn}\" in our catalog. Would you like me to search for similar parts?",
                'structured' => ['type' => 'not_found', 'mpn' => $mpn],
                'actions' => [['type' => 'search_similar', 'label' => 'Search similar']],
                'confidence' => 0.5,
            ];
        }

        $alts = $this->alternatives->findAlternatives($product->id, null, 10);

        if (empty($alts['alternatives'])) {
            return [
                'reply' => "I couldn't find any alternatives for {$mpn}. It may be a unique part.",
                'structured' => ['type' => 'no_alternatives', 'mpn' => $mpn],
                'actions' => [],
                'confidence' => 0.6,
            ];
        }

        $altList = array_map(fn ($a) => "- **{$a['name']}** ({$a['mpn']}) by {$a['brand']} — {$a['reason']}", $alts['alternatives']);
        $reply = "Found " . count($alts['alternatives']) . " alternatives for {$mpn}:\n\n" . implode("\n", $altList);

        return [
            'reply' => $reply,
            'structured' => [
                'type' => 'alternatives',
                'product' => $alts['product'],
                'alternatives' => $alts['alternatives'],
                'analysis' => $alts['analysis'],
            ],
            'actions' => [
                ['type' => 'view_alternative', 'label' => 'View comparison'],
                ['type' => 'add_to_cart', 'label' => 'Add to cart'],
            ],
            'confidence' => 0.85,
        ];
    }

    /**
     * Handle price query.
     */
    private function handlePriceQuery(string $message, array $intent): array
    {
        preg_match('/([A-Z0-9][A-Z0-9\-\/\.]+[A-Z0-9])/i', $message, $matches);
        $mpn = $matches[1] ?? null;

        if (! $mpn) {
            return [
                'reply' => "Which product would you like pricing for? Please provide the MPN.",
                'structured' => ['type' => 'prompt'],
                'actions' => [],
                'confidence' => 0.5,
            ];
        }

        $normalized = $this->normalization->normalize($mpn)['normalized'];
        $product = \App\Models\Marketplace\Product::published()
            ->whereRaw("upper(replace(coalesce(mpn, ''), ' ', '')) = ?", [$normalized])
            ->first();

        if (! $product) {
            return [
                'reply' => "I couldn't find pricing for \"{$mpn}\". It may not be in our catalog.",
                'structured' => ['type' => 'not_found', 'mpn' => $mpn],
                'actions' => [],
                'confidence' => 0.5,
            ];
        }

        // Get pricing
        $pricingService = app(\App\Services\Bom\BomPricingService::class);
        $price = $pricingService->estimate(new \App\Models\Bom\BomProject(['id' => 0]), ['quantities' => [0 => 1]]);

        return [
            'reply' => "Pricing for {$product->name} ({$mpn}): Check the product page for current pricing and availability.",
            'structured' => [
                'type' => 'pricing',
                'product' => [
                    'id' => $product->id,
                    'name' => $product->name,
                    'mpn' => $product->mpn,
                ],
            ],
            'actions' => [
                ['type' => 'view_product', 'label' => 'View product'],
                ['type' => 'add_to_cart', 'label' => 'Add to cart'],
            ],
            'confidence' => 0.7,
        ];
    }

    /**
     * Handle stock query.
     */
    private function handleStockQuery(string $message, array $intent): array
    {
        preg_match('/([A-Z0-9][A-Z0-9\-\/\.]+[A-Z0-9])/i', $message, $matches);
        $mpn = $matches[1] ?? null;

        if (! $mpn) {
            return [
                'reply' => "Which product would you like stock information for?",
                'structured' => ['type' => 'prompt'],
                'actions' => [],
                'confidence' => 0.5,
            ];
        }

        return [
            'reply' => "Stock information for {$mpn}: Please check the product page for real-time availability across our warehouses.",
            'structured' => [
                'type' => 'stock_query',
                'mpn' => $mpn,
            ],
            'actions' => [
                ['type' => 'view_product', 'label' => 'Check availability'],
            ],
            'confidence' => 0.6,
        ];
    }

    /**
     * Handle RFQ request.
     */
    private function handleRfqRequest(string $message, ?int $userId, array $intent): array
    {
        if (! $userId) {
            return [
                'reply' => "Please log in to create an RFQ.",
                'structured' => ['type' => 'auth_required'],
                'actions' => [['type' => 'login', 'label' => 'Log in']],
                'confidence' => 0.9,
            ];
        }

        return [
            'reply' => "I can help you create an RFQ. Please provide the parts you need quoted, or upload a BOM file.",
            'structured' => ['type' => 'rfq_guide'],
            'actions' => [
                ['type' => 'create_rfq', 'label' => 'Start RFQ'],
                ['type' => 'upload_bom', 'label' => 'Upload BOM'],
            ],
            'confidence' => 0.8,
        ];
    }

    /**
     * Handle cart request.
     */
    private function handleCartRequest(string $message, ?int $userId, array $intent): array
    {
        if (! $userId) {
            return [
                'reply' => "Please log in to add items to your cart.",
                'structured' => ['type' => 'auth_required'],
                'actions' => [['type' => 'login', 'label' => 'Log in']],
                'confidence' => 0.9,
            ];
        }

        return [
            'reply' => "I can help you add items to your cart. What would you like to add?",
            'structured' => ['type' => 'cart_guide'],
            'actions' => [
                ['type' => 'search_products', 'label' => 'Search products'],
                ['type' => 'upload_bom', 'label' => 'Upload BOM'],
            ],
            'confidence' => 0.7,
        ];
    }

    /**
     * Handle technical question.
     */
    private function handleTechnicalQuestion(string $message, array $intent): array
    {
        return [
            'reply' => "For detailed technical specifications, please check the product page which includes datasheets, pinouts, and full specifications.",
            'structured' => ['type' => 'technical_guide'],
            'actions' => [
                ['type' => 'search_products', 'label' => 'Search products'],
            ],
            'confidence' => 0.6,
        ];
    }

    /**
     * Handle general query.
     */
    private function handleGeneralQuery(string $message, array $intent): array
    {
        return [
            'reply' => "I can help you with:\n- Searching for components by MPN\n- Finding alternatives\n- Processing BOMs\n- Creating RFQs\n- Checking prices and stock\n\nWhat would you like to do?",
            'structured' => ['type' => 'help'],
            'actions' => [
                ['type' => 'search_products', 'label' => 'Search products'],
                ['type' => 'upload_bom', 'label' => 'Upload BOM'],
                ['type' => 'create_rfq', 'label' => 'Create RFQ'],
            ],
            'confidence' => 0.5,
        ];
    }
}
