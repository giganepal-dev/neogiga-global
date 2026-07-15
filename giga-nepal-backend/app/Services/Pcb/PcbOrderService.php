<?php

namespace App\Services\Pcb;

use App\Models\Pcb\PcbOrder;
use App\Models\Pcb\PcbProject;
use App\Models\Pcb\PcbQuoteConfiguration;
use Illuminate\Support\Facades\DB;

class PcbOrderService
{
    public function approve(PcbProject $project, PcbQuoteConfiguration $quote, $user, ?string $customerNotes = null): PcbOrder
    {
        return DB::transaction(function () use ($project, $quote, $user, $customerNotes) {
            $total = ($quote->setup_charge ?? 0)
                + ($quote->engineering_charge ?? 0)
                + ($quote->total_fabrication_price ?? 0);

            $order = PcbOrder::create([
                'project_id' => $project->id,
                'quote_id' => $quote->id,
                'user_id' => $user->id,
                'currency' => $quote->currency ?? 'USD',
                'total_amount' => $total,
                'customer_notes' => $customerNotes,
                'milestones' => PcbOrder::defaultMilestones(),
                'estimated_ship_date' => now()->addDays((int) ($quote->lead_time_days ?? 7)),
            ]);

            $quote->update(['status' => 'approved']);
            $project->update(['status' => 'awaiting_approval']);

            $project->activityLogs()->create([
                'user_id' => $user->id,
                'action' => 'order_created',
                'description' => "Order {$order->order_number} created from approved quote",
                'metadata' => ['order_id' => $order->id, 'quote_id' => $quote->id],
                'ip_address' => request()->ip(),
                'user_agent' => request()->userAgent(),
            ]);

            return $order;
        });
    }
}
