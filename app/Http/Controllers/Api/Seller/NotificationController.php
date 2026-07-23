<?php

namespace App\Http\Controllers\Api\Seller;

use App\Http\Controllers\Controller;
use App\Models\SellerNotification;
use Illuminate\Http\Request;

class NotificationController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth:sanctum');
    }

    /**
     * Get all notifications for seller
     */
    public function index(Request $request)
    {
        $vendorId = auth()->user()->vendor_id;

        $query = SellerNotification::where('vendor_id', $vendorId)
            ->orWhere('recipient_type', 'all_sellers')
            ->orderBy('created_at', 'desc');

        if ($request->has('type')) {
            $query->where('type', $request->type);
        }

        if ($request->has('unread')) {
            $query->where('read_at', null);
        }

        return response()->json([
            'success' => true,
            'data' => $query->paginate(20),
        ]);
    }

    /**
     * Get unread notification count
     */
    public function unreadCount()
    {
        $vendorId = auth()->user()->vendor_id;

        $count = SellerNotification::where('vendor_id', $vendorId)
            ->whereNull('read_at')
            ->count();

        return response()->json([
            'success' => true,
            'data' => ['unread_count' => $count],
        ]);
    }

    /**
     * Mark notification as read
     */
    public function markAsRead(SellerNotification $notification)
    {
        $vendorId = auth()->user()->vendor_id;

        if ($notification->vendor_id !== $vendorId && $notification->recipient_type !== 'all_sellers') {
            return response()->json(['success' => false, 'message' => 'Unauthorized'], 403);
        }

        $notification->update(['read_at' => now()]);

        return response()->json([
            'success' => true,
            'message' => 'Notification marked as read.',
        ]);
    }

    /**
     * Mark all notifications as read
     */
    public function markAllAsRead()
    {
        $vendorId = auth()->user()->vendor_id;

        SellerNotification::where('vendor_id', $vendorId)
            ->whereNull('read_at')
            ->update(['read_at' => now()]);

        return response()->json([
            'success' => true,
            'message' => 'All notifications marked as read.',
        ]);
    }

    /**
     * Delete notification
     */
    public function destroy(SellerNotification $notification)
    {
        $vendorId = auth()->user()->vendor_id;

        if ($notification->vendor_id !== $vendorId) {
            return response()->json(['success' => false, 'message' => 'Unauthorized'], 403);
        }

        $notification->delete();

        return response()->json([
            'success' => true,
            'message' => 'Notification deleted.',
        ]);
    }
}
