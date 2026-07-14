<?php

namespace App\Http\Controllers\Api\Admin\Marketing;

use App\Http\Controllers\Concerns\ApiResponses;
use App\Http\Controllers\Controller;
use App\Services\Marketing\CampaignAudienceBuilder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class CampaignAdminController extends Controller
{
    use ApiResponses;

    public function audiencePreview(Request $request, CampaignAudienceBuilder $audience): JsonResponse
    {
        $data = $request->validate(['filters' => 'array', 'limit' => 'integer|min:1|max:100']);

        return $this->success(['summary' => $audience->summary($data['filters'] ?? []), 'recipients' => $audience->preview($data['filters'] ?? [], $data['limit'] ?? 25)]);
    }

    public function audienceCount(Request $request, CampaignAudienceBuilder $audience): JsonResponse
    {
        $data = $request->validate(['filters' => 'array']);

        return $this->success($audience->summary($data['filters'] ?? []));
    }

    public function createMultiChannel(Request $request): JsonResponse
    {
        $data = $request->validate(['name' => 'required|string|max:190', 'channels' => 'required|array', 'channels.*' => 'in:email,newsletter,whatsapp', 'targeting_rules' => 'array']);

        return $this->success(['message' => 'Multi-channel campaign draft validated. Create each channel campaign independently so consent and delivery histories remain separate.', 'channels' => $data['channels']], 201);
    }
}
