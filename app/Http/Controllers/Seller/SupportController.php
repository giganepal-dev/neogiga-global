<?php

namespace App\Http\Controllers\Seller;

use App\Http\Controllers\Controller;
use App\Models\SupportTicket;
use App\Models\SupportTicketMessage;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class SupportController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth:vendor');
    }

    public function index(Request $request)
    {
        $vendor = Auth::guard('vendor')->user();
        
        $query = SupportTicket::where('seller_id', $vendor->id);

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        if ($request->filled('priority')) {
            $query->where('priority', $request->priority);
        }

        if ($request->filled('category')) {
            $query->where('category', $request->category);
        }

        $tickets = $query->with(['assignedAgent', 'latestMessage'])
            ->latest()
            ->paginate(20);

        $stats = [
            'total' => SupportTicket::where('seller_id', $vendor->id)->count(),
            'open' => SupportTicket::where('seller_id', $vendor->id)->where('status', 'open')->count(),
            'pending' => SupportTicket::where('seller_id', $vendor->id)->where('status', 'pending')->count(),
            'resolved' => SupportTicket::where('seller_id', $vendor->id)->where('status', 'resolved')->count(),
        ];

        return view('seller.support.index', compact('tickets', 'stats'));
    }

    public function create()
    {
        $categories = [
            'account' => 'Account & Verification',
            'products' => 'Products & Catalog',
            'orders' => 'Orders & Fulfillment',
            'payments' => 'Payments & Payouts',
            'technical' => 'Technical Issues',
            'compliance' => 'Compliance & Policy',
            'other' => 'Other',
        ];

        $priorities = [
            'low' => 'Low',
            'normal' => 'Normal',
            'high' => 'High',
            'urgent' => 'Urgent',
        ];

        return view('seller.support.create', compact('categories', 'priorities'));
    }

    public function store(Request $request)
    {
        $vendor = Auth::guard('vendor')->user();

        $validated = $request->validate([
            'subject' => 'required|string|max:255',
            'category' => 'required|in:account,products,orders,payments,technical,compliance,other',
            'priority' => 'required|in:low,normal,high,urgent',
            'message' => 'required|string|max:10000',
            'related_type' => 'nullable|in:order,payout,product,rfq',
            'related_id' => 'nullable|integer',
            'attachments.*' => 'nullable|file|mimes:pdf,jpg,jpeg,png,doc,docx,xls,xlsx,zip|max:10240',
        ]);

        DB::beginTransaction();
        try {
            $ticket = SupportTicket::create([
                'seller_id' => $vendor->id,
                'subject' => $validated['subject'],
                'category' => $validated['category'],
                'priority' => $validated['priority'],
                'status' => 'open',
                'related_type' => $validated['related_type'] ?? null,
                'related_id' => $validated['related_id'] ?? null,
            ]);

            // Create initial message
            $message = SupportTicketMessage::create([
                'ticket_id' => $ticket->id,
                'sender_type' => 'seller',
                'sender_id' => $vendor->id,
                'message' => $validated['message'],
                'is_internal' => false,
            ]);

            // Handle attachments
            if ($request->hasFile('attachments')) {
                foreach ($request->file('attachments') as $attachment) {
                    $path = $attachment->store('support/' . $ticket->id, 'public');
                    
                    \App\Models\SupportTicketAttachment::create([
                        'ticket_id' => $ticket->id,
                        'message_id' => $message->id,
                        'file_path' => $path,
                        'file_name' => $attachment->getClientOriginalName(),
                    ]);
                }
            }

            event(new \App\Events\SupportTicketCreated($ticket));

            DB::commit();

            return redirect()->route('seller.support.show', $ticket->id)
                ->with('success', 'Support ticket created. We will respond shortly.');
        } catch (\Exception $e) {
            DB::rollBack();
            return back()->withErrors(['error' => 'Failed to create ticket: ' . $e->getMessage()]);
        }
    }

    public function show($id)
    {
        $vendor = Auth::guard('vendor')->user();
        
        $ticket = SupportTicket::where('seller_id', $vendor->id)->findOrFail($id);
        
        $ticket->load(['messages.sender', 'assignedAgent']);

        $relatedEntity = null;
        if ($ticket->related_type && $ticket->related_id) {
            switch ($ticket->related_type) {
                case 'order':
                    $relatedEntity = \App\Models\Order::find($ticket->related_id);
                    break;
                case 'payout':
                    $relatedEntity = \App\Models\VendorPayout::find($ticket->related_id);
                    break;
                case 'product':
                    $relatedEntity = \App\Models\SellerOffer::find($ticket->related_id);
                    break;
                case 'rfq':
                    $relatedEntity = \App\Models\Rfq::find($ticket->related_id);
                    break;
            }
        }

        return view('seller.support.show', compact('ticket', 'relatedEntity'));
    }

    public function reply(Request $request, $id)
    {
        $vendor = Auth::guard('vendor')->user();
        
        $ticket = SupportTicket::where('seller_id', $vendor->id)->findOrFail($id);

        if (in_array($ticket->status, ['resolved', 'closed'])) {
            return back()->withErrors(['error' => 'Cannot reply to a resolved or closed ticket. Please reopen if needed.']);
        }

        $validated = $request->validate([
            'message' => 'required|string|max:10000',
            'attachments.*' => 'nullable|file|mimes:pdf,jpg,jpeg,png,doc,docx,xls,xlsx,zip|max:10240',
        ]);

        DB::beginTransaction();
        try {
            $message = SupportTicketMessage::create([
                'ticket_id' => $ticket->id,
                'sender_type' => 'seller',
                'sender_id' => $vendor->id,
                'message' => $validated['message'],
                'is_internal' => false,
            ]);

            // Handle attachments
            if ($request->hasFile('attachments')) {
                foreach ($request->file('attachments') as $attachment) {
                    $path = $attachment->store('support/' . $ticket->id, 'public');
                    
                    \App\Models\SupportTicketAttachment::create([
                        'ticket_id' => $ticket->id,
                        'message_id' => $message->id,
                        'file_path' => $path,
                        'file_name' => $attachment->getClientOriginalName(),
                    ]);
                }
            }

            // Update ticket status if it was pending
            if ($ticket->status === 'pending') {
                $ticket->status = 'open';
                $ticket->save();
            }

            event(new \App\Events\SupportTicketReplied($ticket, $message));

            DB::commit();

            return back()->with('success', 'Reply sent.');
        } catch (\Exception $e) {
            DB::rollBack();
            return back()->withErrors(['error' => 'Failed to send reply: ' . $e->getMessage()]);
        }
    }

    public function close($id)
    {
        $vendor = Auth::guard('vendor')->user();
        
        $ticket = SupportTicket::where('seller_id', $vendor->id)->findOrFail($id);

        if (!in_array($ticket->status, ['open', 'pending'])) {
            return back()->withErrors(['error' => 'Ticket is already closed or resolved.']);
        }

        $ticket->status = 'resolved';
        $ticket->resolved_at = now();
        $ticket->save();

        event(new \App\Events\SupportTicketClosed($ticket));

        return back()->with('success', 'Ticket marked as resolved.');
    }

    public function reopen($id)
    {
        $vendor = Auth::guard('vendor')->user();
        
        $ticket = SupportTicket::where('seller_id', $vendor->id)->findOrFail($id);

        if ($ticket->status !== 'resolved') {
            return back()->withErrors(['error' => 'Only resolved tickets can be reopened.']);
        }

        $ticket->status = 'open';
        $ticket->resolved_at = null;
        $ticket->save();

        event(new \App\Events\SupportTicketReopened($ticket));

        return back()->with('success', 'Ticket reopened.');
    }

    public function rate($id, Request $request)
    {
        $vendor = Auth::guard('vendor')->user();
        
        $ticket = SupportTicket::where('seller_id', $vendor->id)->findOrFail($id);

        if ($ticket->status !== 'resolved') {
            return back()->withErrors(['error' => 'Can only rate resolved tickets.']);
        }

        $validated = $request->validate([
            'rating' => 'required|integer|min:1|max:5',
            'feedback' => 'nullable|string|max:1000',
        ]);

        $ticket->update([
            'satisfaction_rating' => $validated['rating'],
            'satisfaction_feedback' => $validated['feedback'] ?? null,
        ]);

        return back()->with('success', 'Thank you for your feedback!');
    }
}
