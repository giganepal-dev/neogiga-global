<?php

namespace App\Http\Controllers\Api\Bom;

use App\Http\Controllers\Controller;
use App\Models\Bom\BomImport;
use App\Models\Bom\BomImportLine;
use App\Services\Bom\BomImportService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use RuntimeException;

/**
 * Customer BOM procurement import (auth: api.token).
 *
 * Upload a parts list (pasted text or a CSV file), review the catalog matches,
 * override matches manually, then convert to an RFQ. Every read/write is scoped
 * to the authenticated owner.
 */
class BomImportController extends Controller
{
    public function __construct(private readonly BomImportService $service)
    {
    }

    /** GET /api/v1/bom/imports */
    public function index(Request $request): JsonResponse
    {
        $imports = BomImport::where('user_id', $request->user()->id)
            ->orderByDesc('id')
            ->paginate(20);

        return response()->json(['success' => true, 'data' => $imports]);
    }

    /** POST /api/v1/bom/imports  (content paste OR file upload) */
    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:160'],
            'content' => ['required_without:file', 'nullable', 'string', 'max:1000000'],
            'file' => ['required_without:content', 'nullable', 'file', 'mimes:csv,txt,tsv', 'max:5120'],
            'currency' => ['nullable', 'string', 'size:3'],
        ]);

        [$content, $format] = $this->resolveContent($request, $data);

        try {
            $import = $this->service->createFromContent(
                $request->user()->id,
                $data['name'],
                $content,
                $format,
                strtoupper($data['currency'] ?? 'USD'),
            );
        } catch (RuntimeException $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 422);
        }

        return response()->json(['success' => true, 'data' => $import->load('lines')], 201);
    }

    /** GET /api/v1/bom/imports/{import} */
    public function show(Request $request, BomImport $import): JsonResponse
    {
        $this->authorizeOwner($request, $import);

        return response()->json(['success' => true, 'data' => $import->load('lines')]);
    }

    /** POST /api/v1/bom/imports/{import}/rematch */
    public function rematch(Request $request, BomImport $import): JsonResponse
    {
        $this->authorizeOwner($request, $import);

        return response()->json(['success' => true, 'data' => $this->service->rematch($import)->load('lines')]);
    }

    /** PATCH /api/v1/bom/imports/{import}/lines/{line} */
    public function updateLine(Request $request, BomImport $import, BomImportLine $line): JsonResponse
    {
        $this->authorizeOwner($request, $import);

        if ((int) $line->bom_import_id !== (int) $import->id) {
            return response()->json(['success' => false, 'message' => 'Not found.'], 404);
        }

        $data = $request->validate([
            'matched_product_id' => ['present', 'nullable', 'integer'],
        ]);

        try {
            $line = $this->service->setLineMatch($line, $data['matched_product_id']);
        } catch (RuntimeException $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 422);
        }

        return response()->json(['success' => true, 'data' => $line]);
    }

    /** POST /api/v1/bom/imports/{import}/convert-to-rfq */
    public function convertToRfq(Request $request, BomImport $import): JsonResponse
    {
        $this->authorizeOwner($request, $import);

        if ($import->status === 'converted' && $import->rfq_request_id) {
            return response()->json([
                'success' => false,
                'message' => 'This BOM has already been converted to an RFQ.',
                'rfq_request_id' => $import->rfq_request_id,
            ], 409);
        }

        $data = $request->validate([
            'company_name' => ['nullable', 'string', 'max:160'],
            'contact_name' => ['nullable', 'string', 'max:120'],
            'contact_email' => ['nullable', 'email', 'max:190'],
            'contact_phone' => ['nullable', 'string', 'max:40'],
            'marketplace_id' => ['nullable', 'integer'],
            'notes' => ['nullable', 'string', 'max:2000'],
        ]);

        try {
            $rfq = $this->service->convertToRfq($import, $data);
        } catch (RuntimeException $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 422);
        }

        return response()->json(['success' => true, 'data' => $rfq->load('items')], 201);
    }

    /** @return array{0:string,1:string} [content, source_format] */
    private function resolveContent(Request $request, array $data): array
    {
        if ($request->hasFile('file')) {
            $file = $request->file('file');
            $content = (string) file_get_contents($file->getRealPath());

            return [$content, 'csv'];
        }

        return [(string) ($data['content'] ?? ''), 'paste'];
    }

    private function authorizeOwner(Request $request, BomImport $import): void
    {
        if ((int) $import->user_id !== (int) $request->user()->id) {
            abort(404, 'Not found.');
        }
    }
}
