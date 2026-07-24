<?php

namespace App\Http\Controllers\Seller;

use App\Http\Controllers\Controller;
use App\Models\VendorPayout;
use App\Models\Order;
use App\Models\SellerOffer;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class FinanceController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth:vendor');
    }

    public function dashboard()
    {
        $vendor = Auth::guard('vendor')->user();
        
        $payouts = VendorPayout::where('seller_id', $vendor->id);
        
        $stats = [
            'pending_balance' => VendorPayout::where('seller_id', $vendor->id)
                ->where('status', 'pending')
                ->sum('amount'),
            'processing_balance' => VendorPayout::where('seller_id', $vendor->id)
                ->where('status', 'processing')
                ->sum('amount'),
            'paid_total' => VendorPayout::where('seller_id', $vendor->id)
                ->where('status', 'paid')
                ->sum('amount'),
            'hold_balance' => VendorPayout::where('seller_id', $vendor->id)
                ->whereIn('status', ['on_hold', 'disputed'])
                ->sum('amount'),
        ];

        $recentPayouts = (clone $payouts)->latest()->take(10)->get();

        return view('seller.finance.dashboard', compact('stats', 'recentPayouts'));
    }

    public function payouts(Request $request)
    {
        $vendor = Auth::guard('vendor')->user();
        
        $query = VendorPayout::where('seller_id', $vendor->id);

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        if ($request->filled('date_from')) {
            $query->where('created_at', '>=', $request->date_from);
        }

        if ($request->filled('date_to')) {
            $query->where('created_at', '<=', $request->date_to);
        }

        $payouts = $query->latest()->paginate(20);

        return view('seller.finance.payouts', compact('payouts'));
    }

    public function showPayout($id)
    {
        $vendor = Auth::guard('vendor')->user();
        
        $payout = VendorPayout::where('seller_id', $vendor->id)->findOrFail($id);
        
        $payout->load(['orders', 'adjustments']);

        return view('seller.finance.payout-show', compact('payout'));
    }

    public function statements()
    {
        $vendor = Auth::guard('vendor')->user();
        
        // Generate monthly statements
        $statements = [];
        
        for ($i = 0; $i < 12; $i++) {
            $startDate = now()->subMonths($i)->startOfMonth();
            $endDate = now()->subMonths($i)->endOfMonth();
            
            $orders = Order::whereHas('items', function($q) use ($vendor) {
                $q->whereHas('offer', function($q2) use ($vendor) {
                    $q2->where('seller_id', $vendor->id);
                });
            })
            ->whereBetween('delivered_at', [$startDate, $endDate])
            ->get();

            $grossSales = $orders->sum(function($order) use ($vendor) {
                return $order->items->filter(function($item) use ($vendor) {
                    return $item->offer && $item->offer->seller_id === $vendor->id;
                })->sum('total');
            });

            $commissions = $orders->sum(function($order) use ($vendor) {
                return $order->items->filter(function($item) use ($vendor) {
                    return $item->offer && $item->offer->seller_id === $vendor->id;
                })->sum('commission_amount');
            });

            $refunds = $orders->where('status', 'refunded')->sum(function($order) use ($vendor) {
                return $order->items->filter(function($item) use ($vendor) {
                    return $item->offer && $item->offer->seller_id === $vendor->id;
                })->sum('total');
            });

            if ($grossSales > 0) {
                $statements[] = [
                    'period' => $startDate->format('F Y'),
                    'start_date' => $startDate,
                    'end_date' => $endDate,
                    'gross_sales' => $grossSales,
                    'commissions' => $commissions,
                    'refunds' => $refunds,
                    'net_earnings' => $grossSales - $commissions - $refunds,
                    'order_count' => $orders->count(),
                ];
            }
        }

        return view('seller.finance.statements', compact('statements'));
    }

    public function commissions()
    {
        $vendor = Auth::guard('vendor')->user();
        
        $orders = Order::whereHas('items', function($q) use ($vendor) {
            $q->whereHas('offer', function($q2) use ($vendor) {
                $q2->where('seller_id', $vendor->id);
            });
        })
        ->whereNotNull('delivered_at')
        ->with(['items.offer.product'])
        ->latest()
        ->paginate(20);

        $totalCommissions = $orders->flatMap(function($order) use ($vendor) {
            return $order->items->filter(function($item) use ($vendor) {
                return $item->offer && $item->offer->seller_id === $vendor->id;
            });
        })->sum('commission_amount');

        return view('seller.finance.commissions', compact('orders', 'totalCommissions'));
    }

    public function taxes()
    {
        $vendor = Auth::guard('vendor')->user();
        
        $currentYear = date('Y');
        
        // Get all paid orders for tax calculation
        $orders = Order::whereHas('items', function($q) use ($vendor) {
            $q->whereHas('offer', function($q2) use ($vendor) {
                $q2->where('seller_id', $vendor->id);
            });
        })
        ->whereYear('delivered_at', $currentYear)
        ->whereNotIn('status', ['cancelled', 'refunded'])
        ->get();

        $taxSummary = [];
        
        foreach ($orders as $order) {
            $sellerItems = $order->items->filter(function($item) use ($vendor) {
                return $item->offer && $item->offer->seller_id === $vendor->id;
            });

            foreach ($sellerItems as $item) {
                $marketplace = $item->offer->marketplace?->name ?? 'Global';
                
                if (!isset($taxSummary[$marketplace])) {
                    $taxSummary[$marketplace] = [
                        'marketplace' => $marketplace,
                        'gross_sales' => 0,
                        'tax_collected' => 0,
                        'order_count' => 0,
                    ];
                }

                $taxSummary[$marketplace]['gross_sales'] += $item->total;
                $taxSummary[$marketplace]['tax_collected'] += $item->tax_amount ?? 0;
                $taxSummary[$marketplace]['order_count']++;
            }
        }

        return view('seller.finance.taxes', compact('taxSummary', 'currentYear'));
    }

    public function invoices()
    {
        $vendor = Auth::guard('vendor')->user();
        
        $invoices = VendorPayout::where('seller_id', $vendor->id)
            ->where('status', 'paid')
            ->whereNotNull('invoice_path')
            ->latest()
            ->paginate(20);

        return view('seller.finance.invoices', compact('invoices'));
    }

    public function downloadInvoice($id)
    {
        $vendor = Auth::guard('vendor')->user();
        
        $payout = VendorPayout::where('seller_id', $vendor->id)->findOrFail($id);

        if (!$payout->invoice_path) {
            return back()->withErrors(['error' => 'Invoice not available.']);
        }

        return response()->download(storage_path('app/public/' . $payout->invoice_path));
    }

    public function export(Request $request)
    {
        $vendor = Auth::guard('vendor')->user();
        
        $validated = $request->validate([
            'date_from' => 'required|date',
            'date_to' => 'required|date|after_or_equal:date_from',
            'type' => 'required|in:payouts,orders,commissions',
        ]);

        $data = [];

        if ($validated['type'] === 'payouts') {
            $records = VendorPayout::where('seller_id', $vendor->id)
                ->whereBetween('created_at', [$validated['date_from'], $validated['date_to']])
                ->get();

            foreach ($records as $record) {
                $data[] = [
                    'Date' => $record->created_at->format('Y-m-d'),
                    'Reference' => $record->reference_number,
                    'Amount' => $record->amount,
                    'Status' => $record->status,
                    'Notes' => $record->notes,
                ];
            }
        } elseif ($validated['type'] === 'orders') {
            $orders = Order::whereHas('items', function($q) use ($vendor) {
                $q->whereHas('offer', function($q2) use ($vendor) {
                    $q2->where('seller_id', $vendor->id);
                });
            })
            ->whereBetween('created_at', [$validated['date_from'], $validated['date_to']])
            ->get();

            foreach ($orders as $order) {
                $sellerItems = $order->items->filter(function($item) use ($vendor) {
                    return $item->offer && $item->offer->seller_id === $vendor->id;
                });

                $data[] = [
                    'Order Number' => $order->order_number,
                    'Date' => $order->created_at->format('Y-m-d'),
                    'Status' => $order->status,
                    'Total' => $sellerItems->sum('total'),
                    'Commission' => $sellerItems->sum('commission_amount'),
                    'Net' => $sellerItems->sum('total') - $sellerItems->sum('commission_amount'),
                ];
            }
        }

        // Generate CSV
        $csv = \League\Csv\Writer::createFromFileObject(new \SplTempFileObject());
        
        if (!empty($data)) {
            $csv->insertOne(array_keys($data[0]));
            foreach ($data as $row) {
                $csv->insertOne($row);
            }
        }

        $filename = 'seller_' . $validated['type'] . '_' . date('Y-m-d') . '.csv';
        
        return $csv->download($filename);
    }
}
