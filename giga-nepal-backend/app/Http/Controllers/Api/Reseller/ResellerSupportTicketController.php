<?php

namespace App\Http\Controllers\Api\Reseller;

use App\Http\Controllers\Concerns\ApiResponses;
use App\Http\Controllers\Controller;
use App\Models\ResellerSupportTicket;
use App\Services\Reseller\ResellerContextService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ResellerSupportTicketController extends Controller
{
    use ApiResponses;

    public function index(Request $request, ResellerContextService $context): JsonResponse
    {
        $reseller = $context->abortUnlessReseller($request->user());

        return $this->success(
            ResellerSupportTicket::query()->where('reseller_id', $reseller->id)->latest()->paginate(25)
        );
    }

    public function store(Request $request, ResellerContextService $context): JsonResponse
    {
        $reseller = $context->abortUnlessReseller($request->user());
        $data = $request->validate([
            'subject' => ['required', 'string', 'max:190'],
            'body' => ['required', 'string', 'max:5000'],
            'priority' => ['nullable', 'string', 'max:20'],
        ]);

        $ticket = ResellerSupportTicket::create([
            ...$data,
            'reseller_id' => $reseller->id,
            'user_id' => $request->user()->id,
            'ticket_number' => 'RST-'.now()->format('YmdHis').'-'.$reseller->id,
            'status' => 'open',
        ]);

        return $this->success($ticket, 201);
    }
}
