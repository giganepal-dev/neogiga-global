<?php

namespace App\Services\Pcb;

use App\Models\Pcb\PcbAnalysisWarning;
use App\Models\Pcb\PcbDetectedLayer;
use App\Models\Pcb\PcbFile;
use App\Models\Pcb\PcbFileScanResult;
use App\Models\Pcb\PcbFileVersion;
use App\Models\Pcb\PcbGerberAnalysisRun;
use App\Models\Pcb\PcbProject;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use ZipArchive;

class PcbFileService
{
    public function store(PcbProject $project, User $user, UploadedFile $upload, string $fileType): PcbFile
    {
        $extension = strtolower($upload->getClientOriginalExtension());
        $allowed = config("pcb.allowed_extensions.{$fileType}", []);

        if (! in_array($extension, $allowed, true)) {
            throw ValidationException::withMessages([
                'file' => 'This file extension is not allowed for the selected document type.',
            ]);
        }

        $maxBytes = max(1, (int) config('pcb.max_file_size_mb', 100)) * 1024 * 1024;
        if (($upload->getSize() ?: 0) > $maxBytes) {
            throw ValidationException::withMessages(['file' => 'The uploaded file exceeds the configured PCB file limit.']);
        }

        $archive = $extension === 'zip' ? $this->inspectArchive($upload) : null;
        $checksum = hash_file('sha256', $upload->getPathname());
        $storedName = Str::uuid().'.'.$extension;
        $directory = trim((string) config('pcb.storage_path', 'pcb-projects'), '/').'/'.$project->id;
        $disk = (string) config('pcb.storage_disk', 'local');
        $path = Storage::disk($disk)->putFileAs($directory, $upload, $storedName);

        if (! $path) {
            throw ValidationException::withMessages(['file' => 'The file could not be stored. Please try again.']);
        }

        try {
            return DB::transaction(function () use ($project, $user, $upload, $fileType, $disk, $path, $storedName, $checksum, $archive) {
                $projectVersion = $project->versions()->latest('version_number')->first();
                if (! $projectVersion) {
                    $projectVersion = $project->versions()->create([
                        'version_number' => max(1, (int) $project->current_version),
                        'change_summary' => 'Initial project version',
                        'created_by_id' => $user->id,
                    ]);
                }

                $file = PcbFile::create([
                    'project_id' => $project->id,
                    'user_id' => $user->id,
                    'version_id' => $projectVersion->id,
                    'filename_original' => mb_substr($upload->getClientOriginalName(), 0, 255),
                    'filename_stored' => $storedName,
                    'file_type' => $fileType,
                    'mime_type' => (string) ($upload->getMimeType() ?: 'application/octet-stream'),
                    'file_size' => (int) $upload->getSize(),
                    'layer_type' => in_array($fileType, ['bom', 'cpl', 'schematic', 'pcb_source', 'step', 'assembly_drawing'], true) ? $fileType : null,
                    'storage_disk' => $disk,
                    'storage_path' => $path,
                    'malware_scanned' => false,
                    'malware_clean' => true,
                    'signature_validated' => $archive !== null,
                    'mime_validated' => true,
                    'processing_status' => 'completed',
                    'nda_required' => $project->confidentiality === 'nda_required',
                    'metadata' => array_filter([
                        'checksum_sha256' => $checksum,
                        'archive' => $archive,
                        'validation' => 'structural_only',
                        'malware_scan' => 'pending_external_scanner',
                    ]),
                ]);

                PcbFileVersion::create([
                    'file_id' => $file->id,
                    'version_number' => 1,
                    'filename_original' => $file->filename_original,
                    'filename_stored' => $file->filename_stored,
                    'storage_path' => $file->storage_path,
                    'file_size' => $file->file_size,
                    'change_summary' => 'Initial upload',
                    'uploaded_by_id' => $user->id,
                ]);

                PcbFileScanResult::create([
                    'file_id' => $file->id,
                    'scanner_name' => 'neogiga-structure-validator',
                    'scanner_version' => '1.0',
                    'is_clean' => true,
                    'scan_details' => [
                        'scope' => 'filename, extension, MIME and archive safety',
                        'malware_scan_completed' => false,
                    ],
                    'scan_duration_ms' => 0,
                ]);

                if ($fileType === 'gerber' && $archive) {
                    $this->recordGerberInspection($project, $file, $user, $archive['entries']);
                }

                $project->activityLogs()->create([
                    'user_id' => $user->id,
                    'action' => 'file_uploaded',
                    'description' => "{$fileType} file uploaded",
                    'metadata' => ['file_id' => $file->id, 'filename' => $file->filename_original],
                    'ip_address' => request()->ip(),
                    'user_agent' => request()->userAgent(),
                ]);

                return $file;
            });
        } catch (\Throwable $exception) {
            Storage::disk($disk)->delete($path);
            throw $exception;
        }
    }

