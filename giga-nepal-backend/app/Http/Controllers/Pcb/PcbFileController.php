<?php

namespace App\Http\Controllers\Pcb;

use App\Http\Controllers\Controller;
use App\Models\Pcb\PcbFile;
use App\Models\Pcb\PcbProject;
use App\Services\Pcb\PcbFileService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;

class PcbFileController extends Controller
{
    public function store(Request $request, PcbProject $project, PcbFileService $files): JsonResponse
    {
        abort_unless($project->canBeEditedBy($request->user()), 403);
        $data = $request->validate([
            'file_type' => ['required', 'in:gerber,bom,cpl,schematic,pcb_source,step,assembly_drawing,other'],
            'file' => ['required', 'file', 'max:'.((int) config('pcb.max_file_size_mb', 100) * 1024)],
        ]);

        $file = $files->store($project, $request->user(), $request->file('file'), $data['file_type']);

        if ($data['file_type'] === 'gerber' && in_array($project->status, ['draft', 'requirements_pending'], true)) {
            $project->update(['status' => 'files_ready']);
        }

        return response()->json(['success' => true, 'data' => $file, 'message' => 'Private PCB file stored.'], 201);
    }

    public function download(Request $request, PcbProject $project, PcbFile $file): StreamedResponse
    {
        abort_unless($project->canBeAccessedBy($request->user()), 403);
        abort_unless($file->project_id === $project->id, 404);
        abort_unless(Storage::disk($file->storage_disk)->exists($file->storage_path), 404);

        $file->accessLogs()->create([
            'user_id' => $request->user()->id,
            'action' => 'download',
            'ip_address' => $request->ip(),
            'user_agent' => $request->userAgent(),
            'reason' => 'authenticated_api',
        ]);

        return Storage::disk($file->storage_disk)->download($file->storage_path, $file->filename_original, [
            'Content-Type' => $file->mime_type,
            'X-Robots-Tag' => 'noindex, nofollow, noarchive',
        ]);
    }

    public function destroy(Request $request, PcbProject $project, PcbFile $file): JsonResponse
    {
        abort_unless($project->canBeEditedBy($request->user()), 403);
        abort_unless($file->project_id === $project->id, 404);
        abort_unless(in_array($project->status, ['draft', 'requirements_pending', 'files_ready', 'cancelled'], true), 409);

        $file->delete();
        $project->activityLogs()->create([
            'user_id' => $request->user()->id,
            'action' => 'file_deactivated',
            'description' => 'PCB file removed from the active project workspace',
            'metadata' => ['file_id' => $file->id, 'filename' => $file->filename_original],
            'ip_address' => $request->ip(),
            'user_agent' => $request->userAgent(),
        ]);

        return response()->json(['success' => true, 'message' => 'File deactivated. The private object is retained for audit recovery.']);
    }
}
