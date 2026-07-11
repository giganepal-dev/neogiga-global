<?php

namespace App\Http\Controllers\Pcb;

use App\Http\Controllers\Controller;
use App\Models\Pcb\PcbProject;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;

class PcbProjectController extends Controller
{
    /**
     * Display a listing of user's PCB projects.
     */
    public function index(Request $request): JsonResponse
    {
        $query = PcbProject::where('user_id', Auth::id())
            ->orWhereHas('members', function ($q) {
                $q->where('user_id', Auth::id());
            });

        if ($request->has('status')) {
            $query->where('status', $request->status);
        }

        if ($request->has('organization_id') && Auth::user()->organization_id) {
            $query->where('organization_id', Auth::user()->organization_id);
        }

        $projects = $query->with(['assignedEngineer', 'preferredManufacturer'])
            ->latest()
            ->paginate($request->get('per_page', 15));

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
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'application_type' => 'nullable|string|max:100',
            'confidentiality' => 'nullable|in:public,internal,confidential,nda_required',
            'project_type' => 'nullable|in:prototype,production',
            'target_quantity' => 'nullable|integer|min:1',
            'target_budget' => 'nullable|numeric|min:0',
            'currency' => 'nullable|string|size:3',
            'required_date' => 'nullable|date|after:today',
            'destination_country' => 'nullable|string|max:100',
            'shipping_postal_code' => 'nullable|string|max:20',
            'preferred_region' => 'nullable|string|max:100',
            'preferred_manufacturer_id' => 'nullable|exists:manufacturers,id',
            'preferred_warehouse_id' => 'nullable|exists:warehouses,id',
        ]);

        $project = PcbProject::create(array_merge($validated, [
            'user_id' => Auth::id(),
            'organization_id' => Auth::user()->organization_id,
            'marketplace' => session('marketplace', 'global'),
            'status' => 'draft',
        ]));

        // Create initial project member (owner)
        $project->members()->create([
            'user_id' => Auth::id(),
            'role' => 'owner',
        ]);

        // Log activity
        $project->activityLogs()->create([
            'user_id' => Auth::id(),
            'action' => 'project_created',
            'description' => 'PCB project created',
            'ip_address' => $request->ip(),
            'user_agent' => $request->userAgent(),
        ]);

        return response()->json([
            'success' => true,
            'data' => $project->load(['assignedEngineer', 'preferredManufacturer']),
            'message' => 'PCB project created successfully',
        ], 201);
    }

    /**
     * Display the specified PCB project.
     */
    public function show(PcbProject $project): JsonResponse
    {
        // Authorization check
        if (!$project->canBeAccessedBy(Auth::user())) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized access to this project',
            ], 403);
        }

        $project->load([
            'members.user',
            'versions.createdBy',
            'files.user',
            'gerberAnalysisRuns',
            'quoteConfigurations',
            'cplImports',
            'componentMatches.matchedProduct',
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
        if (!$project->canBeAccessedBy(Auth::user())) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized access to this project',
            ], 403);
        }

        // Only owner, admin, or engineer can update
        $member = $project->members()->where('user_id', Auth::id())->first();
        if (!$member || !in_array($member->role, ['owner', 'admin', 'engineer'])) {
            return response()->json([
                'success' => false,
                'message' => 'Insufficient permissions to update this project',
            ], 403);
        }

        $validated = $request->validate([
            'name' => 'sometimes|required|string|max:255',
            'description' => 'nullable|string',
            'application_type' => 'nullable|string|max:100',
            'confidentiality' => 'nullable|in:public,internal,confidential,nda_required',
            'project_type' => 'nullable|in:prototype,production',
            'target_quantity' => 'nullable|integer|min:1',
            'target_budget' => 'nullable|numeric|min:0',
            'currency' => 'nullable|string|size:3',
            'required_date' => 'nullable|date|after:today',
            'destination_country' => 'nullable|string|max:100',
            'shipping_postal_code' => 'nullable|string|max:20',
            'preferred_region' => 'nullable|string|max:100',
            'preferred_manufacturer_id' => 'nullable|exists:manufacturers,id',
            'preferred_warehouse_id' => 'nullable|exists:warehouses,id',
            'status' => 'nullable|in:draft,requirements_pending,design_requested,design_in_progress,design_review,design_approved,files_ready,quote_pending,quoted,awaiting_approval,ordered,manufacturing,inspection,shipped,completed,on_hold,cancelled',
        ]);

        $project->update($validated);

        // Log activity
        $project->activityLogs()->create([
            'user_id' => Auth::id(),
            'action' => 'project_updated',
            'description' => 'PCB project updated',
            'metadata' => ['fields' => array_keys($validated)],
            'ip_address' => $request->ip(),
            'user_agent' => $request->userAgent(),
        ]);

        return response()->json([
            'success' => true,
            'data' => $project->fresh(['assignedEngineer', 'preferredManufacturer']),
            'message' => 'PCB project updated successfully',
        ]);
    }

    /**
     * Remove the specified PCB project.
     */
    public function destroy(PcbProject $project): JsonResponse
    {
        if (!$project->canBeAccessedBy(Auth::user())) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized access to this project',
            ], 403);
        }

        // Only owner can delete
        if ($project->user_id !== Auth::id()) {
            return response()->json([
                'success' => false,
                'message' => 'Only project owner can delete the project',
            ], 403);
        }

        // Prevent deletion of active projects
        if (!in_array($project->status, ['draft', 'cancelled'])) {
            return response()->json([
                'success' => false,
                'message' => 'Cannot delete active projects. Please cancel the project first.',
            ], 400);
        }

        $projectName = $project->name;
        $project->delete();

        return response()->json([
            'success' => true,
            'message' => "PCB project '{$projectName}' deleted successfully",
        ]);
    }

    /**
     * Get project activity log.
     */
    public function activity(PcbProject $project): JsonResponse
    {
        if (!$project->canBeAccessedBy(Auth::user())) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized access to this project',
            ], 403);
        }

        $activity = $project->activityLogs()
            ->with('user')
            ->latest()
            ->paginate(50);

        return response()->json([
            'success' => true,
            'data' => $activity,
        ]);
    }
}
