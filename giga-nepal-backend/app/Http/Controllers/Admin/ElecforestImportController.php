<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\CatalogImport\Elecforest\ElecforestImporter;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class ElecforestImportController extends Controller
{
    public function index(Request $request, ElecforestImporter $importer): View
    {
        $sourceId = (int) (DB::table('catalog_sources')->where('code', 'elecforest')->value('id') ?: 0);
        $filters = [
            'q' => trim((string) $request->query('q', '')),
            'review_status' => trim((string) $request->query('review_status', '')),
            'category_status' => trim((string) $request->query('category_status', '')),
        ];
        $products = DB::table('supplier_products as sp')
            ->join('products as p', 'p.id', '=', 'sp.product_id')
            ->leftJoin('product_categories as c', 'c.id', '=', 'p.category_id')
            ->leftJoin('product_brands as b', 'b.id', '=', 'p.brand_id')
            ->leftJoin('manufacturers as m', 'm.id', '=', 'p.manufacturer_id')
            ->where('sp.catalog_source_id', $sourceId)
            ->when($filters['q'] !== '', function ($query) use ($filters): void {
                $like = '%'.$filters['q'].'%';
                $query->where(fn ($nested) => $nested->where('p.name', 'like', $like)->orWhere('p.sku', 'like', $like)
                    ->orWhere('sp.supplier_sku', 'like', $like)->orWhere('sp.source_url', 'like', $like));
            })
            ->when($filters['review_status'] !== '', fn ($query) => $query->where('sp.review_status', $filters['review_status']))
            ->select([
                'sp.id as supplier_product_id', 'sp.source_product_id', 'sp.supplier_sku', 'sp.source_price', 'sp.source_currency',
                'sp.source_stock_state', 'sp.review_status', 'sp.data_quality_score', 'sp.source_url', 'sp.last_seen_at',
                'p.id as product_id', 'p.name', 'p.sku', 'p.slug', 'p.status', 'p.visibility_status',
                'c.name as category_name', 'b.name as brand_name', 'm.name as manufacturer_name',
            ])->latest('sp.id')->paginate(50)->withQueryString();

        $supplierIds = DB::table('supplier_products')->where('catalog_source_id', $sourceId)->pluck('id');
        $productIds = DB::table('supplier_products')->where('catalog_source_id', $sourceId)->whereNotNull('product_id')->pluck('product_id')->unique();

        $stats = [
            'source_records' => DB::table('supplier_products')->where('catalog_source_id', $sourceId)->count(),
            'drafts' => DB::table('supplier_products as sp')->join('products as p', 'p.id', '=', 'sp.product_id')->where('sp.catalog_source_id', $sourceId)->where('p.status', 'draft')->count(),
            'open_reviews' => DB::table('catalog_review_tasks')->where('catalog_source_id', $sourceId)->where('status', 'open')->count(),
            'failed_rows' => DB::table('catalog_import_failures as f')->join('catalog_import_runs as r', 'r.id', '=', 'f.catalog_import_run_id')->where('r.catalog_source_id', $sourceId)->where('f.retry_status', '!=', 'resolved')->count(),
            'media_downloaded' => DB::table('supplier_product_assets as a')->join('supplier_products as sp', 'sp.id', '=', 'a.supplier_product_id')->where('sp.catalog_source_id', $sourceId)->where('a.download_status', 'downloaded')->count(),
            'media_pending' => DB::table('supplier_product_assets as a')->join('supplier_products as sp', 'sp.id', '=', 'a.supplier_product_id')->where('sp.catalog_source_id', $sourceId)->where('a.rights_status', 'pending_review')->count(),
            'duplicate_candidates' => DB::table('product_identifiers')->whereIn('product_id', $productIds)->where('confidence_level', 'ambiguous_source_value')->distinct()->count('product_id'),
            'unresolved_categories' => DB::table('product_category_assignments')->where('catalog_source_id', $sourceId)->where('mapping_status', '!=', 'auto_mapped')->count(),
            'missing_brand' => DB::table('products')->whereIn('id', $productIds)->whereNull('brand_id')->count(),
            'missing_manufacturer' => DB::table('products')->whereIn('id', $productIds)->whereNull('manufacturer_id')->count(),
            'missing_mpn' => DB::table('products')->whereIn('id', $productIds)->where(fn ($query) => $query->whereNull('mpn')->orWhere('mpn', ''))->count(),
            'missing_images' => DB::table('products')->whereIn('id', $productIds)->whereNotExists(fn ($query) => $query->selectRaw('1')->from('product_images')->whereColumn('product_images.product_id', 'products.id'))->count(),
            'failed_images' => DB::table('supplier_product_assets')->whereIn('supplier_product_id', $supplierIds)->where('download_status', 'failed')->count(),
            'missing_descriptions' => DB::table('products')->whereIn('id', $productIds)->where(fn ($query) => $query->whereNull('description')->orWhere('description', ''))->count(),
            'missing_seo' => DB::table('products')->whereIn('id', $productIds)->whereNotExists(fn ($query) => $query->selectRaw('1')->from('product_seo_meta')->whereColumn('product_seo_meta.product_id', 'products.id'))->count(),
            'ready_to_publish' => DB::table('products')->whereIn('id', $productIds)->whereNotNull('manufacturer_id')->whereNotNull('category_id')
                ->whereNotNull('description')->whereNotNull('short_description')
                ->whereExists(fn ($query) => $query->selectRaw('1')->from('product_images')->whereColumn('product_images.product_id', 'products.id')->where('is_active', true))
                ->whereExists(fn ($query) => $query->selectRaw('1')->from('product_seo_meta')->whereColumn('product_seo_meta.product_id', 'products.id')->whereNotNull('meta_description'))
                ->whereNotExists(fn ($query) => $query->selectRaw('1')->from('catalog_review_tasks')->whereColumn('catalog_review_tasks.product_id', 'products.id')->where('status', 'open'))->count(),
            'requiring_review' => 0,
        ];
        $stats['requiring_review'] = max(0, $productIds->count() - $stats['ready_to_publish']);
        $runs = DB::table('catalog_import_runs')->where('catalog_source_id', $sourceId)->latest('created_at')->limit(20)->get();
        $failures = DB::table('catalog_import_failures as f')->join('catalog_import_runs as r', 'r.id', '=', 'f.catalog_import_run_id')
            ->where('r.catalog_source_id', $sourceId)->select(['f.*'])->latest('f.id')->limit(30)->get();
        $mappings = DB::table('supplier_category_mappings as scm')->leftJoin('product_categories as c', 'c.id', '=', 'scm.category_id')
            ->where('scm.catalog_source_id', $sourceId)->select(['scm.*', 'c.name as category_name'])->orderBy('scm.source_category_path')->get();

        return view('admin.elecforest-imports', [
            'stats' => $stats, 'products' => $products, 'runs' => $runs, 'failures' => $failures,
            'mappings' => $mappings, 'filters' => $filters, 'validation' => $importer->validateImported(),
        ]);
    }

    public function start(Request $request, ElecforestImporter $importer): RedirectResponse
    {
        $data = $request->validate([
            'limit' => ['nullable', 'integer', 'min:0', 'max:3178'],
            'start_line' => ['nullable', 'integer', 'min:1', 'max:3178'],
            'mode' => ['required', 'in:dry_run,queue'],
            'download_images' => ['nullable', 'boolean'],
        ]);
        $options = [
            'limit' => (int) ($data['limit'] ?? 0), 'start_line' => (int) ($data['start_line'] ?? 1),
            'dry_run' => $data['mode'] === 'dry_run', 'queue' => $data['mode'] === 'queue',
            'download_images' => (bool) ($data['download_images'] ?? false), 'generate_seo' => true,
            'skip_inventory' => true, 'draft_all' => true,
        ];
        $result = $data['mode'] === 'queue'
            ? $importer->queueFile((string) config('elecforest_import.default_file'), $options)
            : $importer->importFile((string) config('elecforest_import.default_file'), $options);

        return back()->with('success', 'ElecForest operation: '.json_encode($result, JSON_UNESCAPED_SLASHES));
    }

    public function retry(string $run, ElecforestImporter $importer): RedirectResponse
    {
        $result = $importer->retryFailures($run);
        return back()->with('success', 'Retry completed: '.json_encode($result));
    }

    public function pause(string $run): RedirectResponse
    {
        DB::table('catalog_import_runs')->where('id', $run)->whereIn('status', ['running', 'queued'])->update(['status' => 'paused', 'updated_at' => now()]);
        return back()->with('success', "ElecForest run {$run} paused. Queued jobs will remain available for resume.");
    }

    public function resume(string $run, ElecforestImporter $importer): RedirectResponse
    {
        $status = DB::table('catalog_import_runs')->where('id', $run)->value('status');
        if ($status === 'paused') {
            DB::table('catalog_import_runs')->where('id', $run)->update(['status' => 'queued', 'completed_at' => null, 'updated_at' => now()]);
            return back()->with('success', "ElecForest run {$run} resumed; existing queued jobs were preserved.");
        }

        return back()->with('success', 'Resume queued: '.json_encode($importer->resume($run, ['queue' => true])));
    }

    public function generateSeo(Request $request, ElecforestImporter $importer): RedirectResponse
    {
        $data = $request->validate(['limit' => ['nullable', 'integer', 'min:0', 'max:3178']]);
        return back()->with('success', 'SEO generated: '.json_encode($importer->generateSeoForImported((int) ($data['limit'] ?? 0))));
    }

    public function downloadImages(Request $request, ElecforestImporter $importer): RedirectResponse
    {
        $data = $request->validate(['limit' => ['nullable', 'integer', 'min:0', 'max:3178'], 'retry_failed' => ['nullable', 'boolean']]);
        $result = $importer->downloadImages((int) ($data['limit'] ?? 0), false, (bool) ($data['retry_failed'] ?? false));
        return back()->with('success', 'Image jobs queued: '.json_encode($result));
    }

    public function publish(ElecforestImporter $importer): RedirectResponse
    {
        return back()->with('success', 'Qualified publication: '.json_encode($importer->publishQualified(false)));
    }

    public function mapCategory(Request $request, ElecforestImporter $importer): RedirectResponse
    {
        $data = $request->validate([
            'source_category' => ['required', 'string', 'max:500'],
            'neo_category' => ['required', 'string', 'max:250'],
        ]);
        $parts = preg_split('/\s*(?:\/|>|\|)\s*/', $data['source_category'], 2) ?: [];
        $result = $importer->mapCategory(trim($parts[0] ?? ''), trim($parts[1] ?? ''), $data['neo_category']);
        return back()->with('success', 'Category mapped: '.json_encode($result));
    }
}
