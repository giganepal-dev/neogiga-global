<?php

namespace App\Services\Seller;

use App\Models\Marketplace\CanonicalProduct;
use App\Models\Marketplace\Product;
use App\Models\Marketplace\ProductBrand;
use App\Models\Marketplace\ProductCategory;
use App\Models\Marketplace\SellerOffer;
use App\Models\Marketplace\Vendor;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Database\Eloquent\Collection;

class MpnMatchingService
{
    /**
     * Search for products by MPN with normalization
     */
    public function searchByMpn(string $mpn, ?int $limit = 20): Collection
    {
        $normalizedMpn = $this->normalizeMpn($mpn);
        $originalMpn = trim($mpn);

        // Search strategies in priority order
        $results = collect();

        // 1. Exact MPN match
        $exact = CanonicalProduct::where('mpn', $originalMpn)
            ->orWhere('normalized_mpn', $normalizedMpn)
            ->limit($limit)
            ->get();
        $results = $results->merge($exact);

        // 2. Normalized MPN match
        if ($normalizedMpn !== $originalMpn) {
            $normalized = CanonicalProduct::where('normalized_mpn', $normalizedMpn)
                ->whereNotIn('id', $exact->pluck('id'))
                ->limit($limit - $results->count())
                ->get();
            $results = $results->merge($normalized);
        }

        // 3. Manufacturer + MPN combination
        if (strpos($originalMpn, ' ') !== false || strpos($originalMpn, '-') !== false) {
            $parts = preg_split('/[\s\-]+/', $originalMpn, 2);
            if (count($parts) === 2) {
                $manufacturerPart = $parts[0];
                $mpnPart = $parts[1];

                $combined = CanonicalProduct::whereHas('brand', function ($q) use ($manufacturerPart) {
                        $q->where('name', 'LIKE', "%{$manufacturerPart}%");
                    })
                    ->where(function ($q) use ($mpnPart, $normalizedMpn) {
                        $q->where('mpn', 'LIKE', "%{$mpnPart}%")
                          ->orWhere('normalized_mpn', 'LIKE', "%{$normalizedMpn}%");
                    })
                    ->limit($limit - $results->count())
                    ->get();
                $results = $results->merge($combined);
            }
        }

        // 4. Alias/part number match
        $aliases = DB::table('product_part_numbers')
            ->where('part_number', $originalMpn)
            ->orWhere('part_number', $normalizedMpn)
            ->limit($limit - $results->count())
            ->get();

        if ($aliases->count() > 0) {
            $aliasIds = $aliases->pluck('product_id');
            $aliasProducts = CanonicalProduct::whereIn('id', $aliasIds)
                ->whereNotIn('id', $results->pluck('id'))
                ->get();
            $results = $results->merge($aliasProducts);
        }

        // 5. Fuzzy search on title
        if ($results->count() < $limit) {
            $keywords = $this->extractKeywords($mpn);
            $fuzzy = CanonicalProduct::where(function ($q) use ($keywords) {
                foreach ($keywords as $keyword) {
                    $q->orWhere('title', 'LIKE', "%{$keyword}%")
                      ->orWhere('short_description', 'LIKE', "%{$keyword}%");
                }
            })
            ->whereNotIn('id', $results->pluck('id'))
            ->limit($limit - $results->count())
            ->get();
            $results = $results->merge($fuzzy);
        }

        return $results->unique('id')->take($limit);
    }

    /**
     * Normalize MPN for consistent matching
     */
    public function normalizeMpn(string $mpn): string
    {
        // Remove special characters except hyphens and underscores
        $normalized = preg_replace('/[^a-zA-Z0-9\-_]/', '', strtoupper(trim($mpn)));
        
        // Remove leading zeros from numeric segments
        $normalized = preg_replace_callback('/(^|-)(0+)(\d)/', function ($matches) {
            return $matches[1] . $matches[3];
        }, $normalized);

        // Standardize common substitutions
        $replacements = [
            'ZERO' => '0',
            'ONE' => '1',
            'TWO' => '2',
            'THREE' => '3',
            'FOUR' => '4',
            'FIVE' => '5',
            'SIX' => '6',
            'SEVEN' => '7',
            'EIGHT' => '8',
            'NINE' => '9',
        ];

        $normalized = str_replace(array_keys($replacements), array_values($replacements), $normalized);

        return $normalized;
    }

