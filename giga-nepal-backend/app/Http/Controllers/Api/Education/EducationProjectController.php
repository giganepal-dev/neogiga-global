<?php

namespace App\Http\Controllers\Api\Education;

use App\Http\Controllers\Controller;
use App\Services\Education\EducationProjectService;
use App\Services\Education\AiProjectBuilderService;
use App\Services\Product\RecommendationEngineService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class EducationProjectController extends Controller
{
    public function __construct(
        private EducationProjectService $projectService,
        private AiProjectBuilderService $aiBuilder,
        private RecommendationEngineService $recommendations,
    ) {}

    /** GET /api/v1/education/projects */
    public function index(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'q' => ['nullable', 'string', 'max:200'],
            'category' => ['nullable', 'string', 'max:100'],
            'skill_level' => ['nullable', 'string', 'in:beginner,intermediate,advanced,expert'],
            'controller' => ['nullable', 'string', 'max:100'],
            'featured' => ['nullable', 'boolean'],
            'limit' => ['nullable', 'integer', 'min:1', 'max:50'],
            'offset' => ['nullable', 'integer', 'min:0'],
        ]);

        $result = $this->projectService->search(
            $validated['q'] ?? '',
            array_filter([
                'category' => $validated['category'] ?? null,
                'skill_level' => $validated['skill_level'] ?? null,
                'controller' => $validated['controller'] ?? null,
                'featured' => $validated['featured'] ?? null,
            ]),
            $validated['limit'] ?? 20,
            $validated['offset'] ?? 0,
        );

        return response()->json(['success' => true, 'data' => $result]);
    }

    /** GET /api/v1/education/projects/featured */
    public function featured(): JsonResponse
    {
        return response()->json([
            'success' => true,
            'data' => $this->projectService->getFeatured(),
        ]);
    }

    /** GET /api/v1/education/projects/categories */
    public function categories(): JsonResponse
    {
        return response()->json([
            'success' => true,
            'data' => $this->projectService->getCategories(),
        ]);
    }

    /** GET /api/v1/education/projects/{slug} */
    public function show(string $slug): JsonResponse
    {
        $project = $this->projectService->getProject($slug);
        if (!$project) {
            return response()->json(['success' => false, 'message' => 'Project not found.'], 404);
        }
        $this->projectService->recordView($project->id);
        return response()->json(['success' => true, 'data' => $project]);
    }

    /** GET /api/v1/education/projects/{id}/bom */
    public function bom(Request $request, int $id): JsonResponse
    {
        $bomData = $this->projectService->getProjectBomWithLivePricing(
            $id,
            $request->input('marketplace_id'),
        );
        return response()->json(['success' => true, 'data' => $bomData]);
    }

    /** GET /api/v1/education/projects/{id}/code */
    public function code(int $id): JsonResponse
    {
        return response()->json([
            'success' => true,
            'data' => $this->projectService->getProjectCode($id),
        ]);
    }

    /** POST /api/v1/education/ai-build */
    public function aiBuild(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'prompt' => ['required', 'string', 'max:2000'],
        ]);

        $result = $this->aiBuilder->buildProjectResponse($validated['prompt']);
        return response()->json(['success' => true, 'data' => $result]);
    }

    /** POST /api/v1/education/ai-intent */
    public function aiIntent(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'prompt' => ['required', 'string', 'max:2000'],
        ]);

        $intent = $this->aiBuilder->detectIntent($validated['prompt']);
        return response()->json(['success' => true, 'data' => $intent]);
    }

    /** GET /api/v1/education/sensors */
    public function sensors(Request $request): JsonResponse
    {
        $query = \App\Models\Education\SensorKnowledge::query();
        if ($request->has('type')) {
            $query->where('sensor_type', $request->input('type'));
        }
        return response()->json([
            'success' => true,
            'data' => $query->get(),
        ]);
    }

    /** GET /api/v1/education/sensors/{type} */
    public function sensor(string $type): JsonResponse
    {
        $sensor = \App\Models\Education\SensorKnowledge::where('sensor_type', $type)->first();
        if (!$sensor) {
            return response()->json(['success' => false, 'message' => 'Sensor type not found.'], 404);
        }
        return response()->json(['success' => true, 'data' => $sensor]);
    }
}
