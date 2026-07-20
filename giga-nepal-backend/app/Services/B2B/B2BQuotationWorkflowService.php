<?php

namespace App\Services\B2B;

use App\Models\B2B\B2BAccount;
use App\Models\B2B\B2BQuotation;
use App\Models\B2B\B2BQuoteRequest;
use App\Models\Order;
use App\Models\Payment;
use App\Models\User;
use App\Services\Marketplace\UserMarketplaceScopeService;
use App\Services\Payments\PaymentMethodPolicyService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class B2BQuotationWorkflowService
{
    public function __construct(
        private readonly B2BQuotationService $quotations,
        private readonly InstitutionalDiscountService $discounts,
        private readonly B2BCommunicationService $communications,
        private readonly PaymentMethodPolicyService $paymentMethods,
        private readonly UserMarketplaceScopeService $marketplaceScope,
    ) {}

    /**
     * Build a quotation from an RFQ with institutional discounts applied.
     *
     * @param  array<string, mixed>  $overrides
     */
    public function createFromRfq(B2BQuoteRequest $rfq, B2BAccount $account, array $overrides, ?int $adminUserId = null): B2BQuotation
    {
        $rfq->loadMissing('items');

        $items = [];
        $discountPercent = $this->discounts->percentForAccount($account);

        foreach ($rfq->items as $line) {
            $basePrice = (float) ($line->target_price ?? 0);
            if ($basePrice <= 0) {
                $basePrice = (float) ($overrides['items'][$line->id]['unit_price'] ?? 0);
            }

            $priced = $this->discounts->applyDiscount($basePrice, $account);

            $items[] = [
                'product_id' => $line->product_id,
                'sku' => $line->sku,
                'name' => $line->name,
                'quantity' => $line->quantity,
                'unit_price' => $priced['unit_price'],
                'tax_amount' => 0,
            ];
        }

        if ($items === [] && ! empty($overrides['items'])) {
            foreach ($overrides['items'] as $item) {
                $priced = $this->discounts->applyDiscount((float) $item['unit_price'], $account);
                $items[] = array_merge($item, ['unit_price' => $priced['unit_price']]);
            }
        }

        $quote = $this->quotations->create([
            'b2b_account_id' => $account->id,
            'b2b_quote_request_id' => $rfq->id,
            'currency_code' => $rfq->currency_code ?? 'USD',
            'shipping_total' => $overrides['shipping_total'] ?? 0,
            'valid_until' => $overrides['valid_until'] ?? now()->addDays(30)->toDateString(),
            'items' => $items,
            'metadata' => [
                'institutional_discount_percent' => $discountPercent,
                'account_type' => $account->type,
            ],
        ], $adminUserId);

        $rfq->forceFill(['status' => 'quoted'])->save();

        return $this->send($quote->fresh(['items']), $account);
    }

    public function send(B2BQuotation $quotation, B2BAccount $account): B2BQuotation
    {
        $quotation->forceFill([
            'status' => 'sent',
            'sent_at' => now(),
            'payment_status' => 'locked',
        ])->save();

        $this->communications->quotationReady($quotation, $account);

        return $quotation->fresh();
    }

    public function accept(B2BQuotation $quotation, B2BAccount $account): B2BQuotation
    {
        if ($quotation->valid_until->isPast()) {
            throw ValidationException::withMessages(['quotation' => 'Quotation has expired.']);
        }

        if (! in_array($quotation->status, ['sent', 'draft'], true)) {
            throw ValidationException::withMessages(['quotation' => 'Quotation cannot be accepted in its current state.']);
        }

        $quotation->forceFill([
            'status' => 'accepted',
            'accepted_at' => now(),
            'payment_status' => 'unlocked',
        ])->save();

        $this->communications->quotationAccepted($quotation, $account);

        return $quotation->fresh();
    }

    public function pay(B2BQuotation $quotation, B2BAccount $account, User $user, string $paymentMethod): Order
    {
        if ($quotation->status !== 'accepted' || $quotation->payment_status !== 'unlocked') {
            throw ValidationException::withMessages(['payment' => 'Payment is not available until the quotation is accepted.']);
        }

        if ($quotation->order_id) {
            return Order::findOrFail($quotation->order_id);
        }

        $marketplaceId = $account->marketplace_id;
        $this->marketplaceScope->assertCanPurchase($user, $marketplaceId);
        $this->paymentMethods->assertAllowed($paymentMethod, $marketplaceId, $quotation->currency_code);

        return DB::transaction(function () use ($quotation, $account, $user, $paymentMethod, $marketplaceId) {
            $quotation->loadMissing('items');

            $order = Order::create([
                'order_number' => 'NG-B2B-'.now()->format('YmdHis').'-'.Str::upper(Str::random(5)),
                'user_id' => $user->id,
                'b2b_account_id' => $account->id,
                'marketplace_id' => $marketplaceId,
                'status' => 'pending',
                'currency_code' => $quotation->currency_code,
                'subtotal' => $quotation->subtotal,
                'tax_total' => $quotation->tax_total,
                'discount_total' => data_get($quotation->metadata, 'discount_total', 0),
                'shipping_total' => $quotation->shipping_total,
                'grand_total' => $quotation->grand_total,
                'amount_paid' => 0,
                'amount_due' => $quotation->grand_total,
                'payment_method' => $paymentMethod,
                'payment_status' => 'pending',
                'metadata' => [
                    'source' => 'b2b_quotation',
                    'b2b_quotation_id' => $quotation->id,
                    'quotation_number' => $quotation->quotation_number,
                ],
            ]);

            foreach ($quotation->items as $item) {
                $order->items()->create([
                    'product_id' => $item->product_id,
                    'product_name' => $item->name,
                    'product_sku' => $item->sku ?? 'B2B',
                    'quantity' => $item->quantity,
                    'unit_price' => $item->unit_price,
                    'tax_amount' => $item->tax_amount,
                    'discount_amount' => 0,
                    'total_price' => $item->line_total,
                ]);
            }

            Payment::create([
                'payment_number' => 'PAY-B2B-'.now()->format('YmdHis').'-'.Str::upper(Str::random(4)),
                'order_id' => $order->id,
                'marketplace_id' => $marketplaceId,
                'payment_method' => $paymentMethod,
                'payment_gateway' => 'manual',
                'amount' => $order->grand_total,
                'currency_code' => $order->currency_code,
                'status' => 'pending',
                'payment_details' => ['b2b_quotation_id' => $quotation->id],
            ]);

            $quotation->forceFill([
                'payment_status' => 'paid',
                'order_id' => $order->id,
            ])->save();

            return $order;
        });
    }
}