    /**
     * Check if a product already has an offer from this seller
     */
    public function hasExistingOffer(Vendor $vendor, int $canonicalProductId, ?int $warehouseId = null): bool
    {
        $query = SellerOffer::where('seller_id', $vendor->id)
            ->where('canonical_product_id', $canonicalProductId)
            ->whereIn('status', ['active', 'pending']);

        if ($warehouseId) {
            $query->where('warehouse_id', $warehouseId);
        }

        return $query->exists();
    }

    /**
     * Create a seller offer for an existing canonical product
     */
    public function createOffer(User $user, array $data): SellerOffer
    {
        $vendor = Vendor::where('user_id', $user->id)->firstOrFail();

        // Validate canonical product exists
        $canonicalProduct = CanonicalProduct::findOrFail($data['canonical_product_id']);

        // Check for duplicate offers
        if ($this->hasExistingOffer($vendor, $canonicalProduct->id, $data['warehouse_id'] ?? null)) {
            throw new \Exception('Seller already has an active offer for this product');
        }

        return DB::transaction(function () use ($vendor, $data, $canonicalProduct) {
            $offer = SellerOffer::create([
                'canonical_product_id' => $data['canonical_product_id'],
                'variation_id' => $data['variation_id'] ?? null,
                'seller_id' => $vendor->id,
                'warehouse_id' => $data['warehouse_id'],
                'marketplace_id' => $data['marketplace_id'],
                'base_price' => $data['base_price'],
                'sale_price' => $data['sale_price'] ?? null,
                'cost_price' => $data['cost_price'] ?? null,
                'currency_code' => $data['currency_code'] ?? 'USD',
                'moq' => $data['moq'] ?? 1,
                'order_multiple' => $data['order_multiple'] ?? 1,
                'max_order_qty' => $data['max_order_qty'] ?? null,
                'stock_quantity' => $data['stock_quantity'] ?? 0,
                'reserved_quantity' => 0,
                'incoming_quantity' => $data['incoming_quantity'] ?? 0,
                'allow_backorder' => $data['allow_backorder'] ?? false,
                'backorder_limit' => $data['backorder_limit'] ?? null,
                'lead_time_days' => $data['lead_time_days'] ?? 7,
                'fulfillment_type' => $data['fulfillment_type'] ?? 'ship_from_stock',
                'status' => 'active',
                'approval_status' => 'pending',
                'is_published' => false,
                'seller_sku' => $data['seller_sku'] ?? null,
                'seller_notes' => $data['seller_notes'] ?? null,
                'condition_grade' => $data['condition_grade'] ?? 'new',
                'packaging_type' => $data['packaging_type'] ?? 'original',
                'country_of_origin' => $data['country_of_origin'] ?? null,
                'warranty_type' => $data['warranty_type'] ?? null,
                'warranty_period' => $data['warranty_period'] ?? null,
                'created_by' => $user->id,
            ]);

            // Log inventory movement for initial stock
            if ($offer->stock_quantity > 0) {
                $this->logInventoryMovement($offer, 'opening_balance', $offer->stock_quantity, $user->id);
            }

            return $offer;
        });
    }

    /**
     * Add stock to an existing offer
     */
    public function addStock(SellerOffer $offer, int $quantity, User $user, string $reason = 'manual_increase'): SellerOffer
    {
        if ($quantity <= 0) {
            throw new \Exception('Quantity must be positive');
        }

        return DB::transaction(function () use ($offer, $quantity, $user, $reason) {
            $offer->increment('stock_quantity', $quantity);
            
            $this->logInventoryMovement($offer, $reason, $quantity, $user->id);

            return $offer->fresh();
        });
    }