    private function inspectArchive(UploadedFile $upload): array
    {
        if (! class_exists(ZipArchive::class)) {
            throw ValidationException::withMessages(['file' => 'ZIP inspection is temporarily unavailable.']);
        }

        $zip = new ZipArchive();
        if ($zip->open($upload->getPathname()) !== true) {
            throw ValidationException::withMessages(['file' => 'The ZIP archive is invalid or unreadable.']);
        }

        $entries = [];
        $uncompressed = 0;
        $maxEntries = max(1, (int) config('pcb.max_archive_entries', 2000));
        $maxUncompressed = max(1, (int) config('pcb.max_archive_uncompressed_mb', 500)) * 1024 * 1024;

        try {
            if ($zip->numFiles < 1 || $zip->numFiles > $maxEntries) {
                throw ValidationException::withMessages(['file' => 'The ZIP archive contains an unsafe number of entries.']);
            }

            for ($index = 0; $index < $zip->numFiles; $index++) {
                $stat = $zip->statIndex($index);
                $name = str_replace('\\', '/', (string) ($stat['name'] ?? ''));

                if ($name === '' || str_starts_with($name, '/') || preg_match('/(^|\/)\.\.($|\/)/', $name) || preg_match('/^[A-Za-z]:\//', $name)) {
                    throw ValidationException::withMessages(['file' => 'The ZIP archive contains an unsafe path.']);
                }

                $size = (int) ($stat['size'] ?? 0);
                $uncompressed += $size;
                if ($uncompressed > $maxUncompressed) {
                    throw ValidationException::withMessages(['file' => 'The ZIP archive expands beyond the configured safety limit.']);
                }

                if (! str_ends_with($name, '/')) {
                    $entries[] = ['name' => mb_substr($name, 0, 300), 'size' => $size];
                }
            }

            $compressed = max(1, (int) $upload->getSize());
            $ratio = $uncompressed / $compressed;
            if ($ratio > max(1, (int) config('pcb.max_archive_ratio', 100))) {
                throw ValidationException::withMessages(['file' => 'The ZIP archive compression ratio exceeds the safety limit.']);
            }

            return [
                'entry_count' => count($entries),
                'uncompressed_bytes' => $uncompressed,
                'compression_ratio' => round($ratio, 2),
                'entries' => $entries,
            ];
        } finally {
            $zip->close();
        }
    }

    private function recordGerberInspection(PcbProject $project, PcbFile $file, User $user, array $entries): void
    {
        $run = PcbGerberAnalysisRun::create([
            'project_id' => $project->id,
            'file_id' => $file->id,
            'triggered_by_id' => $user->id,
            'parser_version' => 'filename-inspector-1.0',
            'status' => 'completed',
            'confidence_level' => 'medium',
            'engineering_reviewed' => false,
        ]);

        $detected = [];
        foreach ($entries as $entry) {
            $type = $this->detectLayerType($entry['name']);
            if (! $type || isset($detected[$entry['name']])) {
                continue;
            }

            $detected[$entry['name']] = $type;
            PcbDetectedLayer::create([
                'analysis_run_id' => $run->id,
                'filename' => $entry['name'],
                'detected_type' => $type,
                'expected_type' => $type === 'inner_copper' ? null : $type,
                'is_matched' => $type !== 'unknown',
                'metadata' => ['method' => 'filename_extension'],
            ]);
        }

        $types = array_values($detected);
        if (! in_array('board_outline', $types, true)) {
            PcbAnalysisWarning::create([
                'analysis_run_id' => $run->id,
                'severity' => 'engineering_review',
                'warning_code' => 'OUTLINE_NOT_DETECTED',
                'message' => 'A board outline was not confidently detected from archive filenames.',
            ]);
        }
    }

    private function detectLayerType(string $filename): ?string
    {
        $name = strtolower($filename);
        $extension = strtolower(pathinfo($name, PATHINFO_EXTENSION));

        return match (true) {
            in_array($extension, ['gtl', 'cmp'], true) => 'top_copper',
            in_array($extension, ['gbl', 'sol'], true) => 'bottom_copper',
            preg_match('/g\d+l$/', $extension) === 1 => 'inner_copper',
            in_array($extension, ['gts', 'stc'], true) => 'top_solder_mask',
            in_array($extension, ['gbs', 'sts'], true) => 'bottom_solder_mask',
            in_array($extension, ['gto', 'plc'], true) => 'top_silkscreen',
            in_array($extension, ['gbo', 'pls'], true) => 'bottom_silkscreen',
            $extension === 'gtp' => 'top_paste',
            $extension === 'gbp' => 'bottom_paste',
            in_array($extension, ['gko', 'gm1', 'gml'], true) || str_contains($name, 'outline') => 'board_outline',
            in_array($extension, ['drl', 'xln'], true) || str_contains($name, 'drill') => 'drill',
            default => null,
        };
    }
}
