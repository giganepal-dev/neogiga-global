<?php

namespace App\Services\Pcb;

use App\Models\Pcb\PcbFile;
use App\Models\Pcb\PcbProject;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class PcbFileService
{
    public function store(PcbProject $project, $user, UploadedFile $file, string $fileType): PcbFile
    {
        $allowed = config('pcb.allowed_extensions.'.$fileType, ['zip', 'pdf', 'csv', 'txt']);
        $ext = strtolower((string) $file->getClientOriginalExtension());

        abort_unless(in_array($ext, $allowed, true), 422, "File type .{$ext} not allowed for {$fileType}.");

        $disk = config('pcb.storage_disk', 'local');
        $basePath = config('pcb.storage_path', 'pcb-projects');
        $storedName = Str::uuid().'.'.$ext;
        $path = $file->storeAs("{$basePath}/{$project->id}", $storedName, ['disk' => $disk]);

        $pcbFile = $project->files()->create([
            'user_id' => $user->id,
            'version_id' => $project->current_version ? $project->currentVersion?->id : null,
            'filename_original' => $file->getClientOriginalName(),
            'filename_stored' => $storedName,
            'file_type' => $fileType,
            'mime_type' => $file->getMimeType() ?: 'application/octet-stream',
            'file_size' => $file->getSize(),
            'storage_disk' => $disk,
            'storage_path' => $path,
            'processing_status' => 'pending',
        ]);

        $project->activityLogs()->create([
            'user_id' => $user->id,
            'action' => 'file_uploaded',
            'description' => "Uploaded {$fileType} file: {$file->getClientOriginalName()}",
            'metadata' => ['file_id' => $pcbFile->id, 'file_type' => $fileType, 'size' => $file->getSize()],
            'ip_address' => request()->ip(),
            'user_agent' => request()->userAgent(),
        ]);

        if ($fileType === 'gerber') { \App\Jobs\Pcb\RunGerberAnalysis::dispatch($pcbFile, $user->id); }

        return $pcbFile;
    }
}
