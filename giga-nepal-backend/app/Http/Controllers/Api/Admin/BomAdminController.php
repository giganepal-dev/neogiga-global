<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Concerns\ApiResponses;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\Bom\AdminBomItemRequest;
use App\Http\Requests\Admin\Bom\AdminBomProjectRequest;
use App\Models\Bom\BomProject;
use App\Models\Bom\BomProjectItem;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

class BomAdminController extends Controller
{
    use ApiResponses;

    public function projects(): JsonResponse
    {
        if (! Schema::hasTable('bom_projects')) {
            return $this->error('BOM project commerce migration is pending.', 503);
        }

        return $this->success(BomProject::with('items')->latest()->paginate(25));
    }

    public function storeProject(AdminBomProjectRequest $request): JsonResponse
    {
        if (! Schema::hasTable('bom_projects')) {
            return $this->error('BOM project commerce migration is pending.', 503);
        }

        $data = $request->validated();
        $data['slug'] = $data['slug'] ?? Str::slug($data['title']);

        return $this->success(BomProject::create($data), 201);
    }

    public function updateProject(AdminBomProjectRequest $request, int $project): JsonResponse
    {
        if (! Schema::hasTable('bom_projects')) {
            return $this->error('BOM project commerce migration is pending.', 503);
        }

        $record = BomProject::findOrFail($project);
        $record->fill($request->validated())->save();

        return $this->success($record->fresh('items'));
    }

    public function storeItem(AdminBomItemRequest $request, int $project): JsonResponse
    {
        if (! Schema::hasTable('bom_project_items')) {
            return $this->error('BOM project item migration is pending.', 503);
        }

        BomProject::findOrFail($project);
        $item = BomProjectItem::create(['bom_project_id' => $project] + $request->validated());

        return $this->success($item, 201);
    }

    public function updateItem(AdminBomItemRequest $request, int $project, int $item): JsonResponse
    {
        if (! Schema::hasTable('bom_project_items')) {
            return $this->error('BOM project item migration is pending.', 503);
        }

        $record = BomProjectItem::where('bom_project_id', $project)->findOrFail($item);
        $record->fill($request->validated())->save();

        return $this->success($record->fresh());
    }

    public function deleteItem(int $project, int $item): JsonResponse
    {
        if (! Schema::hasTable('bom_project_items')) {
            return $this->error('BOM project item migration is pending.', 503);
        }

        BomProjectItem::where('bom_project_id', $project)->findOrFail($item)->delete();

        return $this->success(['deleted' => true]);
    }
}
