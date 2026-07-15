<?php

namespace App\Http\Controllers\Pcb;

use App\Http\Controllers\Controller;
use App\Services\Pcb\PcbPricingService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class PcbPublicQuoteController extends Controller
{
    public function calculate(Request $request, PcbPricingService $pricing): JsonResponse
    {
        $data = $request->validate([
            'layers' => ['required', 'integer', 'min:1', 'max:64'],
            'width_mm' => ['required', 'numeric', 'min:5', 'max:600'],
            'height_mm' => ['required', 'numeric', 'min:5', 'max:600'],
            'quantity' => ['required', 'integer', 'min:5', 'max:100000'],
            'board_material' => ['nullable', 'string', 'max:40'],
            'board_type' => ['nullable', 'string', 'max:40'],
            'surface_finish' => ['nullable', 'string', 'max:40'],
            'solder_mask_color' => ['nullable', 'string', 'max:20'],
            'outer_copper_oz' => ['nullable', 'string', 'max:10'],
            'production_speed' => ['nullable', 'in:standard,fast,express'],
            'impedance_control' => ['nullable', 'boolean'],
            'electrical_test' => ['nullable', 'boolean'],
        ]);

        $result = $pricing->calculate($data);

        return response()->json([
            'success' => true,
            'data' => $result,
        ]);
    }

    public function capabilities(): View
    {
        return view('pcb.capabilities');
    }

    public function designRules(): View
    {
        return view('pcb.design-rules');
    }
}
