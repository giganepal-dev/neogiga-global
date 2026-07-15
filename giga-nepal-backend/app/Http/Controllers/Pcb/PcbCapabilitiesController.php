<?php

namespace App\Http\Controllers\Pcb;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;

class PcbCapabilitiesController extends Controller
{
    public function __invoke(): JsonResponse
    {
        return response()->json([
            'success' => true,
            'data' => [
                'board_types' => config('pcb-capabilities.board_types'),
                'materials' => config('pcb-capabilities.materials'),
                'dimensions' => config('pcb-capabilities.dimensions'),
                'copper' => config('pcb-capabilities.copper'),
                'surface_finishes' => config('pcb-capabilities.surface_finishes'),
                'solder_mask' => config('pcb-capabilities.solder_mask'),
                'silkscreen' => config('pcb-capabilities.silkscreen'),
                'drilling' => config('pcb-capabilities.drilling'),
                'advanced' => config('pcb-capabilities.advanced'),
                'testing' => config('pcb-capabilities.testing'),
                'quality' => config('pcb-capabilities.quality'),
                'lead_times' => config('pcb-capabilities.lead_times'),
                'file_types' => array_keys(config('pcb.allowed_extensions', [])),
                'max_upload_mb' => (int) config('pcb.max_file_size_mb', 100),
                'pricing' => [
                    'mode' => 'instant_estimate_with_engineering_review',
                    'note' => 'Pricing is estimated before engineering review of Gerber files.',
                ],
            ],
        ]);
    }
}
