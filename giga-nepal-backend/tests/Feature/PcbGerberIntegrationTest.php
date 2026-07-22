<?php

namespace Tests\Feature;

use App\Jobs\Pcb\RunGerberAnalysis;
use App\Models\Pcb\PcbFile;
use App\Models\Pcb\PcbProject;
use App\Models\User;
use App\Services\Pcb\PcbDfmService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\URL;
use Tests\TestCase;
use ZipArchive;

class PcbGerberIntegrationTest extends TestCase
{
    use RefreshDatabase;

    public function test_gerber_analysis_detects_dimensions_and_supplies_private_layer_preview(): void
    {
        config()->set('pcb.domain', 'pcb.neogiga.com');
        Storage::fake('local');
        $user = User::factory()->create();
        $project = PcbProject::create([
            'user_id' => $user->id,
            'name' => 'Gerber integration board',
            'target_quantity' => 25,
            'destination_country' => 'NP',
        ]);
        $archive = $this->gerberArchive();
        $storagePath = 'pcb-projects/'.$project->id.'/board.zip';
        Storage::disk('local')->put($storagePath, file_get_contents($archive));
        $file = PcbFile::create([
            'project_id' => $project->id,
            'user_id' => $user->id,
            'filename_original' => 'board.zip',
            'filename_stored' => 'board.zip',
            'file_type' => 'gerber',
            'mime_type' => 'application/zip',
            'file_size' => filesize($archive),
            'storage_disk' => 'local',
            'storage_path' => $storagePath,
            'processing_status' => 'pending',
        ]);

        $run = app(PcbDfmService::class)->analyze($file, $user->id);

        $this->assertSame('completed', $run->status);
        $this->assertSame('50.0000', $run->detected_width_mm);
        $this->assertSame('25.0000', $run->detected_height_mm);
        $this->assertSame(2, $run->detected_layer_count);
        $this->assertSame(2, $run->detected_hole_count);
        $outline = $run->detectedLayers()->where('detected_type', 'board_outline')->firstOrFail();
        $this->assertSame('nested/board.GKO', $outline->metadata['archive_path']);
        $this->assertStringContainsString('X500000Y250000D01', app(PcbDfmService::class)->layerContents($file, $outline));

        $layerUrl = URL::temporarySignedRoute('pcb.layers.view', now()->addMinutes(5), [
            'project' => $project->id,
            'analysis' => $run->id,
            'layer' => $outline->id,
        ]);
        $this->actingAs($user)->get($layerUrl)
            ->assertOk()
            ->assertHeader('Content-Type', 'text/plain; charset=UTF-8')
            ->assertSee('X500000Y250000D01');
        $this->actingAs(User::factory()->create())->get($layerUrl)->assertForbidden();

        @unlink($archive);
    }

    public function test_project_workspace_prefills_only_detected_gerber_technical_fields(): void
    {
        config()->set('pcb.domain', 'pcb.neogiga.com');
        Storage::fake('local');
        $user = User::factory()->create();
        $project = PcbProject::create([
            'user_id' => $user->id,
            'name' => 'Autofill board',
            'target_quantity' => 10,
            'destination_country' => 'IN',
        ]);
        $archive = $this->gerberArchive();
        $storagePath = 'pcb-projects/'.$project->id.'/autofill.zip';
        Storage::disk('local')->put($storagePath, file_get_contents($archive));
        $file = PcbFile::create([
            'project_id' => $project->id,
            'user_id' => $user->id,
            'filename_original' => 'autofill.zip',
            'filename_stored' => 'autofill.zip',
            'file_type' => 'gerber',
            'mime_type' => 'application/zip',
            'file_size' => filesize($archive),
            'storage_disk' => 'local',
            'storage_path' => $storagePath,
            'processing_status' => 'pending',
        ]);
        app(PcbDfmService::class)->analyze($file, $user->id);

        $this->actingAs($user)
            ->get('http://pcb.neogiga.com/en/projects/'.$project->id)
            ->assertOk()
            ->assertSee('Technical fields suggested from Gerber')
            ->assertSee('Advisory only')
            ->assertSee('value="50.0000"', false)
            ->assertSee('value="25.0000"', false)
            ->assertSee('value="2"', false)
            ->assertSee('gerber-to-svg.min.js')
            ->assertSee('data-gv-action="zoom-in"', false)
            ->assertDontSee('onclick=', false);

        @unlink($archive);
    }

    public function test_project_owner_can_queue_fresh_analysis_for_an_existing_gerber(): void
    {
        config()->set('pcb.domain', 'pcb.neogiga.com');
        Queue::fake();
        $user = User::factory()->create();
        $project = PcbProject::create(['user_id' => $user->id, 'name' => 'Existing board']);
        $file = PcbFile::create([
            'project_id' => $project->id,
            'user_id' => $user->id,
            'filename_original' => 'existing.zip',
            'filename_stored' => 'existing.zip',
            'file_type' => 'gerber',
            'mime_type' => 'application/zip',
            'file_size' => 100,
            'storage_disk' => 'local',
            'storage_path' => 'pcb-projects/existing.zip',
            'processing_status' => 'failed',
            'processing_error' => 'Legacy parser failed.',
        ]);

        $this->actingAs($user)
            ->post('http://pcb.neogiga.com/en/projects/'.$project->id.'/gerber/'.$file->id.'/analyze')
            ->assertSessionHas('status');

        $this->assertDatabaseHas('pcb_files', [
            'id' => $file->id,
            'processing_status' => 'pending',
            'processing_error' => null,
        ]);
        Queue::assertPushed(RunGerberAnalysis::class);
    }

    private function gerberArchive(): string
    {
        $path = tempnam(sys_get_temp_dir(), 'pcb-gerber-test-');
        $zip = new ZipArchive;
        $zip->open($path, ZipArchive::OVERWRITE);
        $outline = "%FSLAX24Y24*%\n%MOMM*%\nX000000Y000000D02*\nX500000Y000000D01*\nX500000Y250000D01*\nX000000Y250000D01*\nX000000Y000000D01*\nM02*\n";
        $copper = "%FSLAX24Y24*%\n%MOMM*%\nX000000Y000000D02*\nX500000Y250000D01*\nM02*\n";
        $drill = "M48\nT01C0.300\n%\nT01\nX1000Y1000\nX2000Y2000\nM30\n";
        $zip->addFromString('nested/board.GKO', $outline);
        $zip->addFromString('nested/board.GTL', $copper);
        $zip->addFromString('nested/board.GBL', $copper);
        $zip->addFromString('nested/board.DRL', $drill);
        $zip->close();

        return $path;
    }
}
