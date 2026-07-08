<?php

namespace App\Http\Controllers\Api\CommerceAi;

use App\Http\Controllers\Concerns\ApiResponses;
use App\Http\Controllers\Controller;
use App\Http\Requests\CommerceAi\CommerceAiPromptRequest;
use App\Services\CommerceAi\CommerceAiService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class CommerceAiDemoController extends Controller
{
    use ApiResponses;

    public function examples(CommerceAiService $ai): JsonResponse
    {
        return $this->success(['examples' => $ai->examples(), 'engine' => 'local_rule_engine']);
    }

    public function session(Request $request, CommerceAiService $ai): JsonResponse
    {
        return $this->success($ai->createSession($request->user()?->id), 201);
    }

    public function message(CommerceAiPromptRequest $request, CommerceAiService $ai): JsonResponse
    {
        $data = $request->validated();

        return $this->success($ai->respond($data['prompt'], $data['session_key'] ?? null, $request->user()?->id));
    }

    public function buildBom(CommerceAiPromptRequest $request, CommerceAiService $ai): JsonResponse
    {
        $data = $request->validated();

        return $this->success($ai->buildBom($data['prompt'], $data['session_key'] ?? null, $request->user()?->id), 201);
    }
}
