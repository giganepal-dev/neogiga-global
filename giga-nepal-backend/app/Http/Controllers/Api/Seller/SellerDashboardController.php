<?php

namespace App\Http\Controllers\Api\Seller;

use App\Http\Controllers\Concerns\ApiResponses;
use App\Http\Controllers\Controller;
use App\Services\Seller\SellerContextService;
use App\Services\Seller\SellerDashboardService;
use App\Services\Seller\SellerOnboardingService;
use App\Services\Seller\MpnMatchingService;
use App\Models\Marketplace\SellerOffer;
use App\Models\Marketplace\VendorWarehouse;
use App\Models\Marketplace\SellerShipment;
use App\Models\Marketplace\VendorOrder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class SellerDashboardController extends Controller
{
    use ApiResponses;

    public function __construct(
        private readonly SellerContextService $context, 
        private readonly SellerDashboardService $dashboard,
        private readonly SellerOnboardingService $onboarding,
        private readonly MpnMatchingService $mpnMatching
    )
    {
    }

    public function dashboard(Request $request): JsonResponse
    {
        return $this->overview($request);
    }

    public function overview(Request $request): JsonResponse
    {
        $vendor = $this->context->abortUnlessVendor($request->user());
        $application = $request->user()->sellerApplication;

        $readinessPercentage = $application ? $this->onboarding->calculateReadinessPercentage($application) : 0;

        return $this->success(array_merge($this->dashboard->overview($vendor), [
            'readiness_percentage' => $readinessPercentage,
            'onboarding_steps' => $application ? $this->onboarding->getOnboardingSteps($application) : [],
        ]));
    }

    public function salesSummary(Request $request): JsonResponse
    {
        return $this->success($this->dashboard->orderSummary($this->context->abortUnlessVendor($request->user())));
    }

    public function orderSummary(Request $request): JsonResponse
    {
        return $this->salesSummary($request);
    }

    public function productSummary(Request $request): JsonResponse
    {
        return $this->success($this->dashboard->productSummary($this->context->abortUnlessVendor($request->user())));
    }

    public function inventorySummary(Request $request): JsonResponse
    {
        return $this->success($this->dashboard->inventorySummary($this->context->abortUnlessVendor($request->user())));
    }

    public function payoutSummary(Request $request): JsonResponse
    {
        return $this->success($this->dashboard->payoutSummary($this->context->abortUnlessVendor($request->user())));
    }

    public function alerts(Request $request): JsonResponse
    {
        $vendor = $this->context->abortUnlessVendor($request->user());
        
        $alerts = $this->dashboard->alerts($vendor);
        
        // Add low stock alerts from offers
        $lowStockOffers = SellerOffer::where('seller_id', $vendor->id)
            ->where('approval_status', 'approved')
            ->where('is_published', true)
            ->whereColumn('stock_quantity', '<=', 'reserved_quantity')
            ->orWhere('stock_quantity', '<', 10)
            ->limit(5)
            ->get();

        foreach ($lowStockOffers as $offer) {
            $alerts['low_stock'][] = [
                'offer_id' => $offer->id,
                'product_name' => $offer->canonicalProduct->title ?? 'Unknown Product',
                'available_quantity' => $offer->available_quantity,
                'warehouse_name' => $offer->warehouse->name ?? 'Unknown Warehouse',
            ];
        }

        // Add pending shipments
        $pendingShipments = SellerShipment::whereHas('orderItem.offer', function ($q) use ($vendor) {
                $q->where('seller_id', $vendor->id);
            })
            ->whereIn('status', ['pending', 'processing'])
            ->where('expected_ship_date', '<', now())
            ->orderBy('expected_ship_date')
            ->limit(5)
            ->get();

        foreach ($pendingShipments as $shipment) {
            $alerts['overdue_shipments'][] = [
                'shipment_id' => $shipment->id,
                'order_number' => $shipment->orderItem->order->order_number ?? null,
                'expected_ship_date' => $shipment->expected_ship_date,
                'days_overdue' => now()->diffInDays($shipment->expected_ship_date),
            ];
        }

        return $this->success($alerts);
    }

    /**
     * Get notifications for the seller
     */
    public function notifications(Request $request): JsonResponse
    {
        $vendor = $this->context->abortUnlessVendor($request->user());
        $user = $request->user();

        $notifications = $user->notifications()
            ->whereJsonContains('data->vendor_id', $vendor->id)
            ->orWhere('notifiable_type', get_class($user))
            ->latest()
            ->limit(50)
            ->get();

        return $this->success([
            'notifications' => $notifications,
            'unread_count' => $user->unreadNotifications()->count(),
        ]);
    }

    /**
     * Mark notification as read
     */
    public function markNotificationRead(Request $request, string $notificationId): JsonResponse
    {
        $user = $request->user();
        $notification = $user->notifications()->where('id', $notificationId)->firstOrFail();
        
        $notification->markAsRead();

        return $this->success(['message' => 'Notification marked as read']);
    }

    /**
     * Mark all notifications as read
     */
    public function markAllNotificationsRead(Request $request): JsonResponse
    {
        $user = $request->user();
        $user->unreadNotifications()->update(['read_at' => now()]);

        return $this->success(['message' => 'All notifications marked as read']);
    }
}
