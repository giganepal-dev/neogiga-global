<?php

namespace App\Http\Controllers\Api\Seller;

use App\Http\Controllers\Concerns\ApiResponses;
use App\Http\Controllers\Controller;
use App\Http\Requests\Seller\SellerSupportTicketStoreRequest;
use App\Models\Marketplace\VendorSupportTicket;
use App\Services\Seller\SellerContextService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Schema;

class SellerSupportTicketController extends Controller
{
    use ApiResponses;

    public function __construct(private readonly SellerContextService $context)
    {
    }

    public function index(Request $request): JsonResponse
    {
        $vendor = $this->context->abortUnlessVendor($request->user());

        if (! Schema::hasTable('vendor_support_tickets')) {
            return $this->error('Vendor support ticket table is pending migration.', 503);
        }

        return $this->success(VendorSupportTicket::query()->where('vendor_id', $vendor->id)->latest()->paginate(25));
    }

    public function store(SellerSupportTicketStoreRequest $request): JsonResponse
    {
        $vendor = $this->context->abortUnlessVendor($request->user());

        if (! Schema::hasTable('vendor_support_tickets')) {
            return $this->error('Vendor support ticket table is pending migration.', 503);
        }

        $ticket = VendorSupportTicket::create([
            ...$request->validated(),
            'vendor_id' => $vendor->id,
            'user_id' => $request->user()->id,
            'ticket_number' => 'VST-' . now()->format('YmdHis') . '-' . $vendor->id,
            'status' => 'open',
        ]);

        return $this->success($ticket, 201);
    }
}
