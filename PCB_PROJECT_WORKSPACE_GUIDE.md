# PCB Project Workspace Implementation Guide

## Overview

This guide details the implementation of the PCB project workspace - the central hub where customers, engineers, and suppliers collaborate on PCB projects from concept through manufacturing.

## Architecture

### Core Components

```
PCB Project Workspace
├── Project Overview Tab
├── Requirements Tab
├── Design Tab
├── Files Tab
├── Gerber Viewer Tab
├── BOM Tab
├── CPL Tab
├── Component Matching Tab
├── DFM Tab
├── Quotes Tab
├── Suppliers Tab
├── Messages Tab
├── Orders Tab
├── Production Tab
├── Quality Tab
└── History Tab
```

## Database Schema

### pcb_projects Table

```sql
CREATE TABLE pcb_projects (
    id UUID PRIMARY KEY DEFAULT gen_random_uuid(),
    user_id UUID NOT NULL REFERENCES users(id) ON DELETE CASCADE,
    organization_id UUID NOT NULL REFERENCES organizations(id) ON DELETE CASCADE,
    marketplace_id UUID REFERENCES marketplaces(id),
    
    -- Identification
    project_name VARCHAR(255) NOT NULL,
    project_code VARCHAR(50) UNIQUE NOT NULL,
    description TEXT,
    application_type VARCHAR(100), -- 'IoT', 'Automotive', 'Industrial', 'Consumer', 'Medical', 'Other'
    
    -- Confidentiality
    confidentiality_level VARCHAR(20) DEFAULT 'internal', -- 'public', 'internal', 'confidential', 'restricted'
    
    -- Project Type
    project_stage VARCHAR(30) DEFAULT 'prototype', -- 'prototype', 'production', 'maintenance'
    target_quantity INTEGER,
    target_budget DECIMAL(15,4),
    currency_code CHAR(3) DEFAULT 'USD',
    
    -- Timeline
    required_date DATE,
    destination_country_code CHAR(2),
    shipping_postal_code VARCHAR(20),
    
    -- Preferences
    preferred_region VARCHAR(50), -- 'Asia', 'North America', 'Europe', etc.
    preferred_manufacturer_id UUID REFERENCES manufacturers(id),
    preferred_warehouse_id UUID REFERENCES warehouses(id),
    
    -- Assignment
    assigned_engineer_id UUID REFERENCES users(id),
    
    -- Status Tracking
    status VARCHAR(30) DEFAULT 'draft',
    current_version INTEGER DEFAULT 1,
    
    -- Timestamps
    created_at TIMESTAMP(0) WITHOUT TIME ZONE DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP(0) WITHOUT TIME ZONE DEFAULT CURRENT_TIMESTAMP,
    deleted_at TIMESTAMP(0) WITHOUT TIME ZONE,
    
    -- Indexes
    CONSTRAINT chk_pcb_project_status CHECK (status IN (
        'draft', 'requirements_pending', 'design_requested', 'design_in_progress',
        'design_review', 'design_approved', 'files_ready', 'quote_pending',
        'quoted', 'awaiting_approval', 'ordered', 'manufacturing', 'inspection',
        'shipped', 'completed', 'on_hold', 'cancelled'
    ))
);

-- Indexes
CREATE INDEX idx_pcb_projects_user ON pcb_projects(user_id);
CREATE INDEX idx_pcb_projects_org ON pcb_projects(organization_id);
CREATE INDEX idx_pcb_projects_status ON pcb_projects(status);
CREATE INDEX idx_pcb_projects_marketplace ON pcb_projects(marketplace_id);
CREATE UNIQUE INDEX idx_pcb_projects_code ON pcb_projects(project_code);
```

### pcb_project_members Table

```sql
CREATE TABLE pcb_project_members (
    id UUID PRIMARY KEY DEFAULT gen_random_uuid(),
    project_id UUID NOT NULL REFERENCES pcb_projects(id) ON DELETE CASCADE,
    user_id UUID NOT NULL REFERENCES users(id) ON DELETE CASCADE,
    role VARCHAR(50) NOT NULL, -- 'owner', 'admin', 'editor', 'viewer', 'engineer', 'supplier'
    
    -- Access Control
    can_view_files BOOLEAN DEFAULT TRUE,
    can_download_files BOOLEAN DEFAULT FALSE,
    can_upload_files BOOLEAN DEFAULT FALSE,
    can_edit_requirements BOOLEAN DEFAULT FALSE,
    can_approve_quotes BOOLEAN DEFAULT FALSE,
    can_view_costs BOOLEAN DEFAULT FALSE,
    
    -- Supplier-specific
    supplier_id UUID REFERENCES suppliers(id),
    access_expires_at TIMESTAMP(0) WITHOUT TIME ZONE,
    nda_accepted BOOLEAN DEFAULT FALSE,
    nda_accepted_at TIMESTAMP(0) WITHOUT TIME ZONE,
    
    -- Timestamps
    created_at TIMESTAMP(0) WITHOUT TIME ZONE DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP(0) WITHOUT TIME ZONE DEFAULT CURRENT_TIMESTAMP,
    
    UNIQUE(project_id, user_id)
);

CREATE INDEX idx_project_members_project ON pcb_project_members(project_id);
CREATE INDEX idx_project_members_user ON pcb_project_members(user_id);
```