    /**
     * Reserve stock for an order
     */
    public function reserveStock(SellerOffer $offer, int $quantity): bool
    {
        if ($offer->available_quantity < $quantity) {
            return false;
        }

        return DB::transaction(function () use ($offer, $quantity) {
            $offer->increment('reserved_quantity', $quantity);
            
            $this->logInventoryMovement($offer, 'reservation', -$quantity, null, [
                'reservation_type' => 'order',
            ]);

            return true;
        });
    }

    /**
     * Release reserved stock
     */
    public function releaseReservedStock(SellerOffer $offer, int $quantity): void
    {
        DB::transaction(function () use ($offer, $quantity) {
            $offer->decrement('reserved_quantity', min($quantity, $offer->reserved_quantity));
            
            $this->logInventoryMovement($offer, 'reservation_release', $quantity, null, [
                'release_reason' => 'order_cancelled',
            ]);
        });
    }

    /**
     * Deduct stock after fulfillment
     */
    public function fulfillStock(SellerOffer $offer, int $quantity): void
    {
        DB::transaction(function () use ($offer, $quantity) {
            // First reduce reserved quantity if any
            $reservedToRelease = min($quantity, $offer->reserved_quantity);
            if ($reservedToRelease > 0) {
                $offer->decrement('reserved_quantity', $reservedToRelease);
            }

            // Then reduce actual stock
            $stockToDeduct = $quantity - $reservedToRelease;
            if ($stockToDeduct > 0) {
                $offer->decrement('stock_quantity', $stockToDeduct);
            }

            $this->logInventoryMovement($offer, 'fulfillment', -$quantity, null, [
                'fulfillment_type' => 'order_shipped',
            ]);
        });
    }

    /**
     * Log inventory movement
     */
    protected function logInventoryMovement(
        SellerOffer $offer, 
        string $movementType, 
        int $quantityChange, 
        ?int $userId, 
        array $metadata = []
    ): void {
        \App\Models\Marketplace\SellerInventoryMovement::create([
            'seller_offer_id' => $offer->id,
            'warehouse_id' => $offer->warehouse_id,
            'movement_type' => $movementType,
            'quantity_change' => $quantityChange,
            'quantity_before' => $offer->stock_quantity - $quantityChange,
            'quantity_after' => $offer->stock_quantity,
            'reserved_before' => $offer->reserved_quantity - ($movementType === 'reservation' ? abs($quantityChange) : 0),
            'reserved_after' => $offer->reserved_quantity,
            'reference_type' => $metadata['reference_type'] ?? null,
            'reference_id' => $metadata['reference_id'] ?? null,
            'notes' => $metadata['notes'] ?? null,
            'metadata' => $metadata,
            'created_by' => $userId,
        ]);
    }

    /**
     * Extract keywords from MPN for fuzzy search
     */
    protected function extractKeywords(string $mpn): array
    {
        // Split by common delimiters
        $keywords = preg_split('/[\s\-_,.]+/', $mpn);
        
        // Filter out very short keywords
        return array_filter($keywords, fn($k) => strlen($k) >= 2);
    }

    /**
     * Get duplicate prevention rules
     */
    public function checkDuplicatePrevention(Vendor $vendor, int $canonicalProductId, ?int $warehouseId = null): array
    {
        $existingOffers = SellerOffer::where('seller_id', $vendor->id)
            ->where('canonical_product_id', $canonicalProductId)
            ->get();

        $duplicates = [];
        foreach ($existingOffers as $offer) {
            $duplicates[] = [
                'offer_id' => $offer->id,
                'warehouse_id' => $offer->warehouse_id,
                'marketplace_id' => $offer->marketplace_id,
                'status' => $offer->status,
                'approval_status' => $offer->approval_status,
            ];
        }

        return [
            'has_duplicates' => count($duplicates) > 0,
            'duplicates' => $duplicates,
            'can_create' => count($duplicates) === 0,
            'message' => count($duplicates) > 0 
                ? 'Seller already has offers for this product' 
                : 'No duplicates found',
        ];
    }
}
