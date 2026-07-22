<?php

namespace App\Http\Controllers\Api\Onboarding;

use App\Http\Controllers\Concerns\ApiResponses;
use App\Http\Controllers\Controller;
use App\Services\Partner\PartnerCountryService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class PartnerCountryController extends Controller
{
    use ApiResponses;

    public function index(Request $request, PartnerCountryService $countries): JsonResponse
    {
        return $this->success($countries->options($request));
    }
}
