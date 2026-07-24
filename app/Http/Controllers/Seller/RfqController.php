<?php

namespace App\Http\Controllers\Seller;

use App\Http\Controllers\Controller;
use App\Models\Rfq;
use App\Models\Quotation;
use App\Models\SellerOffer;
use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class RfqController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth:vendor');
    }

    public function index(Request $request)
    {
        $vendor = Auth::guard('vendor')->user();
        
        $query = Rfq::where('status', 'open')
            ->whereHas('marketplace', function($q) use ($vendor) {
                // Only show RFQs from marketplaces seller is approved for
                $q->whereIn('id', function($q2) use ($vendor) {
                    return \App\Models\SellerApplication::where('seller_id', $vendor->id)
                        ->where('status', 'approved')
                        ->pluck('marketplace_id');
                });
            });

        if ($request->filled('product_id')) {
            $query->where('product_id', $request->product_id);
        }

        if ($request->filled('category_id')) {
            $query->whereHas('product.category', function($q) use ($request) {
                $q->where('id', $request->category_id);
            });
        }

        $rfqs = $query->with(['product', 'customer', 'marketplace'])
            ->latest()
            ->paginate(20);

        return view('seller.rfqs.index', compact('rfqs'));
    }

    public function show($id)
    {
        $vendor = Auth::guard('vendor')->user();
        
        $rfq = Rfq::findOrFail($id);
        
        // Check if seller already submitted quotation
        $existingQuotation = Quotation::where('rfq_id', $id)
            ->where('seller_id', $vendor->id)
            ->first();

        return view('seller.rfqs.show', compact('rfq', 'existingQuotation'));
    }

    public function quotations()
    {
        $vendor = Auth::guard('vendor')->user();
        
        $quotations = Quotation::where('seller_id', $vendor->id)
            ->with(['rfq.product', 'rfq.customer'])
            ->latest()
            ->paginate(20);

        $stats = [
            'total' => Quotation::where('seller_id', $vendor->id)->count(),
            'pending' => Quotation::where('seller_id', $vendor->id)->where('status', 'pending')->count(),
            'accepted' => Quotation::where('seller_id', $vendor->id)->where('status', 'accepted')->count(),
            'declined' => Quotation::where('seller_id', $vendor->id)->where('status', 'declined')->count(),
        ];

        return view('seller.rfqs.quotations', compact('quotations', 'stats'));
    }

    public function submitQuotation(Request $request, $rfqId)
    {
        $vendor = Auth::guard('vendor')->user();
        
        $rfq = Rfq::findOrFail($rfqId);

        if ($rfq->status !== 'open') {
            return back()->withErrors(['error' => 'This RFQ is no longer accepting quotations.']);
        }

        // Check if already submitted
        $existing = Quotation::where('rfq_id', $rfqId)
            ->where('seller_id', $vendor->id)
            ->first();

        if ($existing) {
            return back()->withErrors(['error' => 'You have already submitted a quotation for this RFQ.']);
        }

        $validated = $request->validate([
            'price' => 'required|numeric|min:0',
            'currency' => 'required|string|size:3',
            'quantity' => 'required|integer|min:1',
            'moq' => 'nullable|integer|min:1',
            'lead_time_days' => 'required|integer|min:0',
            'date_code' => 'nullable|string|max:50',
            'condition' => 'required|in:new,refurbished,used',
            'payment_terms' => 'nullable|string|max:500',
            'shipping_terms' => 'nullable|string|max:500',
            'validity_days' => 'required|integer|min:1',
            'notes' => 'nullable|string|max:2000',
            'attachments.*' => 'nullable|file|mimes:pdf,jpg,jpeg,png,doc,docx,xls,xlsx|max:10240',
        ]);

        DB::beginTransaction();
        try {
            $quotation = Quotation::create([
                'rfq_id' => $rfqId,
                'seller_id' => $vendor->id,
                'price' => $validated['price'],
                'currency' => $validated['currency'],
                'quantity' => $validated['quantity'],
                'moq' => $validated['moq'] ?? 1,
                'lead_time_days' => $validated['lead_time_days'],
                'date_code' => $validated['date_code'] ?? null,
                'condition' => $validated['condition'],
                'payment_terms' => $validated['payment_terms'] ?? null,
                'shipping_terms' => $validated['shipping_terms'] ?? null,
                'validity_days' => $validated['validity_days'],
                'expires_at' => now()->addDays($validated['validity_days']),
                'notes' => $validated['notes'] ?? null,
                'status' => 'pending',
            ]);

            // Handle attachments
            if ($request->hasFile('attachments')) {
                foreach ($request->file('attachments') as $attachment) {
                    $path = $attachment->store('quotations/' . $quotation->id, 'public');
                    
                    \App\Models\QuotationAttachment::create([
                        'quotation_id' => $quotation->id,
                        'file_path' => $path,
                        'file_name' => $attachment->getClientOriginalName(),
                    ]);
                }
            }

            event(new \App\Events\SellerQuotationSubmitted($quotation, $rfq));

            DB::commit();

            return redirect()->route('seller.rfqs.quotations')
                ->with('success', 'Quotation submitted successfully.');
        } catch (\Exception $e) {
            DB::rollBack();
            return back()->withErrors(['error' => 'Failed to submit quotation: ' . $e->getMessage()]);
        }
    }

    public function reviseQuotation($id)
    {
        $vendor = Auth::guard('vendor')->user();
        
        $quotation = Quotation::where('seller_id', $vendor->id)->findOrFail($id);

        if (!in_array($quotation->status, ['pending', 'declined'])) {
            return back()->withErrors(['error' => 'Cannot revise quotation in current status.']);
        }

        return view('seller.rfqs.revise', compact('quotation'));
    }

    public function updateQuotation(Request $request, $id)
    {
        $vendor = Auth::guard('vendor')->user();
        
        $quotation = Quotation::where('seller_id', $vendor->id)->findOrFail($id);

        $validated = $request->validate([
            'price' => 'required|numeric|min:0',
            'quantity' => 'required|integer|min:1',
            'lead_time_days' => 'required|integer|min:0',
            'notes' => 'nullable|string|max:2000',
        ]);

        $quotation->update([
            'price' => $validated['price'],
            'quantity' => $validated['quantity'],
            'lead_time_days' => $validated['lead_time_days'],
            'notes' => $validated['notes'] ?? $quotation->notes,
            'revision_count' => $quotation->revision_count + 1,
        ]);

        event(new \App\Events\SellerQuotationRevised($quotation));

        return redirect()->route('seller.rfqs.quotations')
            ->with('success', 'Quotation revised successfully.');
    }

    public function acceptQuotation($id)
    {
        $vendor = Auth::guard('vendor')->user();
        
        $quotation = Quotation::where('seller_id', $vendor->id)->findOrFail($id);

        if ($quotation->status !== 'pending') {
            return back()->withErrors(['error' => 'Cannot accept quotation in current status.']);
        }

        // Seller cannot accept their own quotation - this is done by buyer
        return back()->withErrors(['error' => 'Only the buyer can accept quotations.']);
    }

    public function declineQuotation($id)
    {
        $vendor = Auth::guard('vendor')->user();
        
        $quotation = Quotation::where('seller_id', $vendor->id)->findOrFail($id);

        if ($quotation->status !== 'pending') {
            return back()->withErrors(['error' => 'Cannot decline quotation in current status.']);
        }

        $quotation->status = 'declined';
        $quotation->save();

        event(new \App\Events\SellerQuotationDeclined($quotation));

        return redirect()->route('seller.rfqs.quotations')
            ->with('success', 'Quotation declined.');
    }
}
