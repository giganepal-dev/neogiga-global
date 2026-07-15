<?php

namespace App\Http\Controllers\Pcb;

use App\Http\Controllers\Controller;
use App\Models\Pcb\PcbProject;
use App\Models\Pcb\PcbQuoteConfiguration;
use App\Services\Pcb\PcbOrderService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class PcbQuoteController extends Controller
{
    public function show(Request $request, PcbProject $project, PcbQuoteConfiguration $quote): JsonResponse
    {
        abort_unless($project->canBeAccessedBy($request->user()), 403);
        abort_unless($quote->project_id === $project->id, 404);

        return response()->json(['success' => true, 'data' => $quote->load(['lineItems', 'order'])]);
    }

    public function store(Request $request, PcbProject $project): JsonResponse
    {
        abort_unless($project->canBeEditedBy($request->user()), 403);
        abort_unless($project->files()->where('file_type', 'gerber')->exists(), 422, 'Upload a Gerber ZIP before requesting a quote.');

        $data = $request->validate([
            'board_type' => ['required', 'in:single_sided,double_sided,multilayer,rigid_flex,flex,aluminum,ceramic'],
            'quantity' => ['required', 'integer', 'min:1', 'max:1000000'],
            'length_mm' => ['required', 'numeric', 'min:1', 'max:2000'],
            'width_mm' => ['required', 'numeric', 'min:1', 'max:2000'],
            'thickness_mm' => ['nullable', 'numeric', 'min:0.2', 'max:10'],
            'layer_count' => ['required', 'integer', 'min:1', 'max:64'],
            'substrate_material' => ['nullable', 'string', 'max:80'],
            'outer_copper_oz' => ['nullable', 'string', 'max:20'],
            'solder_mask_color' => ['nullable', 'string', 'max:40'],
            'silkscreen_color' => ['nullable', 'string', 'max:40'],
            'surface_finish' => ['nullable', 'in:HASL,HASL_Lead_Free,ENIG,OSP,Immersion_Silver,Immersion_Tin,Gold_Fingers'],
            'via_covering' => ['nullable', 'in:tented,plugged,filled,open'],
            'panelization_type' => ['nullable', 'in:none,v_score,routing,tab_route'],
            'production_speed' => ['nullable', 'in:standard,fast,express'],
            'aoi_testing' => ['nullable', 'boolean'],
            'electrical_test' => ['nullable', 'boolean'],
            'impedance_control' => ['nullable', 'boolean'],
            'blind_buried_vias' => ['nullable', 'boolean'],
            'hdi' => ['nullable', 'boolean'],
            'edge_plating' => ['nullable', 'boolean'],
            'castellated_holes' => ['nullable', 'boolean'],
        ]);

        $defaults = [
            'created_by_id' => $request->user()->id,
            'organization_id' => $project->organization_id,
            'currency' => $project->currency,
            'status' => 'submitted',
            'submitted_at' => now(),
            'requires_engineering_quote' => true,
            'thickness_mm' => 1.6,
            'substrate_material' => 'FR-4',
            'outer_copper_oz' => '1',
            'solder_mask_color' => 'green',
            'silkscreen_color' => 'white',
            'surface_finish' => 'HASL_Lead_Free',
            'via_covering' => 'tented',
            'panelization_type' => 'none',
            'production_speed' => 'standard',
        ];
        $quote = $project->quoteConfigurations()->whereIn('status', ['draft', 'rejected'])->latest()->first();
        $payload = array_merge($defaults, $data);
        if ($quote) {
            $quote->update($payload);
        } else {
            $quote = $project->quoteConfigurations()->create($payload);
        }
        $project->update(['status' => 'quote_pending']);

        return response()->json(['success' => true, 'data' => $quote->fresh(), 'message' => 'Quote request submitted.'], 201);
    }

    public function approve(Request $request, PcbProject $project, PcbQuoteConfiguration $quote, PcbOrderService $orders): JsonResponse
    {
        abort_unless($quote->project_id === $project->id, 404);
        $data = $request->validate(['customer_notes' => ['nullable', 'string', 'max:2000']]);
        $order = $orders->approve($project, $quote, $request->user(), $data['customer_notes'] ?? null);

        return response()->json(['success' => true, 'data' => ['order_id' => $order->id, 'order_number' => $order->order_number]]);
    }
}
