<?php

namespace App\Services\Pcb;

use App\Models\Order;
use App\Models\Pcb\PcbProject;
use App\Models\Pcb\PcbQuoteConfiguration;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class PcbOrderService
{
    public function approve(PcbProject $project, PcbQuoteConfiguration $quote, User $user, ?string $notes = null): Order
    {
        return DB::transaction(function () use ($project, $quote, $user, $notes) {
            $locked = PcbQuoteConfiguration::query()->lockForUpdate()->findOrFail($quote->id);

            if ($locked->project_id !== $project->id || ! $project->canBeAccessedBy($user)) {
                abort(403);
            }

            if ($locked->order_id) {
                return Order::findOrFail($locked->order_id);
            }

            if ($locked->status !== 'quoted') {
                throw ValidationException::withMessages(['quote' => 'Only a quoted PCB configuration can be approved.']);
            }

            if ($locked->quote_valid_until && $locked->quote_valid_until->isPast()) {
                throw ValidationException::withMessages(['quote' => 'This quote has expired. Request a refreshed quote.']);
            }

            $total = round((float) $locked->setup_charge
                + (float) $locked->engineering_charge
                + (float) $locked->total_fabrication_price
                + (float) $locked->lineItems()->sum('total_price'), 2);

            if ($total <= 0) {
                throw ValidationException::withMessages(['quote' => 'The quote does not contain an approved commercial total.']);
            }

            $marketplaceId = DB::table('marketplaces')
                ->whereRaw('upper(code) = ?', [strtoupper((string) $project->marketplace)])
                ->value('id');

            $order = Order::create([
                'order_number' => 'NG-PCB-'.now()->format('YmdHis').'-'.Str::upper(Str::random(5)),
                'user_id' => $user->id,
                'marketplace_id' => $marketplaceId,
                'status' => 'pending',
                'currency_code' => $locked->currency,
                'subtotal' => $total,
                'grand_total' => $total,
                'amount_due' => $total,
                'payment_status' => 'pending',
                'customer_notes' => $notes,
                'metadata' => [
                    'source' => 'pcb_portal',
                    'pcb_project_id' => $project->id,
                    'pcb_project_code' => $project->code,
                    'pcb_quote_id' => $locked->id,
                    'commercial_snapshot_version' => 1,
                ],
            ]);

            $order->items()->create([
                'product_name' => 'PCB fabrication - '.$project->name,
                'product_sku' => $project->code,
                'quantity' => 1,
                'unit_price' => $total,
                'total_price' => $total,
                'metadata' => [
                    'pcb_quote_snapshot' => $locked->only([
                        'board_type', 'quantity', 'length_mm', 'width_mm', 'thickness_mm',
                        'layer_count', 'substrate_material', 'outer_copper_oz',
                        'solder_mask_color', 'surface_finish', 'production_speed', 'lead_time_days',
                    ]),
                ],
            ]);

            $locked->update([
                'status' => 'approved',
                'order_id' => $order->id,
                'customer_approved_at' => now(),
                'customer_rejected_at' => null,
                'customer_notes' => $notes,
            ]);
            $project->update(['status' => 'ordered']);
            $project->activityLogs()->create([
                'user_id' => $user->id,
                'action' => 'quote_approved',
                'description' => 'PCB quote approved and converted to order '.$order->order_number,
                'metadata' => ['quote_id' => $locked->id, 'order_id' => $order->id],
                'ip_address' => request()->ip(),
                'user_agent' => request()->userAgent(),
            ]);

            return $order;
        });
    }
}
