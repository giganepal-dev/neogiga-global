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
                'board_types' => ['single_sided', 'double_sided', 'multilayer', 'rigid_flex', 'flex', 'aluminum', 'ceramic'],
                'file_types' => array_keys(config('pcb.allowed_extensions', [])),
                'quote_mode' => 'manual_engineering_review',
                'automatic_pricing_enabled' => false,
                'dfm_mode' => 'engineering_review',
                'private_storage' => true,
                'max_upload_mb' => (int) config('pcb.max_file_size_mb', 100),
                'note' => 'Capability, price, lead time and manufacturability are confirmed after engineering review.',
            ],
        ]);
    }
}