### pcb_project_versions Table

```sql
CREATE TABLE pcb_project_versions (
    id UUID PRIMARY KEY DEFAULT gen_random_uuid(),
    project_id UUID NOT NULL REFERENCES pcb_projects(id) ON DELETE CASCADE,
    version_number INTEGER NOT NULL,
    
    -- Version Details
    change_summary TEXT,
    changed_by UUID REFERENCES users(id),
    
    -- Snapshot References
    requirements_snapshot JSONB,
    files_snapshot JSONB,
    bom_snapshot JSONB,
    cpl_snapshot JSONB,
    configuration_snapshot JSONB,
    
    -- Timestamps
    created_at TIMESTAMP(0) WITHOUT TIME ZONE DEFAULT CURRENT_TIMESTAMP,
    
    UNIQUE(project_id, version_number)
);

CREATE INDEX idx_project_versions_project ON pcb_project_versions(project_id);
```

### pcb_project_activity_logs Table

```sql
CREATE TABLE pcb_project_activity_logs (
    id UUID PRIMARY KEY DEFAULT gen_random_uuid(),
    project_id UUID NOT NULL REFERENCES pcb_projects(id) ON DELETE CASCADE,
    user_id UUID REFERENCES users(id),
    
    -- Activity Details
    action VARCHAR(100) NOT NULL, -- 'created', 'updated', 'file_uploaded', 'quote_received', etc.
    entity_type VARCHAR(50), -- 'project', 'file', 'bom', 'quote', 'order'
    entity_id UUID,
    
    -- Context
    description TEXT,
    metadata JSONB,
    ip_address INET,
    user_agent TEXT,
    
    -- Timestamps
    created_at TIMESTAMP(0) WITHOUT TIME ZONE DEFAULT CURRENT_TIMESTAMP
);

CREATE INDEX idx_activity_logs_project ON pcb_project_activity_logs(project_id);
CREATE INDEX idx_activity_logs_user ON pcb_project_activity_logs(user_id);
CREATE INDEX idx_activity_logs_action ON pcb_project_activity_logs(action);
```

## Models

### PcbProject Model

