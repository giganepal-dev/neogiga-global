<?php

namespace App\Http\Controllers\Pcb;

use App\Http\Controllers\Controller;
use App\Models\Pcb\PcbProject;
use App\Models\Pcb\PcbProjectMember;
use App\Models\Pcb\PcbFile;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Auth;

class PcbProjectController extends Controller
{
    /**
     * Display a listing of the user's PCB projects.
     */
    public function index(Request $request): JsonResponse
    {
        $query = PcbProject::with(['organization', 'assignedEngineer'])
            ->where('user_id', Auth::id());

        // Add organization projects where user is a member
        if (Auth::user()->organization_id) {
            $orgProjects = PcbProject::with(['organization', 'assignedEngineer'])
                ->where('organization_id', Auth::user()->organization_id)
                ->whereHas('members', function ($q) {
                    $q->where('user_id', Auth::id());
                });
            
            $query = $query->union($orgProjects);
        }

        // Apply filters
        if ($request->has('status')) {
            $query->where('status', $request->status);
        }

        if ($request->has('marketplace')) {
            $query->where('marketplace', $request->marketplace);
        }

        $projects = $query->orderByDesc('updated_at')
            ->paginate($request->get('per_page', 20));

        return response()->json([
            'success' => true,
            'data' => $projects,
        ]);
    }

    /**
     * Store a newly created PCB project.
     */
    public function store(Request $request): JsonResponse
    {
        Gate::authorize('create', PcbProject::class);

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'application_type' => 'nullable|string|max:100',
            'confidentiality' => 'nullable|in:public,internal,confidential',
            'project_type' => 'nullable|in:prototype,production',
            'target_quantity' => 'nullable|integer|min:1',
            'target_budget' => 'nullable|numeric|min:0',
            'currency' => 'nullable|string|size:3',
            'required_date' => 'nullable|date|after:today',
            'destination_country' => 'nullable|string|size:2',
            'shipping_postal_code' => 'nullable|string|max:20',
            'preferred_region' => 'nullable|string|max:50',
        ]);

        $project = PcbProject::create([
            'user_id' => Auth::id(),
            'organization_id' => Auth::user()->organization_id,
            'marketplace' => $request->get('marketplace', 'global'),
            'name' => $validated['name'],
            'description' => $validated['description'] ?? null,
            'application_type' => $validated['application_type'] ?? null,
            'confidentiality' => $validated['confidentiality'] ?? 'internal',
            'project_type' => $validated['project_type'] ?? 'prototype',
            'target_quantity' => $validated['target_quantity'] ?? 1,
            'target_budget' => $validated['target_budget'] ?? null,
            'currency' => $validated['currency'] ?? 'USD',
            'required_date' => $validated['required_date'] ?? null,
            'destination_country' => $validated['destination_country'] ?? null,
            'shipping_postal_code' => $validated['shipping_postal_code'] ?? null,
            'preferred_region' => $validated['preferred_region'] ?? null,
        ]);

        // Create initial version
        $project->versions()->create([
            'version_number' => 1,
            'name' => 'Initial Version',
            'created_by_id' => Auth::id(),
            'status' => 'draft',
        ]);

        // Add owner as project member
        PcbProjectMember::create([
            'project_id' => $project->id,
            'user_id' => Auth::id(),
            'organization_id' => Auth::user()->organization_id,
            'role' => 'owner',
            'can_edit' => true,
            'can_upload_files' => true,
            'can_approve' => true,
            'can_invite' => true,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'PCB project created successfully',
            'data' => $project->fresh(['versions', 'members']),
        ], 201);
    }

    /**
     * Display the specified PCB project.
     */
    public function show(PcbProject $project): JsonResponse
    {
        Gate::authorize('view', $project);

        $project->load([
            'versions' => function ($query) {
                $query->orderByDesc('version_number');
            },
            'members.user',
            'files' => function ($query) {
                $query->orderByDesc('created_at');
            },
            'assignedEngineer',
            'organization',
        ]);

        return response()->json([
            'success' => true,
            'data' => $project,
        ]);
    }

    /**
     * Update the specified PCB project.
     */
    public function update(Request $request, PcbProject $project): JsonResponse
    {
        Gate::authorize('update', $project);

        $validated = $request->validate([
            'name' => 'sometimes|required|string|max:255',
            'description' => 'nullable|string',
            'application_type' => 'nullable|string|max:100',
            'confidentiality' => 'nullable|in:public,internal,confidential',
            'project_type' => 'nullable|in:prototype,production',
            'target_quantity' => 'nullable|integer|min:1',
            'target_budget' => 'nullable|numeric|min:0',
            'currency' => 'nullable|string|size:3',
            'required_date' => 'nullable|date',
            'destination_country' => 'nullable|string|size:2',
            'shipping_postal_code' => 'nullable|string|max:20',
            'preferred_region' => 'nullable|string|max:50',
            'preferred_manufacturer_id' => 'nullable|uuid|exists:manufacturers,id',
            'preferred_warehouse_id' => 'nullable|uuid|exists:warehouses,id',
            'assigned_engineer_id' => 'nullable|uuid|exists:users,id',
            'status' => 'nullable|in:draft,requirements_pending,design_requested,design_in_progress,design_review,design_approved,files_ready,quote_pending,quoted,awaiting_approval,ordered,manufacturing,inspection,shipped,completed,on_hold,cancelled',
        ]);

        $project->update($validated);

        return response()->json([
            'success' => true,
            'message' => 'Project updated successfully',
            'data' => $project->fresh(),
        ]);
    }

    /**
     * Remove the specified PCB project.
     */
    public function destroy(PcbProject $project): JsonResponse
    {
        Gate::authorize('delete', $project);

        $projectName = $project->name;
        $project->delete();

        return response()->json([
            'success' => true,
            'message' => "Project '{$projectName}' deleted successfully",
        ]);
    }

    /**
     * Get project activity log.
     */
    public function activity(PcbProject $project): JsonResponse
    {
        Gate::authorize('view', $project);

        // TODO: Implement activity logging system
        $activities = collect([]);

        return response()->json([
            'success' => true,
            'data' => $activities,
        ]);
    }
}
