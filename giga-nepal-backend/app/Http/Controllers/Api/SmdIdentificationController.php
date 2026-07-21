<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\Smd\SmdIdentificationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class SmdIdentificationController extends Controller
{
    public function __construct(private readonly SmdIdentificationService $smd) {}

    /** Search SMD markings. Public, rate-limited. */
    public function search(Request $request): JsonResponse
    {
        $params = $request->validate([
            'marking' => 'required|string|max:20',
            'package' => 'nullable|string|max:30',
            'pins' => 'nullable|integer|min:1|max:100',
            'manufacturer' => 'nullable|string|max:100',
            'function' => 'nullable|string|max:100',
            'context' => 'nullable|string|max:500',
        ]);

        $results = $this->smd->search($params);
        $this->smd->logSearch($params, count($results), auth()->id());

        return response()->json(['data' => $results]);
    }

    /** Get a specific marking's details. */
    public function show(string $marking): JsonResponse
    {
        $results = $this->smd->search(['marking' => $marking]);

        return response()->json(['data' => [
            'marking' => $marking,
            'candidates' => $results,
        ]]);
    }

    /** List SMD packages. */
    public function packages(): JsonResponse
    {
        $packages = DB::table('smd_packages')
            ->orderBy('canonical_name')
            ->get(['id', 'canonical_name', 'package_family', 'pin_count', 'mounting_type']);

        return response()->json(['data' => $packages]);
    }

    /** Report incorrect match. */
    public function report(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'match_id' => 'required|integer|exists:smd_marking_matches,id',
            'reason' => 'required|string|max:1000',
            'correction' => 'nullable|string|max:500',
        ]);

        // Log report for review
        DB::table('smd_marking_matches')->where('id', $validated['match_id'])
            ->update(['verification_status' => 'reported']);

        return response()->json(['data' => ['status' => 'reported']]);
    }

    // ─── Admin endpoints ───────────────────────────────────────

    /** Admin: verify a match. */
    public function adminVerify(int $id): JsonResponse
    {
        DB::table('smd_marking_matches')->where('id', $id)->update([
            'verification_status' => 'verified',
            'match_confidence' => 100,
            'updated_at' => now(),
        ]);

        return response()->json(['data' => ['status' => 'verified']]);
    }

    /** Admin: reject a match. */
    public function adminReject(int $id): JsonResponse
    {
        DB::table('smd_marking_matches')->where('id', $id)->update([
            'verification_status' => 'rejected',
            'updated_at' => now(),
        ]);

        return response()->json(['data' => ['status' => 'rejected']]);
    }

    /** Admin: link match to existing product. */
    public function adminLinkProduct(int $id, Request $request): JsonResponse
    {
        $validated = $request->validate(['product_id' => 'required|integer|exists:products,id']);

        DB::table('smd_marking_matches')->where('id', $id)->update([
            'product_id' => $validated['product_id'],
            'verification_status' => 'verified',
            'match_confidence' => 100,
            'updated_at' => now(),
        ]);

        return response()->json(['data' => ['status' => 'linked']]);
    }

    /** Admin: trigger SMD import run. */
    public function adminImport(Request $request): JsonResponse
    {
        $id = DB::table('smd_import_runs')->insertGetId([
            'source' => $request->input('source', 'yooneed'),
            'status' => 'pending',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        // Dispatch import in the background
        $args = ['--source' => $request->input('source', 'yooneed')];
        if ($request->has('limit')) {
            $args['--limit'] = (int) $request->input('limit');
        }
        if ($request->has('prefix')) {
            $args['--prefix'] = $request->input('prefix');
        }

        \Illuminate\Support\Facades\Artisan::queue('neogiga:smd-import', $args);

        // Mark as running (the command doesn't update this table yet — ponytail: poll for completion)
        DB::table('smd_import_runs')->where('id', $id)->update([
            'status' => 'running',
            'started_at' => now(),
            'updated_at' => now(),
        ]);

        return response()->json(['data' => ['id' => $id, 'status' => 'running']], 202);
    }

    /** Admin: check import run status. */
    public function adminImportStatus(int $id): JsonResponse
    {
        $run = DB::table('smd_import_runs')->find($id);
        if (! $run) {
            return response()->json(['error' => 'Not found'], 404);
        }

        return response()->json(['data' => $run]);
    }

    /** Admin: get verification queue. */
    public function adminQueue(): JsonResponse
    {
        $queue = DB::table('smd_marking_matches')
            ->join('smd_marking_codes', 'smd_marking_matches.smd_marking_code_id', '=', 'smd_marking_codes.id')
            ->leftJoin('manufacturers', 'smd_marking_matches.manufacturer_id', '=', 'manufacturers.id')
            ->whereIn('smd_marking_matches.verification_status', ['unverified', 'reported'])
            ->select('smd_marking_matches.*', 'smd_marking_codes.display_marking', DB::raw('COALESCE(manufacturers.name, smd_marking_matches.manufacturer_text) as manufacturer_name'))
            ->orderBy('smd_marking_matches.match_confidence', 'desc')
            ->limit(50)
            ->get();

        return response()->json(['data' => $queue]);
    }
}