```php
<?php

namespace App\Models\PCB;

use App\Models\User;
use App\Models\Organization;
use App\Models\Marketplace;
use App\Models\Manufacturer;
use App\Models\Warehouse;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

class PcbProject extends Model
{
    use SoftDeletes;

    protected $table = 'pcb_projects';
    
    protected $keyType = 'string';
    public $incrementing = false;

    protected $fillable = [
        'user_id',
        'organization_id',
        'marketplace_id',
        'project_name',
        'project_code',
        'description',
        'application_type',
        'confidentiality_level',
        'project_stage',
        'target_quantity',
        'target_budget',
        'currency_code',
        'required_date',
        'destination_country_code',
        'shipping_postal_code',
        'preferred_region',
        'preferred_manufacturer_id',
        'preferred_warehouse_id',
        'assigned_engineer_id',
        'status',
        'current_version',
    ];

    protected $casts = [
        'target_quantity' => 'integer',
        'target_budget' => 'decimal:4',
        'required_date' => 'date',
        'current_version' => 'integer',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    // Status constants
    const STATUS_DRAFT = 'draft';
    const STATUS_REQUIREMENTS_PENDING = 'requirements_pending';
    const STATUS_DESIGN_REQUESTED = 'design_requested';
    const STATUS_DESIGN_IN_PROGRESS = 'design_in_progress';
    const STATUS_DESIGN_REVIEW = 'design_review';
    const STATUS_DESIGN_APPROVED = 'design_approved';
    const STATUS_FILES_READY = 'files_ready';
    const STATUS_QUOTE_PENDING = 'quote_pending';
    const STATUS_QUOTED = 'quoted';
    const STATUS_AWAITING_APPROVAL = 'awaiting_approval';
    const STATUS_ORDERED = 'ordered';
    const STATUS_MANUFACTURING = 'manufacturing';
    const STATUS_INSPECTION = 'inspection';
    const STATUS_SHIPPED = 'shipped';
    const STATUS_COMPLETED = 'completed';
    const STATUS_ON_HOLD = 'on_hold';
    const STATUS_CANCELLED = 'cancelled';

    public static function boot()
    {
        parent::boot();

        static::creating(function ($project) {
            if (empty($project->id)) {
                $project->id = (string) Str::uuid();
            }
            
            if (empty($project->project_code)) {
                $project->project_code = 'PCB-' . strtoupper(Str::random(8));
            }
        });
    }

    public function owner(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function organization(): BelongsTo
    {
        return $this->belongsTo(Organization::class);
    }

    public function marketplace(): BelongsTo
    {
        return $this->belongsTo(Marketplace::class);
    }

    public function assignedEngineer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_engineer_id');
    }

    public function preferredManufacturer(): BelongsTo
    {
        return $this->belongsTo(Manufacturer::class, 'preferred_manufacturer_id');
    }

    public function preferredWarehouse(): BelongsTo
    {
        return $this->belongsTo(Warehouse::class, 'preferred_warehouse_id');
    }

    public function members(): HasMany
    {
        return $this->hasMany(PcbProjectMember::class, 'project_id');
    }

    public function versions(): HasMany
    {
        return $this->hasMany(PcbProjectVersion::class, 'project_id');
    }

    public function files(): HasMany
    {
        return $this->hasMany(PcbFile::class, 'project_id');
    }

    public function designRequests(): HasMany
    {
        return $this->hasMany(PcbDesignRequest::class, 'project_id');
    }

    public function quotes(): HasMany
    {
        return $this->hasMany(PcbQuote::class, 'project_id');
    }

    public function orders(): HasMany
    {
        return $this->hasMany(PcbOrder::class, 'project_id');
    }

    public function activityLogs(): HasMany
    {
        return $this->hasMany(PcbProjectActivityLog::class, 'project_id');
    }

    public function latestVersion()
    {
        return $this->versions()->latest('version_number')->first();
    }

    public function canAccess(User $user): bool
    {
        // Owner can always access
        if ($this->user_id === $user->id) {
            return true;
        }

        // Organization members with permission
        if ($this->organization_id === $user->organization_id) {
            return $user->hasPermissionTo('pcb.project.view');
        }

        // Project members
        $member = $this->members()
            ->where('user_id', $user->id)
            ->where(function($q) {
                $q->whereNull('access_expires_at')
                  ->orWhere('access_expires_at', '>', now());
            })
            ->first();

        return $member !== null;
    }

    public function canEdit(User $user): bool
    {
        if (!$this->canAccess($user)) {
            return false;
        }

        if ($this->user_id === $user->id) {
            return true;
        }

        $member = $this->members()
            ->where('user_id', $user->id)
            ->whereIn('role', ['owner', 'admin', 'editor'])
            ->first();

        return $member !== null;
    }

    public function isStatus(string $status): bool
    {
        return $this->status === $status;
    }

    public function canTransitionTo(string $newStatus): bool
    {
        $allowedTransitions = [
            self::STATUS_DRAFT => [self::STATUS_REQUIREMENTS_PENDING, self::STATUS_CANCELLED],
            self::STATUS_REQUIREMENTS_PENDING => [self::STATUS_DESIGN_REQUESTED, self::STATUS_DRAFT],
            self::STATUS_DESIGN_REQUESTED => [self::STATUS_DESIGN_IN_PROGRESS, self::STATUS_CANCELLED],
            self::STATUS_DESIGN_IN_PROGRESS => [self::STATUS_DESIGN_REVIEW, self::STATUS_ON_HOLD],
            self::STATUS_DESIGN_REVIEW => [self::STATUS_DESIGN_APPROVED, self::STATUS_DESIGN_IN_PROGRESS],
            self::STATUS_DESIGN_APPROVED => [self::STATUS_FILES_READY, self::STATUS_ON_HOLD],
            self::STATUS_FILES_READY => [self::STATUS_QUOTE_PENDING, self::STATUS_ON_HOLD],
            self::STATUS_QUOTE_PENDING => [self::STATUS_QUOTED, self::STATUS_CANCELLED],
            self::STATUS_QUOTED => [self::STATUS_AWAITING_APPROVAL, self::STATUS_QUOTE_PENDING],
            self::STATUS_AWAITING_APPROVAL => [self::STATUS_ORDERED, self::STATUS_QUOTED],
            self::STATUS_ORDERED => [self::STATUS_MANUFACTURING, self::STATUS_CANCELLED],
            self::STATUS_MANUFACTURING => [self::STATUS_INSPECTION, self::STATUS_ON_HOLD],
            self::STATUS_INSPECTION => [self::STATUS_SHIPPED, self::STATUS_ON_HOLD],
            self::STATUS_SHIPPED => [self::STATUS_COMPLETED],
            self::STATUS_COMPLETED => [],
            self::STATUS_ON_HOLD => [self::STATUS_DESIGN_IN_PROGRESS, self::STATUS_MANUFACTURING, self::STATUS_CANCELLED],
            self::STATUS_CANCELLED => [],
        ];

        return in_array($newStatus, $allowedTransitions[$this->status] ?? []);
    }

    public function transitionTo(string $newStatus, User $user): bool
    {
        if (!$this->canTransitionTo($newStatus)) {
            return false;
        }

        $oldStatus = $this->status;
        $this->status = $newStatus;
        $this->save();

        // Log activity
        $this->activityLogs()->create([
            'user_id' => $user->id,
            'action' => 'status_changed',
            'entity_type' => 'project',
            'entity_id' => $this->id,
            'description' => "Status changed from {$oldStatus} to {$newStatus}",
            'metadata' => json_encode([
                'old_status' => $oldStatus,
                'new_status' => $newStatus,
            ]),
        ]);

        return true;
    }
}
```

