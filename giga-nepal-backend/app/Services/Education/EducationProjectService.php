<?php

namespace App\Services\Education;

use App\Models\Education\BomLine;
use App\Models\Education\EducationProject;
use App\Models\Education\CodeFile;
use App\Models\Marketplace\Product;
use App\Services\Product\MpnNormalizationService;
use Illuminate\Support\Facades\DB;

class EducationProjectService
{
    public function __construct(
        private MpnNormalizationService $normalization,
    ) {}

    /**
     * Search projects by query, category, skill level, controller.
     */
    public function search(string $query = '', array $filters = [], int $limit = 20, int $offset = 0): array
    {
        $q = EducationProject::published()->with(['bomLines.preferredProduct', 'course']);

        if ($query !== '') {
            $q->where(function ($w) use ($query) {
                $w->where('title', 'like', "%{$query}%")
                  ->orWhere('summary', 'like', "%{$query}%")
                  ->orWhere('category', 'like', "%{$query}%")
                  ->orWhere('main_controller', 'like', "%{$query}%");
            });
        }

        if (!empty($filters['category'])) $q->ofCategory($filters['category']);
        if (!empty($filters['skill_level'])) $q->ofSkillLevel($filters['skill_level']);
        if (!empty($filters['controller'])) $q->ofController($filters['controller']);
        if (!empty($filters['featured'])) $q->featured();

        $total = $q->count();
        $projects = $q->orderByDesc('is_featured')->orderByDesc('view_count')
            ->offset($offset)->limit($limit)->get();

        return ['projects' => $projects, 'total' => $total, 'limit' => $limit, 'offset' => $offset];
    }

    /**
     * Get a single project with full details.
     */
    public function getProject(string $slug): ?EducationProject
    {
        return EducationProject::where('slug', $slug)
            ->with(['bomLines.preferredProduct', 'codeFiles', 'course'])
            ->first();
    }

    /**
     * Get project BOM with live pricing and stock from catalog.
     */
    public function getProjectBomWithLivePricing(int $projectId, ?int $marketplaceId = null): array
    {
        $project = EducationProject::with('bomLines')->findOrFail($projectId);
        $lines = [];

        foreach ($project->bomLines as $line) {
            $product = $line->preferredProduct;
            $price = null;
            $localStock = false;
            $globalStock = false;

            if ($product) {
                $price = $this->getProductPrice($product->id, $marketplaceId);
                $stock = $this->getProductStock($product->id, $marketplaceId);
                $localStock = $stock['local'] > 0;
                $globalStock = $stock['global'] > 0;
            }

            $lines[] = [
                'id' => $line->id,
                'line_no' => $line->line_no,
                'component_role' => $line->component_role,
                'preferred_mpn' => $line->preferred_mpn,
                'preferred_manufacturer' => $line->preferred_manufacturer,
                'quantity' => $line->quantity,
                'is_required' => $line->is_required,
                'product_id' => $product?->id,
                'product_name' => $product?->name,
                'product_mpn' => $product?->mpn,
                'product_sku' => $product?->sku,
                'product_image' => $product?->image_url ?? null,
                'unit_price' => $price,
                'extended_price' => $price ? $price * $line->quantity : null,
                'in_local_stock' => $localStock,
                'in_global_stock' => $globalStock,
                'alternatives' => $this->getAlternativesForLine($line),
            ];
        }

        $totalCost = collect($lines)->whereNotNull('extended_price')->sum('extended_price');
        $requiredLines = collect($lines)->where('is_required', true);
        $requiredCost = $requiredLines->whereNotNull('extended_price')->sum('extended_price');

        return [
            'project' => $project,
            'lines' => $lines,
            'total_lines' => count($lines),
            'required_lines' => $requiredLines->count(),
            'total_cost' => round($totalCost, 2),
            'required_cost' => round($requiredCost, 2),
            'currency' => $project->currency ?? 'USD',
            'coverage_pct' => count($lines) > 0
                ? round((collect($lines)->whereNotNull('product_id')->count() / count($lines)) * 100)
                : 0,
        ];
    }

    /**
     * Get code files for a project.
     */
    public function getProjectCode(int $projectId): array
    {
        return CodeFile::where('education_project_id', $projectId)
            ->orderBy('language')
            ->get()
            ->toArray();
    }

    /**
     * Record a project view.
     */
    public function recordView(int $projectId): void
    {
        EducationProject::where('id', $projectId)->increment('view_count');
    }

    /**
     * Get featured projects for homepage.
     */
    public function getFeatured(int $limit = 12): array
    {
        return EducationProject::published()->featured()
            ->with('course')
            ->orderByDesc('rating_avg')
            ->limit($limit)
            ->get()
            ->toArray();
    }

    /**
     * Get projects by category.
     */
    public function getByCategory(string $category, int $limit = 20): array
    {
        return EducationProject::published()->ofCategory($category)
            ->with('course')
            ->orderByDesc('view_count')
            ->limit($limit)
            ->get()
            ->toArray();
    }

    /**
     * Get categories with project counts.
     */
    public function getCategories(): array
    {
        return EducationProject::published()
            ->select('category', DB::raw('count(*) as project_count'))
            ->groupBy('category')
            ->orderByDesc('project_count')
            ->get()
            ->toArray();
    }

    /**
     * Get product price from marketplace.
     */
    private function getProductPrice(int $productId, ?int $marketplaceId): ?float
    {
        if (!DB::getSchemaBuilder()->hasTable('marketplace_product_prices')) return null;

        $query = DB::table('marketplace_product_prices')
            ->where('product_id', $productId)
            ->where(function ($q) {
                $q->whereNull('starts_at')->orWhere('starts_at', '<=', now());
            })
            ->where(function ($q) {
                $q->whereNull('ends_at')->orWhere('ends_at', '>=', now());
            });

        if ($marketplaceId) $query->where('marketplace_id', $marketplaceId);

        return $query->orderByDesc('id')->value('price');
    }

    /**
     * Get product stock info.
     */
    private function getProductStock(int $productId, ?int $marketplaceId): array
    {
        $local = 0; $global = 0;

        if (DB::getSchemaBuilder()->hasTable('marketplace_product_prices')) {
            $global = (int) DB::table('marketplace_product_prices')
                ->where('product_id', $productId)->sum('stock_quantity');
        }

        return ['local' => $local, 'global' => $global];
    }

    /**
     * Get alternatives for a BOM line.
     */
    private function getAlternativesForLine(BomLine $line): array
    {
        if (empty($line->alternative_product_ids)) return [];

        return Product::whereIn('id', $line->alternative_product_ids)
            ->published()
            ->select('id', 'name', 'mpn', 'sku', 'slug')
            ->limit(5)
            ->get()
            ->toArray();
    }
}
