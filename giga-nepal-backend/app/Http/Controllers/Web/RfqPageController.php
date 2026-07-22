<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\Marketplace\Product;
use App\Services\Account\CustomerIdentityService;
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
    public function __construct(private readonly CustomerIdentityService $identity) {}

    public function create(Request $request): View
    {
        $product = null;
        if ($slug = (string) $request->query('product', '')) {
            $product = Product::with('brand')->published()->where('slug', $slug)->first();
        }

        return view('frontend.rfq.create', [
            'product' => $product,
            'customer' => $this->identity->defaults($request->user()),
        ]);
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
            // Line items arrive as parallel arrays (item_name[], quantity[],
            // target_price[]) so the form can add multiple parts. Entries are
            // lenient here and filtered below, so a stray empty cloned row does
            // not fail the whole submission.
            'item_name' => ['required', 'array', 'min:1'],
            'item_name.*' => ['nullable', 'string', 'max:190'],
            'mpn' => ['nullable', 'string', 'max:120'],
            'quantity' => ['nullable', 'array'],
            'quantity.*' => ['nullable', 'numeric', 'min:1'],
            'target_price' => ['nullable', 'array'],
            'target_price.*' => ['nullable', 'numeric', 'min:0'],
            'required_date' => ['nullable', 'date', 'after_or_equal:today'],
            'message' => ['nullable', 'string', 'max:2000'],
        ]);

        $product = ! empty($data['product_slug'])
            ? Product::published()->where('slug', $data['product_slug'])->first()
            : null;

        $names = array_values($data['item_name']);
        $quantities = array_values($data['quantity'] ?? []);
        $targets = array_values($data['target_price'] ?? []);

        $items = [];
        foreach ($names as $i => $name) {
            $name = trim((string) $name);
            if ($name === '') {
                continue; // skip blank rows the user added but never filled in
            }

            $isFirst = $items === [];
            $target = $targets[$i] ?? null;
            $items[] = [
                // Only the first line can map to the source product page.
                'product_id' => $isFirst ? $product?->id : null,
                'sku' => $isFirst ? $product?->sku : null,
                'name' => $name,
                'quantity' => (float) ($quantities[$i] ?? 1),
                'target_price' => ($target === null || $target === '') ? null : (float) $target,
                'notes' => $isFirst && ! empty($data['mpn']) ? 'MPN: '.$data['mpn'] : null,
            ];
        }

        if ($items === []) {
            return back()->withInput()->withErrors(['item_name' => 'Add at least one part to request a quote.']);
        }

        $rfq = $rfqs->create([
            'user_id' => $request->user()?->id,
            'company_name' => $data['company_name'] ?? null,
            'contact_name' => $data['contact_name'],
            'contact_email' => $data['contact_email'],
            'contact_phone' => $data['contact_phone'] ?? null,
            'notes' => $data['message'] ?? null,
            'items' => $items,
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
            'changed_by_user_id' => $request->user()?->id,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        // Email notification placeholder: wire to the marketing mailer once the
        // sales notification template exists. Intentionally log-only for now.
        Log::info('RFQ submitted via web form', ['rfq_number' => $rfq->rfq_number]);

        return redirect()->route('rfq.create')
            ->with('rfq_submitted', [
                'reference' => $rfq->rfq_number,
                'items_count' => count($items),
                'email' => $data['contact_email'] ?? null,
            ]);
    }
}