## API Endpoints

### Project Management

```php
// routes/api.php

// Project CRUD
Route::middleware(['auth:sanctum', 'organization.context'])->group(function () {
    Route::prefix('pcb/projects')->group(function () {
        Route::get('/', [PcbProjectController::class, 'index']);
        Route::post('/', [PcbProjectController::class, 'store']);
        Route::get('/{project}', [PcbProjectController::class, 'show']);
        Route::put('/{project}', [PcbProjectController::class, 'update']);
        Route::delete('/{project}', [PcbProjectController::class, 'destroy']);
        
        // Project Actions
        Route::post('/{project}/transition', [PcbProjectController::class, 'transition']);
        Route::post('/{project}/clone', [PcbProjectController::class, 'clone']);
        Route::post('/{project}/archive', [PcbProjectController::class, 'archive']);
        
        // Members
        Route::get('/{project}/members', [PcbProjectMemberController::class, 'index']);
        Route::post('/{project}/members', [PcbProjectMemberController::class, 'store']);
        Route::put('/{project}/members/{member}', [PcbProjectMemberController::class, 'update']);
        Route::delete('/{project}/members/{member}', [PcbProjectMemberController::class, 'destroy']);
        
        // Versions
        Route::get('/{project}/versions', [PcbProjectVersionController::class, 'index']);
        Route::get('/{project}/versions/{version}', [PcbProjectVersionController::class, 'show']);
        
        // Activity
        Route::get('/{project}/activity', [PcbActivityLogController::class, 'index']);
    });
});
```

### Controllers

#### PcbProjectController

