<?php

use App\Http\Controllers\Pcb\PcbProjectController;
use App\Http\Controllers\Pcb\PcbFileController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| PCB Platform API Routes
|--------------------------------------------------------------------------
|
| These routes are loaded for the pcb.neogiga.com application.
| They share authentication with the main NeoGiga platform.
|
*/

// Public PCB routes
Route::prefix('api/pcb')->group(function () {
    // Capabilities, pricing info, public resources
    Route::get('/capabilities', [PcbProjectController::class, 'index'])->name('pcb.capabilities');
});

// Protected PCB routes (require authentication)
Route::prefix('api/pcb')->middleware(['auth:sanctum'])->group(function () {
    
    // PCB Projects
    Route::prefix('projects')->group(function () {
        Route::get('/', [PcbProjectController::class, 'index'])->name('pcb.projects.index');
        Route::post('/', [PcbProjectController::class, 'store'])->name('pcb.projects.store');
        Route::get('/{project}', [PcbProjectController::class, 'show'])->name('pcb.projects.show');
        Route::put('/{project}', [PcbProjectController::class, 'update'])->name('pcb.projects.update');
        Route::delete('/{project}', [PcbProjectController::class, 'destroy'])->name('pcb.projects.destroy');
        
        // Project activity
        Route::get('/{project}/activity', [PcbProjectController::class, 'activity'])->name('pcb.projects.activity');
        
        // Project members (TODO: Create controller)
        // Route::get('/{project}/members', [PcbProjectMemberController::class, 'index']);
        // Route::post('/{project}/members', [PcbProjectMemberController::class, 'store']);
        // Route::delete('/{project}/members/{member}', [PcbProjectMemberController::class, 'destroy']);
        
        // Project versions (TODO: Create controller)
        // Route::get('/{project}/versions', [PcbProjectVersionController::class, 'index']);
        // Route::post('/{project}/versions', [PcbProjectVersionController::class, 'store']);
        
        // Project files
        Route::prefix('{project}/files')->group(function () {
            Route::get('/', [PcbFileController::class, 'index'])->name('pcb.files.index');
            Route::post('/', [PcbFileController::class, 'store'])->name('pcb.files.store');
            Route::get('/{file}', [PcbFileController::class, 'show'])->name('pcb.files.show');
            Route::delete('/{file}', [PcbFileController::class, 'destroy'])->name('pcb.files.destroy');
            
            // File download with signed URL
            Route::get('/{file}/download', [PcbFileController::class, 'download'])->name('pcb.files.download');
            
            // File upload endpoint (multipart)
            Route::post('/upload', [PcbFileController::class, 'upload'])->name('pcb.files.upload');
            
            // Gerber-specific endpoints
            Route::post('/gerber/upload', [PcbFileController::class, 'uploadGerber'])->name('pcb.files.gerber.upload');
            Route::get('/gerber/analyze', [PcbFileController::class, 'analyzeGerber'])->name('pcb.files.gerber.analyze');
        });
        
        // Quote endpoints (TODO)
        // Route::get('/{project}/quote', [PcbQuoteController::class, 'generate']);
        // Route::post('/{project}/quote/request', [PcbQuoteController::class, 'requestQuote']);
        
        // BOM/CPL endpoints (TODO - integrate with existing BOM system)
        // Route::get('/{project}/bom', [PcbBomController::class, 'index']);
        // Route::post('/{project}/bom/import', [PcbBomController::class, 'import']);
        
        // DFM endpoints (TODO)
        // Route::get('/{project}/dfm', [PcbDfmController::class, 'run']);
    });
    
    // Direct file access (for signed URLs)
    Route::get('/files/{file}/download', [PcbFileController::class, 'downloadWithToken'])
        ->name('pcb.files.download.token');
});
