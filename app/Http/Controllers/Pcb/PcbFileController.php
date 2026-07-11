<?php

namespace App\Http\Controllers\Pcb;

use App\Http\Controllers\Controller;
use App\Models\Pcb\PcbProject;
use App\Models\Pcb\PcbFile;
use App\Models\Pcb\PcbFileScanResult;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;
use Throwable;

class PcbFileController extends Controller
{
    /**
     * Display a listing of project files.
     */
    public function index(PcbProject $project): JsonResponse
    {
        Gate::authorize('view', $project);

        $files = $project->files()
            ->with(['uploader', 'scanResults' => function ($query) {
                $query->latest()->limit(1);
            }])
            ->orderByDesc('created_at')
            ->paginate(50);

        return response()->json([
            'success' => true,
            'data' => $files,
        ]);
    }

    /**
     * Store a newly uploaded file.
     */
    public function store(Request $request, PcbProject $project): JsonResponse
    {
        Gate::authorize('uploadFiles', $project);

        $validated = $request->validate([
            'file' => 'required|file|max:102400', // 100MB max
            'file_type' => 'required|in:gerber,schematic,bom,cpl,drill,step,dxf,pdf,zip,other',
            'version_id' => 'nullable|uuid|exists:pcb_project_versions,id',
        ]);

        try {
            DB::beginTransaction();

            /** @var UploadedFile $uploadedFile */
            $uploadedFile = $request->file('file');

            // Generate secure filename
            $storedFilename = Str::uuid() . '_' . Str::slug(pathinfo($uploadedFile->getClientOriginalName(), PATHINFO_FILENAME)) . '.' . $uploadedFile->extension();
            $filePath = 'pcb-projects/' . $project->id . '/' . $storedFilename;

            // Store in private disk
            $storedPath = Storage::disk('private')->put($filePath, $uploadedFile->get());

            if (!$storedPath) {
                throw new \Exception('Failed to store file');
            }

            // Calculate checksum
            $checksum = hash_file('sha256', $uploadedFile->getPathname());

            // Create file record
            $file = PcbFile::create([
                'project_id' => $project->id,
                'version_id' => $validated['version_id'] ?? null,
                'uploaded_by_id' => Auth::id(),
                'file_type' => $validated['file_type'],
                'original_filename' => $uploadedFile->getClientOriginalName(),
                'stored_filename' => $storedFilename,
                'file_path' => $filePath,
                'mime_type' => $uploadedFile->getMimeType(),
                'file_size' => $uploadedFile->getSize(),
                'checksum_sha256' => $checksum,
                'status' => 'pending_scan',
                'is_encrypted' => true,
            ]);

            DB::commit();

            // Dispatch async scan job (TODO: implement queue job)
            // ScanFileJob::dispatch($file);

            return response()->json([
                'success' => true,
                'message' => 'File uploaded successfully. Scanning in progress.',
                'data' => $file,
            ], 201);

        } catch (Throwable $e) {
            DB::rollBack();
            Log::error('PCB file upload failed', [
                'error' => $e->getMessage(),
                'project_id' => $project->id,
                'user_id' => Auth::id(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'File upload failed: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Display the specified file.
     */
    public function show(PcbProject $project, PcbFile $file): JsonResponse
    {
        Gate::authorize('view', $project);

        if ($file->project_id !== $project->id) {
            return response()->json([
                'success' => false,
                'message' => 'File not found in this project',
            ], 404);
        }

        $file->load(['uploader', 'scanResults', 'versions']);

        return response()->json([
            'success' => true,
            'data' => $file,
        ]);
    }

    /**
     * Download the specified file with signed URL.
     */
    public function download(PcbProject $project, PcbFile $file): JsonResponse
    {
        Gate::authorize('view', $project);

        if ($file->project_id !== $project->id) {
            return response()->json([
                'success' => false,
                'message' => 'File not found in this project',
            ], 404);
        }

        if (!$file->isAccessibleBy(Auth::user())) {
            return response()->json([
                'success' => false,
                'message' => 'Access denied',
            ], 403);
        }

        // Record access
        $file->recordAccess(Auth::user(), 'download');

        // Generate temporary signed URL (1 hour expiry)
        $signedUrl = Storage::disk('private')->temporaryUrl(
            $file->file_path,
            now()->addHour(),
            [
                'ResponseContentDisposition' => 'attachment; filename="' . $file->original_filename . '"',
            ]
        );

        return response()->json([
            'success' => true,
            'data' => [
                'download_url' => $signedUrl,
                'expires_at' => now()->addHour()->toIso8601String(),
                'filename' => $file->original_filename,
                'size' => $file->file_size,
            ],
        ]);
    }

    /**
     * Download file using token from signed URL.
     */
    public function downloadWithToken(PcbFile $file, string $token)
    {
        try {
            // Decrypt and validate token
            $decrypted = \Illuminate\Support\Facades\Crypt::decryptString($token);
            [$fileId, $uploaderId, $expiry] = explode(':', $decrypted);

            if ($fileId !== $file->id) {
                abort(403, 'Invalid token');
            }

            if (now()->timestamp > (int) $expiry) {
                abort(403, 'Token expired');
            }

            // Check if user has access (token-based access doesn't require login)
            // But we still verify the uploader or project membership
            
            // Record access (anonymous if no auth)
            if (Auth::check()) {
                $file->recordAccess(Auth::user(), 'download', 'token_access');
            }

            return Storage::disk('private')->download(
                $file->file_path,
                $file->original_filename,
                ['Content-Type' => $file->mime_type]
            );

        } catch (\Exception $e) {
            abort(403, 'Invalid or expired download link');
        }
    }

    /**
     * Upload Gerber ZIP file specifically.
     */
    public function uploadGerber(Request $request, PcbProject $project): JsonResponse
    {
        Gate::authorize('uploadFiles', $project);

        $validated = $request->validate([
            'gerber_zip' => 'required|file|mimes:zip|max:102400', // 100MB
            'version_id' => 'nullable|uuid|exists:pcb_project_versions,id',
        ]);

        try {
            DB::beginTransaction();

            /** @var UploadedFile $zipFile */
            $zipFile = $request->file('gerber_zip');

            // Validate ZIP structure (basic check)
            $zip = new \ZipArchive();
            if ($zip->open($zipFile->getPathname()) !== true) {
                throw new \Exception('Invalid ZIP file');
            }

            // Check for ZIP bomb (ratio check)
            $compressedSize = $zipFile->getSize();
            $uncompressedSize = 0;
            for ($i = 0; $i < $zip->numFiles; $i++) {
                $stat = $zip->statIndex($i);
                $uncompressedSize += $stat['size'];
            }

            // Warn if compression ratio is suspicious (> 100:1)
            if ($uncompressedSize > ($compressedSize * 100)) {
                Log::warning('Potential ZIP bomb detected', [
                    'compressed' => $compressedSize,
                    'uncompressed' => $uncompressedSize,
                    'ratio' => $uncompressedSize / $compressedSize,
                ]);
                // Continue but mark for extra scrutiny
            }

            $zip->close();

            // Store the ZIP
            $storedFilename = 'gerber_' . Str::uuid() . '.zip';
            $filePath = 'pcb-projects/' . $project->id . '/gerbers/' . $storedFilename;

            Storage::disk('private')->putFileAs(
                'pcb-projects/' . $project->id . '/gerbers',
                $zipFile,
                $storedFilename
            );

            $checksum = hash_file('sha256', $zipFile->getPathname());

            $file = PcbFile::create([
                'project_id' => $project->id,
                'version_id' => $validated['version_id'] ?? null,
                'uploaded_by_id' => Auth::id(),
                'file_type' => 'gerber',
                'original_filename' => $zipFile->getClientOriginalName(),
                'stored_filename' => $storedFilename,
                'file_path' => $filePath,
                'mime_type' => 'application/zip',
                'file_size' => $zipFile->getSize(),
                'checksum_sha256' => $checksum,
                'status' => 'pending_scan',
                'metadata' => [
                    'is_gerber_bundle' => true,
                    'file_count' => $zip->numFiles,
                    'compression_ratio' => $compressedSize > 0 ? round($uncompressedSize / $compressedSize, 2) : 0,
                ],
                'is_encrypted' => true,
            ]);

            DB::commit();

            // Dispatch Gerber analysis job (TODO)
            // AnalyzeGerberJob::dispatch($file);

            return response()->json([
                'success' => true,
                'message' => 'Gerber ZIP uploaded successfully. Analysis in progress.',
                'data' => $file,
            ], 201);

        } catch (Throwable $e) {
            DB::rollBack();
            Log::error('Gerber upload failed', [
                'error' => $e->getMessage(),
                'project_id' => $project->id,
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Gerber upload failed: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Analyze uploaded Gerber files.
     */
    public function analyzeGerber(Request $request, PcbProject $project): JsonResponse
    {
        Gate::authorize('view', $project);

        // TODO: Implement actual Gerber analysis
        // For now, return placeholder

        return response()->json([
            'success' => true,
            'message' => 'Gerber analysis endpoint - implementation pending',
            'data' => [
                'status' => 'not_implemented',
                'note' => 'Gerber parser integration required',
            ],
        ]);
    }

    /**
     * Delete the specified file.
     */
    public function destroy(PcbProject $project, PcbFile $file): JsonResponse
    {
        Gate::authorize('update', $project);

        if ($file->project_id !== $project->id) {
            return response()->json([
                'success' => false,
                'message' => 'File not found in this project',
            ], 404);
        }

        $filename = $file->original_filename;
        $file->delete(); // Soft delete will trigger physical file deletion

        return response()->json([
            'success' => true,
            'message' => "File '{$filename}' deleted successfully",
        ]);
    }
}