```php
<?php

namespace App\Http\Controllers\API\PCB;

use App\Http\Controllers\Controller;
use App\Models\PCB\PcbProject;
use App\Models\PCB\PcbProjectMember;
use App\Models\PCB\PcbProjectVersion;
use App\Models\PCB\PcbProjectActivityLog;
use App\Http\Requests\PCB\StorePcbProjectRequest;
use App\Http\Requests\PCB\UpdatePcbProjectRequest;
use App\Http\Resources\PCB\PcbProjectResource;
use App\Http\Resources\PCB\PcbProjectCollection;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;

class PcbProjectController extends Controller
{
    public function index(Request $request)
    {
        $query = PcbProject::with(['organization', 'assignedEngineer', 'marketplace'])
            ->where('organization_id', Auth::user()->organization_id);

        // Filter by status
        if ($request->has('status')) {
            $query->where('status', $request->status);
        }

        // Filter by stage
        if ($request->has('stage')) {
            $query->where('project_stage', $request->stage);
        }

        // Search
        if ($request->has('search')) {
            $search = $request->search;
            $query->where(function($q) use ($search) {
                $q->where('project_name', 'ILIKE', "%{$search}%")
                  ->orWhere('project_code', 'ILIKE', "%{$search}%")
                  ->orWhere('description', 'ILIKE', "%{$search}%");
            });
        }

        $projects = $query->latest()->paginate($request->get('per_page', 20));

        return new PcbProjectCollection($projects);
    }

    public function store(StorePcbProjectRequest $request)
    {
        DB::beginTransaction();

        try {
            $data = $request->validated();
            $data['user_id'] = Auth::id();
            $data['organization_id'] = Auth::user()->organization_id;
            $data['marketplace_id'] = Auth::user()->currentMarketplace()?->id;

            $project = PcbProject::create($data);

            // Create initial version
            $project->versions()->create([
                'version_number' => 1,
                'change_summary' => 'Initial project creation',
                'changed_by' => Auth::id(),
            ]);

            // Add owner as member
            PcbProjectMember::create([
                'project_id' => $project->id,
                'user_id' => Auth::id(),
                'role' => 'owner',
                'can_view_files' => true,
                'can_download_files' => true,
                'can_upload_files' => true,
                'can_edit_requirements' => true,
                'can_approve_quotes' => true,
                'can_view_costs' => true,
            ]);

            // Log activity
            $project->activityLogs()->create([
                'user_id' => Auth::id(),
                'action' => 'created',
                'entity_type' => 'project',
                'entity_id' => $project->id,
                'description' => 'Project created',
            ]);

            DB::commit();

            return new PcbProjectResource($project);
        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

    public function show(PcbProject $project)
    {
        // Authorization check
        if (!$project->canAccess(Auth::user())) {
            abort(403, 'Unauthorized access to project');
        }

        $project->load([
            'members.user',
            'versions.changedBy',
            'files',
            'designRequests',
            'quotes.supplier',
            'orders',
        ]);

        return new PcbProjectResource($project);
    }

    public function update(UpdatePcbProjectRequest $request, PcbProject $project)
    {
        if (!$project->canEdit(Auth::user())) {
            abort(403, 'Cannot edit this project');
        }

        DB::beginTransaction();

        try {
            $project->update($request->validated());

            // Create new version if significant changes
            if ($request->wantsNewVersion()) {
                $project->versions()->create([
                    'version_number' => $project->current_version + 1,
                    'change_summary' => $request->version_summary,
                    'changed_by' => Auth::id(),
                ]);
                
                $project->increment('current_version');
            }

            // Log activity
            $project->activityLogs()->create([
                'user_id' => Auth::id(),
                'action' => 'updated',
                'entity_type' => 'project',
                'entity_id' => $project->id,
                'description' => 'Project updated',
                'metadata' => json_encode($request->validated()),
            ]);

            DB::commit();

            return new PcbProjectResource($project->fresh());
        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

    public function destroy(PcbProject $project)
    {
        if (!$project->canEdit(Auth::user())) {
            abort(403, 'Cannot delete this project');
        }

        // Check if project has orders
        if ($project->orders()->count() > 0) {
            return response()->json([
                'message' => 'Cannot delete project with existing orders'
            ], 422);
        }

        $project->delete();

        return response()->json(null, 204);
    }

    public function transition(Request $request, PcbProject $project)
    {
        if (!$project->canEdit(Auth::user())) {
            abort(403, 'Cannot modify this project');
        }

        $request->validate([
            'status' => 'required|string|in:draft,requirements_pending,design_requested,design_in_progress,design_review,design_approved,files_ready,quote_pending,quoted,awaiting_approval,ordered,manufacturing,inspection,shipped,completed,on_hold,cancelled',
            'comment' => 'nullable|string|max:1000',
        ]);

        if (!$project->transitionTo($request->status, Auth::user())) {
            return response()->json([
                'message' => 'Invalid status transition'
            ], 422);
        }

        if ($request->has('comment')) {
            $project->activityLogs()->create([
                'user_id' => Auth::id(),
                'action' => 'status_transition_comment',
                'entity_type' => 'project',
                'entity_id' => $project->id,
                'description' => $request->comment,
            ]);
        }

        return new PcbProjectResource($project->fresh());
    }

    public function clone(Request $request, PcbProject $project)
    {
        if (!$project->canAccess(Auth::user())) {
            abort(403, 'Cannot access this project');
        }

        DB::beginTransaction();

        try {
            $cloned = $project->replicate();
            $cloned->project_code = 'PCB-' . strtoupper(\Str::random(8));
            $cloned->project_name = $project->project_name . ' (Copy)';
            $cloned->status = PcbProject::STATUS_DRAFT;
            $cloned->current_version = 1;
            $cloned->save();

            // Clone members
            foreach ($project->members as $member) {
                $clonedMember = $member->replicate();
                $clonedMember->project_id = $cloned->id;
                $clonedMember->save();
            }

            // Log activity
            $cloned->activityLogs()->create([
                'user_id' => Auth::id(),
                'action' => 'cloned',
                'entity_type' => 'project',
                'entity_id' => $cloned->id,
                'description' => "Cloned from project {$project->project_code}",
                'metadata' => json_encode(['source_project_id' => $project->id]),
            ]);

            DB::commit();

            return new PcbProjectResource($cloned);
        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }
}
```

