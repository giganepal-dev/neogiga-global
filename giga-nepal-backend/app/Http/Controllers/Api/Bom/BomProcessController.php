<?php

namespace App\Http\Controllers\Api\Bom;

use App\Http\Controllers\Controller;
use App\Jobs\ProcessBomJob;
use App\Models\Bom\BomComment;
use App\Models\Bom\BomCollaborator;
use App\Models\Bom\BomImport;
use App\Models\Bom\BomImportLine;
use App\Services\Bom\BomProcessingService;
use App\Services\Bom\BomRfqService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class BomProcessController extends Controller
{
    public function __construct(
        private BomRfqService $rfqService,
        private BomProcessingService $processingService,
    ) {}

    /**
     * Upload and process a BOM file.
     *
     * POST /api/v1/bom/upload
     */
    public function upload(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'file' => ['required', 'file', 'max:10240'], // 10MB max
            'name' => ['nullable', 'string', 'max:255'],
            'currency' => ['nullable', 'string', 'size:3'],
            'merge_duplicates' => ['nullable', 'boolean'],
        ]);

        $file = $request->file('file');
        $content = file_get_contents($file->getRealPath());

        if ($content === false) {
            return response()->json([
                'success' => false,
                'message' => 'Could not read uploaded file.',
            ], 422);
        }

        try {
            $result = $this->processingService->processText(
                $content,
                $request->user()->id,
                [
                    'name' => $validated['name'] ?? $file->getClientOriginalName(),
                    'filename' => $file->getClientOriginalName(),
                    'currency' => $validated['currency'] ?? 'USD',
                    'merge_duplicates' => $validated['merge_duplicates'] ?? true,
                ],
            );

            return response()->json([
                'success' => true,
                'data' => $result,
            ], 201);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to process BOM: ' . $e->getMessage(),
            ], 422);
        }
    }

    /**
     * Process BOM from pasted text.
     *
     * POST /api/v1/bom/process-text
     */
    public function processText(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'content' => ['required', 'string', 'max:500000'],
            'name' => ['nullable', 'string', 'max:255'],
            'currency' => ['nullable', 'string', 'size:3'],
            'merge_duplicates' => ['nullable', 'boolean'],
        ]);

        try {
            $result = $this->processingService->processText(
                $validated['content'],
                $request->user()->id,
                [
                    'name' => $validated['name'] ?? 'Pasted BOM ' . now()->format('Y-m-d H:i'),
                    'currency' => $validated['currency'] ?? 'USD',
                    'merge_duplicates' => $validated['merge_duplicates'] ?? true,
                ],
            );

            return response()->json([
                'success' => true,
                'data' => $result,
            ], 201);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to process BOM: ' . $e->getMessage(),
            ], 422);
        }
    }

    /**
     * Process a BOM import via queue (for large files).
     *
     * POST /api/v1/bom/process
     */
    public function process(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'bom_import_id' => ['required', 'integer', 'exists:bom_imports,id'],
            'chunk_size' => ['nullable', 'integer', 'min:10', 'max:500'],
        ]);

        $bomImport = BomImport::find($validated['bom_import_id']);

        if ($bomImport->user_id !== $request->user()->id) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized.',
            ], 403);
        }

        $job = ProcessBomJob::dispatch($bomImport->id, [
            'chunk_size' => $validated['chunk_size'] ?? 100,
        ]);

        return response()->json([
            'success' => true,
            'data' => [
                'import_id' => $bomImport->id,
                'job_id' => $job->getJobId(),
                'status' => 'queued',
                'message' => 'BOM processing has been queued.',
            ],
        ], 202);
    }

    /**
     * Get BOM processing status.
     *
     * GET /api/v1/bom/{import}/status
     */
    public function status(Request $request, int $import): JsonResponse
    {
        $bomImport = BomImport::find($import);

        if (! $bomImport) {
            return response()->json([
                'success' => false,
                'message' => 'BOM import not found.',
            ], 404);
        }

        if ($bomImport->user_id !== $request->user()->id) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized.',
            ], 403);
        }

        return response()->json([
            'success' => true,
            'data' => [
                'import_id' => $bomImport->id,
                'name' => $bomImport->name,
                'status' => $bomImport->status,
                'total_lines' => $bomImport->total_lines,
                'matched_lines' => $bomImport->matched_lines,
                'unmatched_lines' => $bomImport->unmatched_lines,
                'progress' => $bomImport->metadata['progress'] ?? null,
                'error' => $bomImport->error_message,
                'completed_at' => $bomImport->processed_at,
                'created_at' => $bomImport->created_at,
            ],
        ]);
    }

    /**
     * Get detailed BOM results.
     *
     * GET /api/v1/bom/{import}/results
     */
    public function results(Request $request, int $import): JsonResponse
    {
        $bomImport = BomImport::find($import);

        if (! $bomImport) {
            return response()->json([
                'success' => false,
                'message' => 'BOM import not found.',
            ], 404);
        }

        if ($bomImport->user_id !== $request->user()->id) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized.',
            ], 403);
        }

        $results = $this->processingService->getResults($import);

        return response()->json([
            'success' => true,
            'data' => $results,
        ]);
    }

    /**
     * Approve or change a match for a specific line.
     *
     * POST /api/v1/bom/{import}/lines/{line}/approve
     */
    public function approveMatch(Request $request, int $import, int $line): JsonResponse
    {
        $validated = $request->validate([
            'product_id' => ['required', 'integer', 'exists:products,id'],
            'notes' => ['nullable', 'string', 'max:1000'],
        ]);

        $bomImport = BomImport::find($import);

        if (! $bomImport || $bomImport->user_id !== $request->user()->id) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized.',
            ], 403);
        }

        try {
            $result = $this->processingService->approveMatch(
                $import,
                $line,
                $validated['product_id'],
                $validated['notes'] ?? null,
            );

            return response()->json([
                'success' => true,
                'data' => $result,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 422);
        }
    }

    /**
     * Get RFQ-ready lines from BOM.
     *
     * GET /api/v1/bom/{import}/rfq-ready
     */
    public function rfqReady(Request $request, int $import): JsonResponse
    {
        $bomImport = BomImport::find($import);

        if (! $bomImport || $bomImport->user_id !== $request->user()->id) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized.',
            ], 403);
        }

        $lines = $this->processingService->getRfqReadyLines($import);

        return response()->json([
            'success' => true,
            'data' => [
                'lines' => $lines,
                'count' => count($lines),
            ],
        ]);
    }

    /**
     * Get cart-ready lines from BOM.
     *
     * GET /api/v1/bom/{import}/cart-ready
     */
    public function cartReady(Request $request, int $import): JsonResponse
    {
        $bomImport = BomImport::find($import);

        if (! $bomImport || $bomImport->user_id !== $request->user()->id) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized.',
            ], 403);
        }

        $lines = $this->processingService->getCartReadyLines($import);

        return response()->json([
            'success' => true,
            'data' => [
                'lines' => $lines,
                'count' => count($lines),
            ],
        ]);
    }

    /**
     * Create RFQ from BOM lines.
     *
     * POST /api/v1/bom/{import}/create-rfq
     */
    public function createRfq(Request $request, int $import): JsonResponse
    {
        $validated = $request->validate([
            'lines' => ['nullable', 'array'],
            'lines.*' => ['integer'],
            'title' => ['nullable', 'string', 'max:255'],
            'notes' => ['nullable', 'string', 'max:2000'],
            'currency' => ['nullable', 'string', 'size:3'],
        ]);

        $bomImport = BomImport::find($import);

        if (! $bomImport || $bomImport->user_id !== $request->user()->id) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized.',
            ], 403);
        }

        try {
            $result = $this->rfqService->createRfqFromBom(
                $import,
                $request->user()->id,
                [
                    'lines' => $validated['lines'] ?? null,
                    'title' => $validated['title'] ?? null,
                    'notes' => $validated['notes'] ?? null,
                    'currency' => $validated['currency'] ?? null,
                ],
            );

            return response()->json([
                'success' => true,
                'data' => $result,
            ], 201);
        } catch (\RuntimeException $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 422);
        }
    }

    /**
     * List user's BOM imports.
     *
     * GET /api/v1/bom/imports
     */
    public function listImports(Request $request): JsonResponse
    {
        $imports = BomImport::where('user_id', $request->user()->id)
            ->orderByDesc('created_at')
            ->paginate(20);

        return response()->json([
            'success' => true,
            'data' => $imports,
        ]);
    }

    /**
     * Delete a BOM import.
     *
     * DELETE /api/v1/bom/{import}
     */
    public function destroy(Request $request, int $import): JsonResponse
    {
        $bomImport = BomImport::find($import);

        if (! $bomImport || $bomImport->user_id !== $request->user()->id) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized.',
            ], 403);
        }

        $bomImport->delete();

        return response()->json([
            'success' => true,
            'message' => 'BOM import deleted.',
        ]);
    }

    /**
     * Add a comment to a BOM import.
     *
     * POST /api/v1/bom/{import}/comments
     */
    public function addComment(Request $request, int $import): JsonResponse
    {
        $validated = $request->validate([
            'comment' => ['required', 'string', 'max:2000'],
            'line_no' => ['nullable', 'integer'],
            'comment_type' => ['nullable', 'string', 'in:general,review,question,warning'],
            'is_internal' => ['nullable', 'boolean'],
        ]);

        $bomImport = BomImport::find($import);

        if (! $bomImport || $bomImport->user_id !== $request->user()->id) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized.',
            ], 403);
        }

        $lineId = null;
        if (! empty($validated['line_no'])) {
            $line = BomImportLine::where('bom_import_id', $import)
                ->where('line_no', $validated['line_no'])
                ->first();
            $lineId = $line?->id;
        }

        $comment = BomComment::create([
            'bom_import_id' => $import,
            'bom_import_line_id' => $lineId,
            'user_id' => $request->user()->id,
            'comment' => $validated['comment'],
            'comment_type' => $validated['comment_type'] ?? 'general',
            'is_internal' => $validated['is_internal'] ?? false,
        ]);

        return response()->json([
            'success' => true,
            'data' => $comment,
        ], 201);
    }

    /**
     * Get comments for a BOM import.
     *
     * GET /api/v1/bom/{import}/comments
     */
    public function getComments(Request $request, int $import): JsonResponse
    {
        $bomImport = BomImport::find($import);

        if (! $bomImport || $bomImport->user_id !== $request->user()->id) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized.',
            ], 403);
        }

        $comments = BomComment::where('bom_import_id', $import)
            ->with('user:id,name,email')
            ->orderByDesc('created_at')
            ->get();

        return response()->json([
            'success' => true,
            'data' => $comments,
        ]);
    }

    /**
     * Add a collaborator to a BOM import.
     *
     * POST /api/v1/bom/{import}/collaborators
     */
    public function addCollaborator(Request $request, int $import): JsonResponse
    {
        $validated = $request->validate([
            'email' => ['required', 'email', 'exists:users,email'],
            'role' => ['required', 'string', 'in:editor,reviewer,viewer,procurement_approver'],
        ]);

        $bomImport = BomImport::find($import);

        if (! $bomImport || $bomImport->user_id !== $request->user()->id) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized.',
            ], 403);
        }

        $userId = \App\Models\User::where('email', $validated['email'])->value('id');

        if ($userId === $request->user()->id) {
            return response()->json([
                'success' => false,
                'message' => 'You cannot add yourself as a collaborator.',
            ], 422);
        }

        $collaborator = BomCollaborator::updateOrCreate(
            ['bom_import_id' => $import, 'user_id' => $userId],
            [
                'role' => $validated['role'],
                'status' => 'pending',
                'invited_at' => now(),
            ]
        );

        return response()->json([
            'success' => true,
            'data' => $collaborator,
        ], 201);
    }

    /**
     * Get collaborators for a BOM import.
     *
     * GET /api/v1/bom/{import}/collaborators
     */
    public function getCollaborators(Request $request, int $import): JsonResponse
    {
        $bomImport = BomImport::find($import);

        if (! $bomImport || $bomImport->user_id !== $request->user()->id) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized.',
            ], 403);
        }

        $collaborators = BomCollaborator::where('bom_import_id', $import)
            ->with('user:id,name,email')
            ->get();

        return response()->json([
            'success' => true,
            'data' => $collaborators,
        ]);
    }
}
