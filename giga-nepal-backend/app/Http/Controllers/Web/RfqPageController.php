<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\Marketplace\Product;
use App\Services\Erp\RfqService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\View\View;

/**
 * Public SSR bulk-RFQ form. Feeds the EXISTING RFQ module (rfq_requests /
 * rfq_items via RfqService) — the same pipeline the sales team answers with
 * quotations. Extra intake fields (country, required date, source page) are
 * stored in rfq_requests.meta; the initial status is recorded in the
 * rfq_status_histories audit trail.
 */
class RfqPageController extends Controller
{
    public function create(Request $request): View
    {
        $product = null;
        if ($slug = (string) $request->query('product', '')) {
            $product = Product::with('brand')->published()->where('slug', $slug)->first();
        }

        return view('frontend.rfq.create', ['product' => $product]);
    }

    public function store(Request $request, RfqService $rfqs): RedirectResponse
    {
        $data = $request->validate([
            'contact_name' => ['required', 'string', 'max:190'],
            'contact_email' => ['required', 'email', 'max:190'],
            'contact_phone' => ['nullable', 'string', 'max:40'],
            'company_name' => ['nullable', 'string', 'max:190'],
            'country' => ['nullable', 'string', 'max:100'],
            'product_slug' => ['nullable', 'string', 'max:190'],
            'item_name' => ['required', 'string', 'max:190'],
            'mpn' => ['nullable', 'string', 'max:120'],
            'quantity' => ['required', 'numeric', 'min:1'],
            'target_price' => ['nullable', 'numeric', 'min:0'],
            'required_date' => ['nullable', 'date', 'after_or_equal:today'],
            'message' => ['nullable', 'string', 'max:2000'],
        ]);

        $product = ! empty($data['product_slug'])
            ? Product::published()->where('slug', $data['product_slug'])->first()
            : null;

        $rfq = $rfqs->create([
            'company_name' => $data['company_name'] ?? null,
            'contact_name' => $data['contact_name'],
            'contact_email' => $data['contact_email'],
            'contact_phone' => $data['contact_phone'] ?? null,
            'notes' => $data['message'] ?? null,
            'items' => [[
                'product_id' => $product?->id,
                'sku' => $product?->sku,
                'name' => $data['item_name'],
                'quantity' => (float) $data['quantity'],
                'target_price' => isset($data['target_price']) ? (float) $data['target_price'] : null,
                'notes' => ! empty($data['mpn']) ? 'MPN: '.$data['mpn'] : null,
            ]],
        ]);

        $rfq->update(['meta' => [
            'country' => $data['country'] ?? null,
            'required_date' => $data['required_date'] ?? null,
            'source_product_page' => $product ? '/products/'.$product->slug : null,
            'channel' => 'web_product_page',
        ]]);

        DB::table('rfq_status_histories')->insert([
            'rfq_request_id' => $rfq->id,
            'previous_status' => null,
            'status' => 'open',
            'notes' => 'Submitted via public RFQ form',
            'changed_by_user_id' => null,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        // Email notification placeholder: wire to the marketing mailer once the
        // sales notification template exists. Intentionally log-only for now.
        Log::info('RFQ submitted via web form', ['rfq_number' => $rfq->rfq_number]);

        return redirect()->route('rfq.create')
            ->with('status', "Your request {$rfq->rfq_number} has been received. Our sales team will reply with a quotation.");
    }
}