## Frontend Components

### Project Workspace Shell (Vue/Nuxt)

```vue
<template>
  <div class="pcb-project-workspace">
    <!-- Header -->
    <div class="workspace-header">
      <div class="project-info">
        <h1>{{ project.project_name }}</h1>
        <span class="project-code">{{ project.project_code }}</span>
        <status-badge :status="project.status" />
      </div>
      
      <div class="workspace-actions">
        <button @click="saveProject" :disabled="!canEdit">Save</button>
        <button @click="transitionStatus" v-if="canEdit">Change Status</button>
        <button @click="cloneProject">Clone</button>
      </div>
    </div>

    <!-- Tabs -->
    <tabs :tabs="workspaceTabs" v-model="activeTab">
      <tab name="overview" label="Overview">
        <project-overview :project="project" />
      </tab>
      
      <tab name="requirements" label="Requirements">
        <project-requirements 
          :project="project" 
          :editable="canEdit"
          @updated="$emit('project-updated')"
        />
      </tab>
      
      <tab name="design" label="Design">
        <project-design :project="project" />
      </tab>
      
      <tab name="files" label="Files">
        <project-files 
          :project="project" 
          :can-upload="canUploadFiles"
          @file-uploaded="$emit('file-uploaded', $event)"
        />
      </tab>
      
      <tab name="gerber" label="Gerber Viewer">
        <gerber-viewer :project="project" />
      </tab>
      
      <tab name="bom" label="BOM">
        <project-bom :project="project" />
      </tab>
      
      <tab name="cpl" label="CPL">
        <project-cpl :project="project" />
      </tab>
      
      <tab name="components" label="Component Matching">
        <component-matching :project="project" />
      </tab>
      
      <tab name="dfm" label="DFM">
        <dfm-analysis :project="project" />
      </tab>
      
      <tab name="quotes" label="Quotes">
        <project-quotes :project="project" />
      </tab>
      
      <tab name="suppliers" label="Suppliers">
        <project-suppliers :project="project" />
      </tab>
      
      <tab name="messages" label="Messages">
        <project-messages :project="project" />
      </tab>
      
      <tab name="orders" label="Orders">
        <project-orders :project="project" />
      </tab>
      
      <tab name="production" label="Production">
        <production-tracking :project="project" />
      </tab>
      
      <tab name="quality" label="Quality">
        <quality-reports :project="project" />
      </tab>
      
      <tab name="history" label="History">
        <project-history :project="project" />
      </tab>
    </tabs>
  </div>
</template>

<script setup>
import { ref, computed } from 'vue';
import { useAuth } from '@/composables/useAuth';
import StatusBadge from './StatusBadge.vue';
import ProjectOverview from './tabs/ProjectOverview.vue';
import ProjectRequirements from './tabs/ProjectRequirements.vue';
import ProjectDesign from './tabs/ProjectDesign.vue';
import ProjectFiles from './tabs/ProjectFiles.vue';
import GerberViewer from './tabs/GerberViewer.vue';
import ProjectBom from './tabs/ProjectBom.vue';
import ProjectCpl from './tabs/ProjectCpl.vue';
import ComponentMatching from './tabs/ComponentMatching.vue';
import DfmAnalysis from './tabs/DfmAnalysis.vue';
import ProjectQuotes from './tabs/ProjectQuotes.vue';
import ProjectSuppliers from './tabs/ProjectSuppliers.vue';
import ProjectMessages from './tabs/ProjectMessages.vue';
import ProjectOrders from './tabs/ProjectOrders.vue';
import ProductionTracking from './tabs/ProductionTracking.vue';
import QualityReports from './tabs/QualityReports.vue';
import ProjectHistory from './tabs/ProjectHistory.vue';

const props = defineProps({
  project: {
    type: Object,
    required: true
  }
});

const emit = defineEmits(['project-updated', 'file-uploaded']);

const { user, hasPermission } = useAuth();

const activeTab = ref('overview');

const workspaceTabs = [
  { id: 'overview', label: 'Overview' },
  { id: 'requirements', label: 'Requirements' },
  { id: 'design', label: 'Design' },
  { id: 'files', label: 'Files' },
  { id: 'gerber', label: 'Gerber Viewer' },
  { id: 'bom', label: 'BOM' },
  { id: 'cpl', label: 'CPL' },
  { id: 'components', label: 'Component Matching' },
  { id: 'dfm', label: 'DFM' },
  { id: 'quotes', label: 'Quotes' },
  { id: 'suppliers', label: 'Suppliers' },
  { id: 'messages', label: 'Messages' },
  { id: 'orders', label: 'Orders' },
  { id: 'production', label: 'Production' },
  { id: 'quality', label: 'Quality' },
  { id: 'history', label: 'History' },
];

const canEdit = computed(() => {
  return user.value.id === props.project.user_id || 
         hasPermission('pcb.project.edit');
});

const canUploadFiles = computed(() => {
  return hasPermission('pcb.file.upload');
});

const saveProject = async () => {
  // Save logic
};

const transitionStatus = async () => {
  // Status transition logic
};

const cloneProject = async () => {
  // Clone logic
};
</script>

<style scoped>
.pcb-project-workspace {
  @apply bg-white rounded-lg shadow-sm;
}

.workspace-header {
  @apply flex justify-between items-center p-6 border-b;
}

.project-info {
  @apply flex items-center gap-4;
}

.project-code {
  @apply text-sm text-gray-500 font-mono;
}

.workspace-actions {
  @apply flex gap-3;
}
</style>
```

