<?php

namespace App\Http\Controllers\Api\LMS;

use App\Http\Controllers\Concerns\ApiResponses;
use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;

/**
 * LMS (Blueprint §27). The lms_* migrations are empty shells and the
 * models are stubs (audit DB-02) — there is no queryable schema yet.
 * Endpoints keep the API contract stable and return 501 until the
 * Phase-1 schema reconciliation lands the real LMS tables.
 */
class LmsController extends Controller
{
    use ApiResponses;

    public function courses(): JsonResponse
    {
        return $this->notImplemented('LMS courses');
    }

    public function projects(): JsonResponse
    {
        return $this->notImplemented('LMS projects');
    }

    public function showProject(string $slug): JsonResponse
    {
        return $this->notImplemented('LMS projects');
    }

    public function projectComponents(string $slug): JsonResponse
    {
        return $this->notImplemented('LMS project components');
    }

    public function projectCodeSamples(string $slug): JsonResponse
    {
        return $this->notImplemented('LMS code samples');
    }
}
