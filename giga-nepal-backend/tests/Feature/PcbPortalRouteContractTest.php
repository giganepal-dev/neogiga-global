<?php

namespace Tests\Feature;

use App\Models\Pcb\PcbProject;
use App\Models\Pcb\PcbProjectActivityLog;
use App\Models\Pcb\PcbProjectMember;
use App\Models\Pcb\PcbProjectVersion;
use App\Models\Pcb\PcbFile;
use App\Models\Pcb\PcbQuoteConfiguration;
use App\Models\Pcb\PcbGerberAnalysisRun;
use App\Models\Pcb\PcbCplImport;
use App\Models\Pcb\PcbComponentMatch;
use App\Models\Pcb\PcbOrder;
use Tests\TestCase;

class PcbPortalRouteContractTest extends TestCase
{
    public function test_workspace_actions_have_named_protected_route_contracts(): void
    {
        $routes = file_get_contents(dirname(__DIR__, 2).'/routes/web.php');

        foreach ([
            "Route::patch('/en/projects/{project}', [PcbPortalController::class, 'update'])->middleware('throttle:10,1')->name('pcb.projects.update');",
            "Route::post('/en/projects/{project}/cancel', [PcbPortalController::class, 'cancel'])->middleware('throttle:10,1')->name('pcb.projects.cancel');",
            "Route::post('/en/projects/{project}/quotes/{quote}/approve', [PcbPortalController::class, 'approveQuote'])->middleware('throttle:10,1')->name('pcb.quotes.approve');",
            "Route::post('/en/projects/{project}/quotes/{quote}/reject', [PcbPortalController::class, 'rejectQuote'])->middleware('throttle:10,1')->name('pcb.quotes.reject');",
        ] as $registration) {
            $this->assertStringContainsString($registration, $routes);
        }
    }

    public function test_project_relations_use_the_persisted_project_id_column(): void
    {
        $project = new PcbProject();

        $this->assertSame('project_id', $project->members()->getForeignKeyName());
        $this->assertSame('project_id', $project->versions()->getForeignKeyName());
        $this->assertSame('project_id', $project->files()->getForeignKeyName());
        $this->assertSame('project_id', $project->activityLogs()->getForeignKeyName());
        $this->assertSame('project_id', $project->gerberAnalysisRuns()->getForeignKeyName());
        $this->assertSame('project_id', $project->quoteConfigurations()->getForeignKeyName());
        $this->assertSame('project_id', $project->cplImports()->getForeignKeyName());
        $this->assertSame('project_id', $project->componentMatches()->getForeignKeyName());

        foreach ([
            new PcbProjectMember(), new PcbProjectVersion(), new PcbFile(),
            new PcbProjectActivityLog(), new PcbGerberAnalysisRun(),
            new PcbQuoteConfiguration(), new PcbCplImport(),
            new PcbComponentMatch(), new PcbOrder(),
        ] as $relationModel) {
            $this->assertSame('project_id', $relationModel->project()->getForeignKeyName());
        }
    }
}