## Authorization Policies

### PcbProjectPolicy

```php
<?php

namespace App\Policies\PCB;

use App\Models\User;
use App\Models\PCB\PcbProject;

class PcbProjectPolicy
{
    public function view(User $user, PcbProject $project): bool
    {
        // Owner can view
        if ($project->user_id === $user->id) {
            return true;
        }

        // Same organization with permission
        if ($project->organization_id === $user->organization_id) {
            return $user->hasPermissionTo('pcb.project.view');
        }

        // Project member
        $member = $project->members()
            ->where('user_id', $user->id)
            ->where(function($q) {
                $q->whereNull('access_expires_at')
                  ->orWhere('access_expires_at', '>', now());
            })
            ->first();

        return $member !== null;
    }

    public function create(User $user): bool
    {
        return $user->hasPermissionTo('pcb.project.create');
    }

    public function update(User $user, PcbProject $project): bool
    {
        if ($project->user_id === $user->id) {
            return true;
        }

        $member = $project->members()
            ->where('user_id', $user->id)
            ->whereIn('role', ['owner', 'admin', 'editor'])
            ->first();

        return $member !== null;
    }

    public function delete(User $user, PcbProject $project): bool
    {
        if ($project->user_id === $user->id) {
            return true;
        }

        return $user->hasPermissionTo('pcb.project.delete');
    }

    public function uploadFiles(User $user, PcbProject $project): bool
    {
        if (!$this->view($user, $project)) {
            return false;
        }

        if ($project->user_id === $user->id) {
            return true;
        }

        $member = $project->members()
            ->where('user_id', $user->id)
            ->where('can_upload_files', true)
            ->first();

        return $member !== null || $user->hasPermissionTo('pcb.file.upload');
    }

    public function downloadFiles(User $user, PcbProject $project): bool
    {
        if (!$this->view($user, $project)) {
            return false;
        }

        if ($project->user_id === $user->id) {
            return true;
        }

        $member = $project->members()
            ->where('user_id', $user->id)
            ->where('can_download_files', true)
            ->first();

        return $member !== null || $user->hasPermissionTo('pcb.file.download');
    }

    public function approveQuotes(User $user, PcbProject $project): bool
    {
        if ($project->user_id === $user->id) {
            return true;
        }

        $member = $project->members()
            ->where('user_id', $user->id)
            ->where('can_approve_quotes', true)
            ->first();

        return $member !== null || $user->hasPermissionTo('pcb.quote.approve');
    }

    public function viewCosts(User $user, PcbProject $project): bool
    {
        if ($project->user_id === $user->id) {
            return true;
        }

        $member = $project->members()
            ->where('user_id', $user->id)
            ->where('can_view_costs', true)
            ->first();

        return $member !== null || $user->hasRole('admin');
    }
}
```

## Testing

### PcbProjectTest

