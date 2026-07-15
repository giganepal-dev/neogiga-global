<?php

namespace App\Http\Controllers\Api\Bom;

use App\Http\Controllers\Concerns\ApiResponses;
use App\Http\Controllers\Controller;
use App\Http\Requests\Bom\BomBuildCustomRequest;
use App\Services\Bom\BomAvailabilityService;
use App\Services\Bom\BomBuilderService;
use App\Services\Bom\BomCartService;
use App\Services\Bom\BomPricingService;
use App\Services\Bom\BomProjectService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class BomProjectController extends Controller
{
    use ApiResponses;

    public function index(BomProjectService $projects): JsonResponse
    {
        if (! Schema::hasTable('bom_projects')) {
            return $this->error('BOM project commerce migration is pending.', 503);
        }

        return $this->success($projects->publicQuery()->paginate(24));
    }

    public function show(string $slug, BomProjectService $projects): JsonResponse
    {
        if (! Schema::hasTable('bom_projects')) {
            return $this->error('BOM project commerce migration is pending.', 503);
        }

        return $this->success($projects->publicBySlug($slug));
    }

    public function items(string $slug, BomProjectService $projects, BomAvailabilityService $availability): JsonResponse
    {
        if (! Schema::hasTable('bom_projects')) {
            return $this->error('BOM project commerce migration is pending.', 503);
        }

        $project = $projects->publicBySlug($slug);

        return $this->success([
            'project' => $project->only(['id', 'title', 'slug']),
            'items' => $project->items()->publiclyAvailable()->orderBy('priority')->get(),
            'availability' => $availability->forProject($project),
        ]);
    }

    public function price(Request $request, string $slug, BomProjectService $projects, BomPricingService $pricing): JsonResponse
    {
        if (! Schema::hasTable('bom_projects')) {
            return $this->error('BOM project commerce migration is pending.', 503);
        }

        $data = $request->validate(['quantities' => ['nullable', 'array']]);

        return $this->success($pricing->estimate($projects->publicBySlug($slug), $data));
    }

    public function addToCart(Request $request, string $slug, BomProjectService $projects, BomPricingService $pricing, BomCartService $cart): JsonResponse
    {
        if (! Schema::hasTable('bom_projects')) {
            return $this->error('BOM project commerce migration is pending.', 503);
        }

        $project = $projects->publicBySlug($slug);
        $estimate = $pricing->estimate($project, $request->validate(['quantities' => ['nullable', 'array']]));

        return $this->success($cart->recordConversion($project, $request->user()?->id, $estimate), 202, ['estimate' => $estimate]);
    }

    public function buildCustom(BomBuildCustomRequest $request, BomBuilderService $builder): JsonResponse
    {
        return $this->success($builder->normalizeCustomBuild($request->validated()), 202);
    }

    public function storeUserBuild(Request $request): JsonResponse
    {
        if (! Schema::hasTable('bom_user_builds')) {
            return $this->error('BOM user build migration is pending.', 503);
        }

        $data = $request->validate([
            'bom_project_id' => ['nullable', 'integer', 'min:1'],
            'name' => ['required', 'string', 'max:180'],
            'status' => ['nullable', 'string', 'max:40'],
            'payload' => ['nullable', 'array'],
        ]);

        $id = DB::table('bom_user_builds')->insertGetId([
            'bom_project_id' => $data['bom_project_id'] ?? null,
            'user_id' => $request->user()?->id,
            'name' => $data['name'],
            'status' => $data['status'] ?? 'draft',
            'payload' => json_encode($data['payload'] ?? []),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        return $this->success(['id' => $id], 201);
    }

    public function showUserBuild(Request $request, int $build): JsonResponse
    {
        if (! Schema::hasTable('bom_user_builds')) {
            return $this->error('BOM user build migration is pending.', 503);
        }

        $record = DB::table('bom_user_builds')
            ->where('id', $build)
            ->where('user_id', $request->user()?->id)
            ->first();

        abort_if(! $record, 404);

        return $this->success($record);
    }
}
