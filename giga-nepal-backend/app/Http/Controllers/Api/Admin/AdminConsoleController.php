<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Concerns\ApiResponses;
use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class AdminConsoleController extends Controller
{
    use ApiResponses;

    public function overview(): JsonResponse
    {
        return $this->success([
            'metrics' => $this->metrics(),
            'pending' => [
                'vendor_approvals' => $this->countWhere('vendor_marketplace_approvals', 'status', 'pending'),
                'vendor_documents' => $this->countWhere('vendor_documents', 'status', 'pending'),
                'product_approvals' => $this->countWhere('product_approval_status', 'status', 'pending'),
                'low_stock_rows' => $this->lowStockCount(),
            ],
            'recent_activity' => $this->recentActivity(),
            'source_notes' => 'Counts are read from NeoGiga production tables; no external dataset was imported.',
            'confidence_level' => 'high',
            'last_updated' => now()->toIso8601String(),
            'disclaimer' => 'Advisory only',
        ]);
    }

    public function navigation(): JsonResponse
    {
        return $this->success([
            ['group' => 'Overview', 'items' => [
                ['label' => 'Dashboard', 'url' => '/admin'],
                ['label' => 'System Health & APIs', 'url' => '/admin/system-health'],
                ['label' => 'Settings', 'url' => '/admin/settings'],
                ['label' => 'Media', 'url' => '/admin/media'],
                ['label' => 'SEO', 'url' => '/admin/seo'],
            ]],
            ['group' => 'Catalog', 'items' => [
                ['label' => 'Categories', 'url' => '/admin/categories'],
                ['label' => 'Products', 'url' => '/admin/products'],
                ['label' => 'Marketplaces', 'url' => '/admin/marketplaces'],
                ['label' => 'Import Review', 'url' => '/admin/imports/jlcpcb'],
                ['label' => 'ElecForest Imports', 'url' => '/admin/imports/elecforest'],
            ]],
            ['group' => 'Operations', 'items' => [
                ['label' => 'Vendors', 'url' => '/admin/vendors'],
                ['label' => 'Inventory', 'url' => '/admin/inventory'],
                ['label' => 'Orders', 'url' => '/admin/orders'],
                ['label' => 'RFQs', 'url' => '/admin/rfqs'],
                ['label' => 'POS', 'url' => '/admin/pos'],
                ['label' => 'LMS', 'url' => '/admin/lms'],
            ]],
            ['group' => 'Growth', 'items' => [
                ['label' => 'Marketing', 'url' => '/admin/marketing'],
                ['label' => 'CRM & Segments', 'url' => '/admin/marketing/crm'],
                ['label' => 'Customer Imports', 'url' => '/admin/marketing/customer-imports'],
                ['label' => 'Email Campaigns', 'url' => '/admin/marketing/email'],
                ['label' => 'Email Credentials', 'url' => '/admin/marketing/settings'],
                ['label' => 'Analytics', 'url' => '/admin/marketing/analytics'],
                ['label' => 'Audit Log', 'url' => '/admin/marketing/audit'],
            ]],
        ]);
    }

    public function settings(): JsonResponse
    {
        return $this->success([
            'admin_settings' => $this->table('admin_settings')->orderBy('group')->orderBy('key')->get(),
            'marketplace_settings' => $this->table('marketplace_settings')->orderBy('group')->orderBy('key')->limit(200)->get(),
            'marketplaces' => $this->table('marketplaces')->orderBy('id')->get(),
            'countries' => $this->table('countries')->where('is_active', true)->orderBy('name')->get(),
            'currencies' => $this->table('currencies')->where('is_active', true)->orderBy('code')->get(),
        ]);
    }

    public function storeSetting(Request $request): JsonResponse
    {
        $data = $request->validate([
            'group' => ['nullable', 'string', 'max:80'],
            'key' => ['required', 'string', 'max:160', 'regex:/^[a-z0-9_.-]+$/i'],
            'value' => ['nullable'],
            'type' => ['nullable', 'string', 'max:40'],
            'is_public' => ['nullable', 'boolean'],
            'description' => ['nullable', 'string', 'max:1000'],
            'metadata' => ['nullable', 'array'],
        ]);

        $value = $data['value'] ?? null;
        if (is_array($value)) {
            $value = json_encode($value);
        }

        $row = [
            'group' => $data['group'] ?? 'general',
            'key' => $data['key'],
            'value' => $value,
            'type' => $data['type'] ?? 'string',
            'is_public' => (bool) ($data['is_public'] ?? false),
            'description' => $data['description'] ?? null,
            'metadata' => json_encode($data['metadata'] ?? []),
            'updated_at' => now(),
        ];

        DB::table('admin_settings')->updateOrInsert(
            ['key' => $data['key']],
            $row + ['created_at' => now()]
        );

        $this->audit('admin_setting_saved', 'admin_settings', null, null, $row + ['setting_key' => $data['key']]);

        return $this->success(DB::table('admin_settings')->where('key', $data['key'])->first(), 201);
    }

    public function media(Request $request): JsonResponse
    {
        $assets = $this->table('admin_media_assets')
            ->when($request->query('folder'), fn ($q, $folder) => $q->where('folder', $folder))
            ->orderByDesc('id')
            ->paginate((int) $request->query('per_page', 24));

        return $this->success($assets);
    }

    public function storeMedia(Request $request): JsonResponse
    {
        $data = $request->validate([
            'file' => ['required', 'file', 'max:5120', 'mimes:jpg,jpeg,png,webp,gif,svg,pdf,csv,txt'],
            'folder' => ['nullable', 'string', 'max:120'],
            'title' => ['nullable', 'string', 'max:180'],
            'alt_text' => ['nullable', 'string', 'max:500'],
            'metadata' => ['nullable', 'array'],
        ]);

        $file = $data['file'];
        $folder = trim($data['folder'] ?? 'general', '/');
        $name = Str::uuid().'.'.$file->getClientOriginalExtension();
        $path = $file->storeAs('admin-media/'.$folder, $name, 'public');

        $id = DB::table('admin_media_assets')->insertGetId([
            'disk' => 'public',
            'path' => $path,
            'original_name' => $file->getClientOriginalName(),
            'mime_type' => $file->getClientMimeType(),
            'size' => $file->getSize(),
            'folder' => $folder,
            'title' => $data['title'] ?? null,
            'alt_text' => $data['alt_text'] ?? null,
            'uploaded_by' => $request->user()?->id,
            'metadata' => json_encode($data['metadata'] ?? []),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $asset = DB::table('admin_media_assets')->where('id', $id)->first();
        $this->audit('admin_media_uploaded', 'admin_media_assets', $id, null, (array) $asset);

        return $this->success($asset, 201, ['url' => Storage::disk('public')->url($path)]);
    }

    public function seo(): JsonResponse
    {
        return $this->success([
            'pages' => $this->table('seo_pages')->orderBy('url_path')->paginate(50),
            'redirects' => $this->table('seo_redirects')->orderByDesc('id')->limit(100)->get(),
            'product_meta_count' => $this->safeCount('product_seo_meta'),
            'sitemap_url' => url('/sitemap.xml'),
        ]);
    }

    public function storeSeoPage(Request $request): JsonResponse
    {
        $data = $request->validate([
            'url_path' => ['required', 'string', 'max:255'],
            'route_name' => ['nullable', 'string', 'max:120'],
            'title' => ['nullable', 'string', 'max:255'],
            'meta_description' => ['nullable', 'string', 'max:1000'],
            'canonical_url' => ['nullable', 'string', 'max:500'],
            'robots' => ['nullable', 'string', 'max:80'],
            'og_image' => ['nullable', 'string', 'max:500'],
            'schema_json' => ['nullable', 'array'],
            'is_indexable' => ['nullable', 'boolean'],
            'source_name' => ['nullable', 'string', 'max:120'],
            'source_url' => ['nullable', 'string', 'max:1000'],
            'source_file' => ['nullable', 'string', 'max:255'],
            'source_page_url' => ['nullable', 'string', 'max:1000'],
            'data_year' => ['nullable', 'string', 'max:20'],
            'license_note' => ['nullable', 'string', 'max:1000'],
            'confidence_level' => ['nullable', 'string', 'max:40'],
            'original_raw_value' => ['nullable', 'string'],
            'normalized_value' => ['nullable', 'string'],
            'metadata' => ['nullable', 'array'],
        ]);

        $path = '/'.ltrim($data['url_path'], '/');
        $row = [
            'url_path' => $path,
            'route_name' => $data['route_name'] ?? null,
            'title' => $data['title'] ?? null,
            'meta_description' => $data['meta_description'] ?? null,
            'canonical_url' => $data['canonical_url'] ?? null,
            'robots' => $data['robots'] ?? 'index,follow',
            'og_image' => $data['og_image'] ?? null,
            'schema_json' => json_encode($data['schema_json'] ?? []),
            'is_indexable' => (bool) ($data['is_indexable'] ?? true),
            'source_name' => $data['source_name'] ?? 'manual',
            'source_url' => $data['source_url'] ?? null,
            'source_file' => $data['source_file'] ?? null,
            'source_page_url' => $data['source_page_url'] ?? null,
            'downloaded_at' => null,
            'imported_at' => now(),
            'data_year' => $data['data_year'] ?? null,
            'license_note' => $data['license_note'] ?? null,
            'confidence_level' => $data['confidence_level'] ?? 'manual',
            'original_raw_value' => $data['original_raw_value'] ?? null,
            'normalized_value' => $data['normalized_value'] ?? null,
            'metadata' => json_encode($data['metadata'] ?? []),
            'updated_at' => now(),
        ];

        DB::table('seo_pages')->updateOrInsert(['url_path' => $path], $row + ['created_at' => now()]);
        $page = DB::table('seo_pages')->where('url_path', $path)->first();
        $this->audit('seo_page_saved', 'seo_pages', $page->id, null, (array) $page);

        return $this->success($page, 201);
    }

    public function storeRedirect(Request $request): JsonResponse
    {
        $data = $request->validate([
            'from_path' => ['required', 'string', 'max:255'],
            'to_url' => ['required', 'string', 'max:500'],
            'status_code' => ['nullable', 'integer', 'in:301,302,307,308'],
            'is_active' => ['nullable', 'boolean'],
            'notes' => ['nullable', 'string', 'max:1000'],
        ]);

        $from = '/'.ltrim($data['from_path'], '/');
        DB::table('seo_redirects')->updateOrInsert(
            ['from_path' => $from],
            [
                'to_url' => $data['to_url'],
                'status_code' => $data['status_code'] ?? 301,
                'is_active' => (bool) ($data['is_active'] ?? true),
                'notes' => $data['notes'] ?? null,
                'metadata' => json_encode([]),
                'updated_at' => now(),
                'created_at' => now(),
            ]
        );

        $redirect = DB::table('seo_redirects')->where('from_path', $from)->first();
        $this->audit('seo_redirect_saved', 'seo_redirects', $redirect->id, null, (array) $redirect);

        return $this->success($redirect, 201);
    }

    public function permissions(): JsonResponse
    {
        return $this->success([
            'roles' => $this->table('roles')->orderBy('name')->get(),
            'users' => $this->table('users')->leftJoin('roles', 'roles.id', '=', 'users.role_id')
                ->select('users.id', 'users.name', 'users.email', 'roles.name as role_name', 'users.last_login_at')
                ->orderByDesc('users.id')
                ->limit(100)
                ->get(),
        ]);
    }

    public function approvals(): JsonResponse
    {
        return $this->success([
            'vendors' => $this->table('vendor_marketplace_approvals')->orderByDesc('id')->limit(50)->get(),
            'documents' => $this->table('vendor_documents')->orderByDesc('id')->limit(50)->get(),
            'products' => $this->table('product_approval_status')->orderByDesc('id')->limit(50)->get(),
        ]);
    }

    private function metrics(): array
    {
        return [
            'marketplaces' => $this->safeCount('marketplaces'),
            'products' => $this->safeCount('products'),
            'vendors' => $this->safeCount('vendors'),
            'orders' => $this->safeCount('orders'),
            'users' => $this->safeCount('users'),
            'warehouses' => $this->safeCount('warehouses'),
            'inventory_units' => (int) $this->table('inventory_stocks')->sum('quantity_available'),
            'pos_sales' => $this->safeCount('pos_sales'),
            'lms_courses' => $this->safeCount('lms_courses'),
            'media_assets' => $this->safeCount('admin_media_assets'),
            'seo_pages' => $this->safeCount('seo_pages'),
        ];
    }

    private function recentActivity(): array
    {
        return [
            'audit_logs' => $this->table('audit_logs')->orderByDesc('id')->limit(10)->get(),
            'marketing_audit_logs' => $this->table('marketing_admin_audit_logs')->orderByDesc('id')->limit(10)->get(),
        ];
    }

    private function table(string $table)
    {
        if (!Schema::hasTable($table)) {
            return DB::table('users')->whereRaw('1 = 0');
        }

        return DB::table($table);
    }

    private function safeCount(string $table): int
    {
        return Schema::hasTable($table) ? DB::table($table)->count() : 0;
    }

    private function countWhere(string $table, string $column, string $value): int
    {
        return Schema::hasTable($table) && Schema::hasColumn($table, $column)
            ? DB::table($table)->where($column, $value)->count()
            : 0;
    }

    private function lowStockCount(): int
    {
        if (!Schema::hasTable('inventory_stocks')) {
            return 0;
        }

        return DB::table('inventory_stocks')->whereColumn('quantity_available', '<=', 'reorder_point')->count();
    }

    private function audit(string $action, string $modelType, string|int|null $modelId, mixed $old, mixed $new): void
    {
        if (!Schema::hasTable('audit_logs')) {
            return;
        }

        DB::table('audit_logs')->insert([
            'user_id' => null,
            'action' => $action,
            'model_type' => $modelType,
            'model_id' => is_numeric($modelId) ? (int) $modelId : null,
            'model_display_name' => $modelType,
            'old_values' => $old ? json_encode($old) : null,
            'new_values' => $new ? json_encode($new) : null,
            'ip_address' => request()?->ip(),
            'user_agent' => substr((string) request()?->userAgent(), 0, 500),
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }
}