```php
<?php

namespace Tests\Feature\API\PCB;

use Tests\TestCase;
use App\Models\User;
use App\Models\Organization;
use App\Models\PCB\PcbProject;
use App\Models\PCB\PcbProjectMember;
use Illuminate\Foundation\Testing\RefreshDatabase;

class PcbProjectTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_can_create_project()
    {
        $user = User::factory()->create();
        
        $response = $this->actingAs($user)
            ->postJson('/api/pcb/projects', [
                'project_name' => 'Test PCB Project',
                'description' => 'Test description',
                'application_type' => 'IoT',
                'project_stage' => 'prototype',
                'target_quantity' => 100,
                'currency_code' => 'USD',
            ]);

        $response->assertStatus(201)
            ->assertJsonPath('data.project_name', 'Test PCB Project')
            ->assertJsonPath('data.status', 'draft');

        $this->assertDatabaseHas('pcb_projects', [
            'project_name' => 'Test PCB Project',
            'user_id' => $user->id,
        ]);
    }

    public function test_user_can_view_own_project()
    {
        $user = User::factory()->create();
        
        $project = PcbProject::factory()->create([
            'user_id' => $user->id,
        ]);

        $response = $this->actingAs($user)
            ->getJson("/api/pcb/projects/{$project->id}");

        $response->assertStatus(200)
            ->assertJsonPath('data.id', $project->id);
    }

    public function test_user_cannot_view_other_organization_project()
    {
        $user = User::factory()->create();
        $otherOrg = Organization::factory()->create();
        $otherUser = User::factory()->create(['organization_id' => $otherOrg->id]);
        
        $project = PcbProject::factory()->create([
            'user_id' => $otherUser->id,
            'organization_id' => $otherOrg->id,
        ]);

        $response = $this->actingAs($user)
            ->getJson("/api/pcb/projects/{$project->id}");

        $response->assertStatus(403);
    }

    public function test_project_member_can_access_project()
    {
        $owner = User::factory()->create();
        $member = User::factory()->create();
        
        $project = PcbProject::factory()->create([
            'user_id' => $owner->id,
        ]);

        PcbProjectMember::create([
            'project_id' => $project->id,
            'user_id' => $member->id,
            'role' => 'viewer',
        ]);

        $response = $this->actingAs($member)
            ->getJson("/api/pcb/projects/{$project->id}");

        $response->assertStatus(200);
    }

    public function test_status_transition_works_correctly()
    {
        $user = User::factory()->create();
        
        $project = PcbProject::factory()->create([
            'user_id' => $user->id,
            'status' => 'draft',
        ]);

        $response = $this->actingAs($user)
            ->postJson("/api/pcb/projects/{$project->id}/transition", [
                'status' => 'requirements_pending',
            ]);

        $response->assertStatus(200)
            ->assertJsonPath('data.status', 'requirements_pending');

        $this->assertDatabaseHas('pcb_projects', [
            'id' => $project->id,
            'status' => 'requirements_pending',
        ]);
    }

    public function test_invalid_status_transition_fails()
    {
        $user = User::factory()->create();
        
        $project = PcbProject::factory()->create([
            'user_id' => $user->id,
            'status' => 'draft',
        ]);

        $response = $this->actingAs($user)
            ->postJson("/api/pcb/projects/{$project->id}/transition", [
                'status' => 'manufacturing', // Invalid transition
            ]);

        $response->assertStatus(422);
    }

    public function test_project_cloning_creates_copy()
    {
        $user = User::factory()->create();
        
        $project = PcbProject::factory()->create([
            'user_id' => $user->id,
            'project_name' => 'Original Project',
        ]);

        $response = $this->actingAs($user)
            ->postJson("/api/pcb/projects/{$project->id}/clone");

        $response->assertStatus(201)
            ->assertJsonPath('data.project_name', 'Original Project (Copy)');

        $this->assertDatabaseCount('pcb_projects', 2);
    }

    public function test_project_with_orders_cannot_be_deleted()
    {
        $user = User::factory()->create();
        
        $project = PcbProject::factory()->create([
            'user_id' => $user->id,
        ]);

        // Create mock order
        $project->orders()->create([
            'order_number' => 'ORD-TEST-001',
            'status' => 'pending',
        ]);

        $response = $this->actingAs($user)
            ->deleteJson("/api/pcb/projects/{$project->id}");

        $response->assertStatus(422);
    }
}
```

## Security Considerations

1. **Organization Isolation**: All queries must filter by organization_id
2. **Member Access Expiry**: Check access_expires_at for all member accesses
3. **NDA Requirements**: Verify nda_accepted for supplier access
4. **Audit Logging**: Log all project access and modifications
5. **File Access Control**: Separate file permissions from project permissions
6. **Cost Visibility**: Restrict cost viewing to authorized roles only

## Performance Optimization

1. **Eager Loading**: Always load related data (members, versions, files)
2. **Pagination**: Paginate large lists (activity logs, files)
3. **Caching**: Cache project summaries and status counts
4. **Indexing**: Ensure proper indexes on foreign keys and status fields
5. **Soft Deletes**: Use soft deletes for audit trail preservation

## Migration Checklist

- [ ] Create pcb_projects table
- [ ] Create pcb_project_members table
- [ ] Create pcb_project_versions table
- [ ] Create pcb_project_activity_logs table
- [ ] Seed initial permissions
- [ ] Register policies
- [ ] Create API routes
- [ ] Build frontend components
- [ ] Write tests
- [ ] Document API endpoints
