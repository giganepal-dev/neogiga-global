<?php

namespace App\Http\Controllers\Api\Support;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

/**
 * Customer-facing support / chat API — the missing side of the EXISTING
 * support module (support_tickets / support_ticket_messages, admin inbox at
 * /admin/support, seller API via SellerSupportTicketController). Deliberately
 * NOT a parallel `conversations` schema: chat scopes ride on `category`
 * (support|product_qa|seller|general) and metadata (product/vendor refs,
 * needs_human AI-handoff flag). Transcript is append-only.
 */
class CustomerSupportController extends Controller
{
    private const CATEGORIES = ['support', 'product_qa', 'seller', 'general'];

    public function index(Request $request): JsonResponse
    {
        $tickets = DB::table('support_tickets')
            ->where('user_id', $request->user()->id)
            ->orderByDesc('id')
            ->limit(50)
            ->get(['id', 'ticket_number', 'subject', 'category', 'priority', 'status', 'created_at', 'updated_at']);

        return response()->json(['data' => $tickets]);
    }

    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'subject' => ['required', 'string', 'max:190'],
            'message' => ['required', 'string', 'max:5000'],
            'category' => ['nullable', 'in:' . implode(',', self::CATEGORIES)],
            'priority' => ['nullable', 'in:low,medium,high'],
            'product_id' => ['nullable', 'integer'],
            'vendor_id' => ['nullable', 'integer'],
        ]);

        $ticketId = DB::transaction(function () use ($request, $data) {
            $id = DB::table('support_tickets')->insertGetId([
                'ticket_number' => 'CST-' . now()->format('YmdHis') . '-' . random_int(100, 999),
                'user_id' => $request->user()->id,
                'subject' => $data['subject'],
                'description' => $data['message'],
                'priority' => $data['priority'] ?? 'medium',
                'status' => 'open',
                'category' => $data['category'] ?? 'support',
                'metadata' => json_encode([
                    'channel' => 'customer_api',
                    'product_id' => $data['product_id'] ?? null,
                    'vendor_id' => $data['vendor_id'] ?? null,
                    'needs_human' => false,
                ]),
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            DB::table('support_ticket_messages')->insert([
                'support_ticket_id' => $id,
                'user_id' => $request->user()->id,
                'sender_type' => 'customer',
                'message' => $data['message'],
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            return $id;
        });

        $ticket = DB::table('support_tickets')->where('id', $ticketId)
            ->first(['id', 'ticket_number', 'subject', 'category', 'priority', 'status', 'created_at']);

        return response()->json(['data' => $ticket], 201);
    }

    public function show(Request $request, int $id): JsonResponse
    {
        $ticket = $this->ownTicket($request, $id);

        $messages = DB::table('support_ticket_messages')
            ->where('support_ticket_id', $id)
            ->orderBy('id')
            ->get(['id', 'sender_type', 'message', 'created_at']);

        return response()->json(['data' => ['ticket' => $ticket, 'messages' => $messages]]);
    }

    public function reply(Request $request, int $id): JsonResponse
    {
        $ticket = $this->ownTicket($request, $id);

        $data = $request->validate(['message' => ['required', 'string', 'max:5000']]);

        DB::transaction(function () use ($request, $ticket, $data) {
            DB::table('support_ticket_messages')->insert([
                'support_ticket_id' => $ticket->id,
                'user_id' => $request->user()->id,
                'sender_type' => 'customer',
                'message' => $data['message'],
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            // A customer reply reopens resolved/closed threads and clears the
            // waiting-on-customer state.
            $newStatus = match ($ticket->status) {
                'resolved', 'closed' => 'open',
                'waiting_customer' => 'in_progress',
                default => $ticket->status,
            };
            DB::table('support_tickets')->where('id', $ticket->id)
                ->update(['status' => $newStatus, 'updated_at' => now()]);
        });

        return response()->json(['data' => ['status' => 'sent']], 201);
    }

    /** AI-handoff placeholder: flags the thread for a human agent. */
    public function requestHuman(Request $request, int $id): JsonResponse
    {
        $ticket = $this->ownTicket($request, $id);

        $meta = json_decode($ticket->metadata ?? '{}', true) ?: [];
        $meta['needs_human'] = true;
        $meta['handoff_requested_at'] = now()->toIso8601String();

        DB::table('support_tickets')->where('id', $ticket->id)->update([
            'metadata' => json_encode($meta),
            'priority' => in_array($ticket->priority, ['low', 'medium'], true) ? 'high' : $ticket->priority,
            'updated_at' => now(),
        ]);

        return response()->json(['data' => ['status' => 'human agent requested']]);
    }

    private function ownTicket(Request $request, int $id): object
    {
        $ticket = DB::table('support_tickets')
            ->where('id', $id)
            ->where('user_id', $request->user()->id)
            ->first();

        abort_unless($ticket, 404);

        return $ticket;
    }
}
