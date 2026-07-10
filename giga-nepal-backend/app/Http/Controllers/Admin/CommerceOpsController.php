<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\Erp\DocumentNumberService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

/**
 * Session-authed (admin.web) config actions for the adaptation modules.
 * All mutations are server-side and guarded. This controller NEVER enables a
 * live gateway or writes gateway credentials — provider `config` and `is_live`
 * are left untouched; only the on/off (`is_enabled`) sandbox flag is toggled.
 */
class CommerceOpsController extends Controller
{
    // ---- Admin console settings -------------------------------------------

    public function storeAdminSetting(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'group' => ['required', 'string', 'max:80'],
            'key' => ['required', 'string', 'max:160', 'regex:/^[a-z0-9_.-]+$/i'],
            'value' => ['nullable', 'string', 'max:10000'],
            'type' => ['required', 'string', 'max:40'],
            'is_public' => ['nullable', 'boolean'],
            'description' => ['nullable', 'string', 'max:1000'],
        ]);

        DB::table('admin_settings')->updateOrInsert(
            ['key' => $data['key']],
            [
                'group' => $data['group'],
                'key' => $data['key'],
                'value' => $data['value'] ?? null,
                'type' => $data['type'],
                'is_public' => (bool) ($data['is_public'] ?? false),
                'description' => $data['description'] ?? null,
                'metadata' => json_encode(['saved_via' => 'admin.web']),
                'created_at' => now(),
                'updated_at' => now(),
            ]
        );

        $this->auditAdminAction($request, 'admin_setting_saved', 'admin_settings', null, ['key' => $data['key']]);

        return back()->with('status', 'Setting saved.');
    }

    public function deleteAdminSetting(Request $request, int $setting): RedirectResponse
    {
        $row = DB::table('admin_settings')->where('id', $setting)->first();
        if (! $row) {
            return back()->with('error', 'Setting not found.');
        }

        DB::table('admin_settings')->where('id', $setting)->delete();
        $this->auditAdminAction($request, 'admin_setting_deleted', 'admin_settings', $setting, (array) $row);

        return back()->with('status', 'Setting deleted.');
    }

    // ---- Admin media -------------------------------------------------------

    public function storeMediaAsset(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'file' => ['required', 'file', 'max:51200', 'mimes:jpg,jpeg,png,webp,gif,svg,pdf,csv,txt,zip'],
            'folder' => ['nullable', 'string', 'max:120'],
            'title' => ['nullable', 'string', 'max:180'],
            'alt_text' => ['nullable', 'string', 'max:500'],
            'seo_title' => ['nullable', 'string', 'max:180'],
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
            'metadata' => json_encode(['seo_title' => $data['seo_title'] ?? null]),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $this->auditAdminAction($request, 'media_uploaded', 'admin_media_assets', $id, ['path' => $path]);

        return back()->with('status', 'Media asset uploaded.');
    }

    public function deleteMediaAsset(Request $request, int $asset): RedirectResponse
    {
        $row = DB::table('admin_media_assets')->where('id', $asset)->first();
        if (! $row) {
            return back()->with('error', 'Media asset not found.');
        }

        if ($row->disk && $row->path) {
            Storage::disk($row->disk)->delete($row->path);
        }

        DB::table('admin_media_assets')->where('id', $asset)->delete();
        $this->auditAdminAction($request, 'media_deleted', 'admin_media_assets', $asset, (array) $row);

        return back()->with('status', 'Media asset deleted.');
    }

    // ---- SEO console -------------------------------------------------------

    public function storeSeoPage(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'url_path' => ['required', 'string', 'max:255'],
            'title' => ['nullable', 'string', 'max:255'],
            'meta_description' => ['nullable', 'string', 'max:1000'],
            'canonical_url' => ['nullable', 'string', 'max:500'],
            'robots' => ['required', 'string', 'max:80'],
            'og_image' => ['nullable', 'string', 'max:500'],
            'schema_type' => ['nullable', 'string', 'max:80'],
            'source_name' => ['nullable', 'string', 'max:120'],
            'source_url' => ['nullable', 'string', 'max:1000'],
            'confidence_level' => ['nullable', 'string', 'max:40'],
        ]);

        $path = '/'.ltrim($data['url_path'], '/');
        DB::table('seo_pages')->updateOrInsert(
            ['url_path' => $path],
            [
                'url_path' => $path,
                'title' => $data['title'] ?? null,
                'meta_description' => $data['meta_description'] ?? null,
                'canonical_url' => $data['canonical_url'] ?? null,
                'robots' => $data['robots'],
                'og_image' => $data['og_image'] ?? null,
                'schema_json' => json_encode(['@type' => $data['schema_type'] ?? 'WebPage']),
                'is_indexable' => ! str_contains(strtolower($data['robots']), 'noindex'),
                'source_name' => $data['source_name'] ?? 'manual',
                'source_url' => $data['source_url'] ?? null,
                'imported_at' => now(),
                'confidence_level' => $data['confidence_level'] ?? 'manual',
                'metadata' => json_encode(['saved_via' => 'admin.web']),
                'created_at' => now(),
                'updated_at' => now(),
            ]
        );

        $this->auditAdminAction($request, 'seo_page_saved', 'seo_pages', null, ['url_path' => $path]);

        return back()->with('status', 'SEO page saved.');
    }

    public function storeSeoRedirect(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'from_path' => ['required', 'string', 'max:255'],
            'to_url' => ['required', 'string', 'max:500'],
            'status_code' => ['required', 'integer', 'in:301,302,307,308'],
            'is_active' => ['nullable', 'boolean'],
            'notes' => ['nullable', 'string', 'max:1000'],
        ]);

        $from = '/'.ltrim($data['from_path'], '/');
        DB::table('seo_redirects')->updateOrInsert(
            ['from_path' => $from],
            [
                'from_path' => $from,
                'to_url' => $data['to_url'],
                'status_code' => $data['status_code'],
                'is_active' => (bool) ($data['is_active'] ?? false),
                'notes' => $data['notes'] ?? null,
                'metadata' => json_encode(['saved_via' => 'admin.web']),
                'created_at' => now(),
                'updated_at' => now(),
            ]
        );

        $this->auditAdminAction($request, 'seo_redirect_saved', 'seo_redirects', null, ['from_path' => $from]);

        return back()->with('status', 'Redirect saved.');
    }

    public function deleteSeoRedirect(Request $request, int $redirect): RedirectResponse
    {
        $row = DB::table('seo_redirects')->where('id', $redirect)->first();
        if (! $row) {
            return back()->with('error', 'Redirect not found.');
        }

        DB::table('seo_redirects')->where('id', $redirect)->delete();
        $this->auditAdminAction($request, 'seo_redirect_deleted', 'seo_redirects', $redirect, (array) $row);

        return back()->with('status', 'Redirect deleted.');
    }

    // ---- Catalog management ------------------------------------------------

    public function storeCategory(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'id' => ['nullable', 'integer', 'exists:product_categories,id'],
            'parent_id' => ['nullable', 'integer', 'exists:product_categories,id'],
            'name' => ['required', 'string', 'max:180'],
            'slug' => ['nullable', 'string', 'max:200'],
            'description' => ['nullable', 'string', 'max:2000'],
            'icon_path' => ['nullable', 'string', 'max:500'],
            'sort_order' => ['nullable', 'integer', 'min:0'],
            'is_active' => ['nullable', 'boolean'],
            'is_featured' => ['nullable', 'boolean'],
            'seo_title' => ['nullable', 'string', 'max:255'],
            'seo_description' => ['nullable', 'string', 'max:500'],
            'country_visibility' => ['nullable', 'string', 'max:255'],
            'lms_topic' => ['nullable', 'string', 'max:255'],
        ]);

        $slug = $data['slug'] ?: Str::slug($data['name']);
        $payload = [
            'parent_id' => $data['parent_id'] ?? null,
            'name' => $data['name'],
            'slug' => $slug,
            'description' => $data['description'] ?? null,
            'icon_path' => $data['icon_path'] ?? null,
            'sort_order' => $data['sort_order'] ?? 100,
            'is_active' => (bool) ($data['is_active'] ?? false),
            'is_featured' => (bool) ($data['is_featured'] ?? false),
            'seo_meta' => json_encode([
                'title' => $data['seo_title'] ?? null,
                'description' => $data['seo_description'] ?? null,
                'country_visibility' => $data['country_visibility'] ?? null,
                'lms_topic' => $data['lms_topic'] ?? null,
            ]),
            'marketplace_visibility' => json_encode([]),
            'updated_at' => now(),
        ];

        if (! empty($data['id'])) {
            DB::table('product_categories')->where('id', $data['id'])->update($payload);
            $id = (int) $data['id'];
            $verb = 'updated';
        } else {
            $payload['created_at'] = now();
            $id = DB::table('product_categories')->insertGetId($payload);
            $verb = 'created';
        }

        $this->auditAdminAction($request, 'category_'.$verb, 'product_categories', $id, $payload + ['id' => $id]);

        return back()->with('status', "Category {$verb}.");
    }

    public function deactivateCategory(Request $request, int $category): RedirectResponse
    {
        $row = DB::table('product_categories')->where('id', $category)->first();
        if (! $row) {
            return back()->with('error', 'Category not found.');
        }

        DB::table('product_categories')->where('id', $category)->update(['is_active' => ! $row->is_active, 'updated_at' => now()]);
        $this->auditAdminAction($request, 'category_status_toggled', 'product_categories', $category, ['is_active' => ! $row->is_active]);

        return back()->with('status', 'Category status updated.');
    }

    public function storeProduct(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'id' => ['nullable', 'integer', 'exists:products,id'],
            'name' => ['required', 'string', 'max:255'],
            'slug' => ['nullable', 'string', 'max:255'],
            'sku' => ['required', 'string', 'max:120'],
            'mpn' => ['nullable', 'string', 'max:120'],
            'brand_id' => ['nullable', 'integer', 'exists:product_brands,id'],
            'category_id' => ['nullable', 'integer', 'exists:product_categories,id'],
            'vendor_id' => ['nullable', 'integer', 'exists:vendors,id'],
            'manufacturer_name' => ['nullable', 'string', 'max:255'],
            'type' => ['nullable', 'string', 'max:60'],
            'status' => ['required', 'string', 'max:60'],
            'base_price' => ['nullable', 'numeric', 'min:0'],
            'sale_price' => ['nullable', 'numeric', 'min:0'],
            'stock_quantity' => ['nullable', 'integer', 'min:0'],
            'low_stock_threshold' => ['nullable', 'integer', 'min:0'],
            'short_description' => ['nullable', 'string', 'max:1000'],
            'description' => ['nullable', 'string'],
            'meta_title' => ['nullable', 'string', 'max:255'],
            'meta_description' => ['nullable', 'string', 'max:500'],
            'country_of_origin' => ['nullable', 'string', 'max:100'],
            'model_number' => ['nullable', 'string', 'max:120'],
            'regional_visibility' => ['nullable', 'string', 'max:500'],
            'attributes_json' => ['nullable', 'string'],
        ]);

        $slug = $data['slug'] ?: Str::slug($data['name']);
        $payload = [
            'name' => $data['name'],
            'slug' => $slug,
            'sku' => $data['sku'],
            'mpn' => $data['mpn'] ?? null,
            'brand_id' => $data['brand_id'] ?? null,
            'category_id' => $data['category_id'] ?? null,
            'vendor_id' => $data['vendor_id'] ?? null,
            'manufacturer_name' => $data['manufacturer_name'] ?? null,
            'type' => $data['type'] ?? 'physical',
            'status' => $data['status'],
            'base_price' => $data['base_price'] ?? null,
            'sale_price' => $data['sale_price'] ?? null,
            'stock_quantity' => $data['stock_quantity'] ?? 0,
            'low_stock_threshold' => $data['low_stock_threshold'] ?? 5,
            'short_description' => $data['short_description'] ?? null,
            'description' => $data['description'] ?? null,
            'meta_title' => $data['meta_title'] ?? null,
            'meta_description' => $data['meta_description'] ?? null,
            'country_of_origin' => $data['country_of_origin'] ?? null,
            'model_number' => $data['model_number'] ?? null,
            'marketplace_visibility' => json_encode(['regional_visibility' => $data['regional_visibility'] ?? null]),
            'attributes' => $this->jsonOrEmpty($data['attributes_json'] ?? null),
            'metadata' => json_encode(['saved_via' => 'admin.web']),
            'updated_at' => now(),
        ];

        if (! empty($data['id'])) {
            DB::table('products')->where('id', $data['id'])->update($payload);
            $id = (int) $data['id'];
            $verb = 'updated';
        } else {
            $payload['created_by'] = $request->user()?->id;
            $payload['created_at'] = now();
            $id = DB::table('products')->insertGetId($payload);
            $verb = 'created';
        }

        $this->auditAdminAction($request, 'product_'.$verb, 'products', $id, ['sku' => $data['sku'], 'name' => $data['name']]);

        return back()->with('status', "Product {$verb}.");
    }

    public function duplicateProduct(Request $request, int $product): RedirectResponse
    {
        $row = DB::table('products')->where('id', $product)->first();
        if (! $row) {
            return back()->with('error', 'Product not found.');
        }

        $copy = (array) $row;
        unset($copy['id']);
        $copy['name'] = $row->name.' Copy';
        $copy['slug'] = Str::slug($copy['name']).'-'.Str::lower(Str::random(5));
        $copy['sku'] = $row->sku.'-COPY-'.Str::upper(Str::random(4));
        $copy['status'] = 'draft';
        $copy['created_at'] = now();
        $copy['updated_at'] = now();
        $id = DB::table('products')->insertGetId($copy);

        $this->auditAdminAction($request, 'product_duplicated', 'products', $id, ['source_product_id' => $product]);

        return back()->with('status', 'Product duplicated as draft.');
    }

    public function deactivateProduct(Request $request, int $product): RedirectResponse
    {
        $row = DB::table('products')->where('id', $product)->first();
        if (! $row) {
            return back()->with('error', 'Product not found.');
        }

        $next = in_array($row->status, ['inactive', 'archived'], true) ? 'draft' : 'inactive';
        DB::table('products')->where('id', $product)->update(['status' => $next, 'updated_at' => now()]);
        $this->auditAdminAction($request, 'product_status_updated', 'products', $product, ['status' => $next]);

        return back()->with('status', 'Product status updated.');
    }

    public function adjustProductStock(Request $request, int $product): RedirectResponse
    {
        $data = $request->validate([
            'stock_quantity' => ['required', 'integer', 'min:0'],
            'low_stock_threshold' => ['nullable', 'integer', 'min:0'],
            'note' => ['nullable', 'string', 'max:500'],
        ]);

        DB::table('products')->where('id', $product)->update([
            'stock_quantity' => $data['stock_quantity'],
            'low_stock_threshold' => $data['low_stock_threshold'] ?? DB::raw('low_stock_threshold'),
            'updated_at' => now(),
        ]);

        $this->auditAdminAction($request, 'product_stock_adjusted', 'products', $product, $data);

        return back()->with('status', 'Product stock updated.');
    }

    public function storeProductSpec(Request $request, int $product): RedirectResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:190'],
            'value' => ['nullable', 'string', 'max:2000'],
            'unit' => ['nullable', 'string', 'max:40'],
            'sort_order' => ['nullable', 'integer', 'min:0'],
            'is_visible' => ['nullable', 'boolean'],
            'is_filterable' => ['nullable', 'boolean'],
        ]);

        abort_unless(DB::table('products')->where('id', $product)->exists(), 404);

        $id = DB::table('product_specs')->insertGetId([
            'product_id' => $product,
            'name' => $data['name'],
            'value' => $data['value'] ?? null,
            'unit' => $data['unit'] ?? null,
            'sort_order' => $data['sort_order'] ?? 100,
            'is_visible' => (bool) ($data['is_visible'] ?? true),
            'is_filterable' => (bool) ($data['is_filterable'] ?? false),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $this->auditAdminAction($request, 'product_spec_created', 'product_specs', $id, ['product_id' => $product, 'name' => $data['name']]);

        return back()->with('status', 'Product specification added.');
    }

    public function deleteProductSpec(Request $request, int $product, int $spec): RedirectResponse
    {
        DB::table('product_specs')->where('product_id', $product)->where('id', $spec)->delete();
        $this->auditAdminAction($request, 'product_spec_deleted', 'product_specs', $spec, ['product_id' => $product]);

        return back()->with('status', 'Product specification deleted.');
    }

    public function storeProductDocument(Request $request, int $product): RedirectResponse
    {
        $data = $request->validate([
            'title' => ['required', 'string', 'max:190'],
            'document_type' => ['required', 'string', 'max:80'],
            'file_url' => ['nullable', 'string', 'max:1000'],
            'source_url' => ['nullable', 'string', 'max:1000'],
        ]);

        abort_unless(DB::table('products')->where('id', $product)->exists(), 404);

        $id = DB::table('product_documents')->insertGetId([
            'product_id' => $product,
            'title' => $data['title'],
            'document_type' => $data['document_type'],
            'file_url' => $data['file_url'] ?? null,
            'source_url' => $data['source_url'] ?? null,
            'metadata' => json_encode(['saved_via' => 'admin.web']),
            'is_active' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $this->auditAdminAction($request, 'product_document_created', 'product_documents', $id, ['product_id' => $product, 'title' => $data['title']]);

        return back()->with('status', 'Product document attached.');
    }

    public function deleteProductDocument(Request $request, int $product, int $document): RedirectResponse
    {
        DB::table('product_documents')->where('product_id', $product)->where('id', $document)->update(['is_active' => false, 'updated_at' => now()]);
        $this->auditAdminAction($request, 'product_document_deactivated', 'product_documents', $document, ['product_id' => $product]);

        return back()->with('status', 'Product document deactivated.');
    }

    public function storeProductRelated(Request $request, int $product): RedirectResponse
    {
        $data = $request->validate([
            'related_product_id' => ['required', 'integer', 'exists:products,id'],
            'relation_type' => ['required', 'string', 'max:80'],
            'notes' => ['nullable', 'string', 'max:1000'],
        ]);

        abort_unless($product !== (int) $data['related_product_id'], 422);

        $id = DB::table('product_related_items')->insertGetId([
            'product_id' => $product,
            'related_product_id' => $data['related_product_id'],
            'relation_type' => $data['relation_type'],
            'notes' => $data['notes'] ?? null,
            'sort_order' => 100,
            'is_active' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $this->auditAdminAction($request, 'product_related_created', 'product_related_items', $id, ['product_id' => $product, 'related_product_id' => $data['related_product_id']]);

        return back()->with('status', 'Related product linked.');
    }

    public function deleteProductRelated(Request $request, int $product, int $related): RedirectResponse
    {
        DB::table('product_related_items')->where('product_id', $product)->where('id', $related)->update(['is_active' => false, 'updated_at' => now()]);
        $this->auditAdminAction($request, 'product_related_deactivated', 'product_related_items', $related, ['product_id' => $product]);

        return back()->with('status', 'Related product deactivated.');
    }

    public function storeProductLmsLink(Request $request, int $product): RedirectResponse
    {
        $data = $request->validate([
            'title' => ['required', 'string', 'max:190'],
            'url' => ['nullable', 'string', 'max:1000'],
            'relation_type' => ['required', 'string', 'max:80'],
            'notes' => ['nullable', 'string', 'max:1000'],
        ]);

        $id = DB::table('product_lms_links')->insertGetId([
            'product_id' => $product,
            'title' => $data['title'],
            'url' => $data['url'] ?? null,
            'relation_type' => $data['relation_type'],
            'notes' => $data['notes'] ?? null,
            'is_active' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $this->auditAdminAction($request, 'product_lms_link_created', 'product_lms_links', $id, ['product_id' => $product, 'title' => $data['title']]);

        return back()->with('status', 'LMS link attached.');
    }

    public function updateProductSeo(Request $request, int $product): RedirectResponse
    {
        $data = $request->validate([
            'meta_title' => ['nullable', 'string', 'max:255'],
            'meta_description' => ['nullable', 'string', 'max:1000'],
            'canonical_url' => ['nullable', 'string', 'max:1000'],
            'robots' => ['required', 'string', 'max:80'],
            'schema_type' => ['required', 'string', 'max:80'],
            'confidence_level' => ['required', 'string', 'max:80'],
        ]);

        DB::table('product_seo_meta')->updateOrInsert(
            ['product_id' => $product],
            $data + [
                'product_id' => $product,
                'metadata' => json_encode(['saved_via' => 'admin.web']),
                'created_at' => now(),
                'updated_at' => now(),
            ]
        );
        DB::table('products')->where('id', $product)->update([
            'seo_meta' => json_encode(['title' => $data['meta_title'] ?? null, 'description' => $data['meta_description'] ?? null]),
            'updated_at' => now(),
        ]);
        $this->auditAdminAction($request, 'product_seo_saved', 'product_seo_meta', null, ['product_id' => $product]);

        return back()->with('status', 'Product SEO saved.');
    }

    // ---- Seller / vendor management ---------------------------------------

    public function storeVendor(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'id' => ['nullable', 'integer', 'exists:vendors,id'],
            'name' => ['required', 'string', 'max:180'],
            'slug' => ['nullable', 'string', 'max:200'],
            'email' => ['nullable', 'email', 'max:255'],
            'phone' => ['nullable', 'string', 'max:80'],
            'website' => ['nullable', 'string', 'max:255'],
            'type' => ['required', 'in:individual,company,manufacturer,distributor'],
            'status' => ['required', 'in:pending,active,suspended,rejected'],
            'country_id' => ['nullable', 'integer', 'exists:countries,id'],
            'tax_number' => ['nullable', 'string', 'max:120'],
            'registration_number' => ['nullable', 'string', 'max:120'],
            'commission_rate' => ['nullable', 'numeric', 'min:0', 'max:100'],
            'settlement_note' => ['nullable', 'string', 'max:1000'],
            'description' => ['nullable', 'string', 'max:2000'],
            'country_visibility' => ['nullable', 'string', 'max:255'],
            'marketplace_visibility' => ['nullable', 'string', 'max:255'],
        ]);

        $payload = [
            'name' => $data['name'],
            'slug' => $data['slug'] ?: Str::slug($data['name']),
            'email' => $data['email'] ?? null,
            'phone' => $data['phone'] ?? null,
            'website' => $data['website'] ?? null,
            'type' => $data['type'],
            'status' => $data['status'],
            'country_id' => $data['country_id'] ?? null,
            'tax_number' => $data['tax_number'] ?? null,
            'registration_number' => $data['registration_number'] ?? null,
            'description' => $data['description'] ?? null,
            'metadata' => json_encode([
                'commission_rate' => $data['commission_rate'] ?? null,
                'settlement_note' => $data['settlement_note'] ?? null,
                'country_visibility' => $data['country_visibility'] ?? null,
                'marketplace_visibility' => $data['marketplace_visibility'] ?? null,
                'saved_via' => 'admin.web',
            ]),
            'updated_at' => now(),
        ];

        if (! empty($data['id'])) {
            DB::table('vendors')->where('id', $data['id'])->update($payload);
            $id = (int) $data['id'];
            $verb = 'updated';
        } else {
            $payload['created_at'] = now();
            $id = DB::table('vendors')->insertGetId($payload);
            $verb = 'created';
        }

        $this->vendorAudit($request, $id, 'vendor_'.$verb, $payload);
        $this->auditAdminAction($request, 'vendor_'.$verb, 'vendors', $id, ['name' => $data['name'], 'status' => $data['status']]);

        return back()->with('status', "Seller {$verb}.");
    }

    public function updateVendorStatus(Request $request, int $vendor): RedirectResponse
    {
        $data = $request->validate([
            'status' => ['required', 'in:pending,active,rejected,suspended'],
            'note' => ['nullable', 'string', 'max:1000'],
        ]);

        $row = DB::table('vendors')->where('id', $vendor)->first();
        if (! $row) {
            return back()->with('error', 'Seller not found.');
        }

        DB::table('vendors')->where('id', $vendor)->update([
            'status' => $data['status'],
            'verified_at' => $data['status'] === 'active' ? now() : $row->verified_at,
            'updated_at' => now(),
        ]);

        $this->vendorAudit($request, $vendor, 'vendor_status_updated', ['from' => $row->status, 'to' => $data['status'], 'note' => $data['note'] ?? null]);

        return back()->with('status', 'Seller status updated.');
    }

    // ---- Users and roles ---------------------------------------------------

    public function storeUser(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'id' => ['nullable', 'integer', 'exists:users,id'],
            'name' => ['required', 'string', 'max:180'],
            'email' => ['required', 'email', 'max:255'],
            'role_id' => ['nullable', 'integer', 'exists:roles,id'],
            'country_access' => ['nullable', 'string', 'max:255'],
            'seller_org' => ['nullable', 'string', 'max:255'],
            'temporary_password' => ['nullable', 'string', 'min:8', 'max:120'],
            'disable' => ['nullable', 'boolean'],
        ]);

        $payload = [
            'name' => $data['name'],
            'email' => $data['email'],
            'role_id' => $data['role_id'] ?? null,
            'updated_at' => now(),
        ];

        if (! empty($data['temporary_password'])) {
            $payload['password'] = Hash::make($data['temporary_password']);
        }

        if (! empty($data['disable'])) {
            $payload['api_token_hash'] = null;
            $payload['remember_token'] = null;
        }

        if (! empty($data['id'])) {
            DB::table('users')->where('id', $data['id'])->update($payload);
            $id = (int) $data['id'];
            $verb = 'updated';
        } else {
            $payload['password'] = $payload['password'] ?? Hash::make(Str::random(20));
            $payload['created_at'] = now();
            $id = DB::table('users')->insertGetId($payload);
            $verb = 'created';
        }

        $this->auditAdminAction($request, 'user_'.$verb, 'users', $id, [
            'email' => $data['email'],
            'role_id' => $data['role_id'] ?? null,
            'country_access' => $data['country_access'] ?? null,
            'seller_org' => $data['seller_org'] ?? null,
            'disabled' => (bool) ($data['disable'] ?? false),
        ]);

        return back()->with('status', "User {$verb}.");
    }

    // ---- LMS operations ----------------------------------------------------

    public function storeLmsCourse(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'slug' => ['nullable', 'string', 'max:255'],
            'lms_course_category_id' => ['nullable', 'integer'],
            'level' => ['nullable', 'string', 'max:60'],
            'status' => ['required', 'string', 'max:60'],
            'language' => ['nullable', 'string', 'max:20'],
            'estimated_minutes' => ['nullable', 'integer', 'min:0'],
            'subtitle' => ['nullable', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'seo_title' => ['nullable', 'string', 'max:255'],
            'seo_description' => ['nullable', 'string', 'max:500'],
        ]);

        $id = DB::table('lms_courses')->insertGetId([
            'lms_course_category_id' => $data['lms_course_category_id'] ?? null,
            'title' => $data['title'],
            'slug' => $data['slug'] ?: Str::slug($data['title']).'-'.Str::lower(Str::random(4)),
            'subtitle' => $data['subtitle'] ?? null,
            'description' => $data['description'] ?? null,
            'level' => $data['level'] ?? 'beginner',
            'status' => $data['status'],
            'language' => $data['language'] ?? 'en',
            'estimated_minutes' => $data['estimated_minutes'] ?? null,
            'seo_title' => $data['seo_title'] ?? null,
            'seo_description' => $data['seo_description'] ?? null,
            'published_at' => $data['status'] === 'published' ? now() : null,
            'metadata' => json_encode(['ai_tutor_placeholder' => true, 'saved_via' => 'admin.web']),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $this->auditAdminAction($request, 'lms_course_created', 'lms_courses', $id, ['title' => $data['title']]);

        return back()->with('status', 'Course created.');
    }

    public function storeLmsLesson(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'lms_course_id' => ['nullable', 'integer', 'exists:lms_courses,id'],
            'lms_module_id' => ['nullable', 'integer', 'exists:lms_modules,id'],
            'title' => ['required', 'string', 'max:255'],
            'type' => ['nullable', 'string', 'max:60'],
            'summary' => ['nullable', 'string', 'max:1000'],
            'content' => ['nullable', 'string'],
            'video_url' => ['nullable', 'string', 'max:500'],
            'duration_minutes' => ['nullable', 'integer', 'min:0'],
            'status' => ['required', 'string', 'max:60'],
        ]);

        $id = DB::table('lms_lessons')->insertGetId([
            'lms_course_id' => $data['lms_course_id'] ?? null,
            'lms_module_id' => $data['lms_module_id'] ?? null,
            'title' => $data['title'],
            'slug' => Str::slug($data['title']).'-'.Str::lower(Str::random(4)),
            'type' => $data['type'] ?? 'tutorial',
            'summary' => $data['summary'] ?? null,
            'content' => $data['content'] ?? null,
            'video_url' => $data['video_url'] ?? null,
            'duration_minutes' => $data['duration_minutes'] ?? null,
            'sort_order' => 100,
            'is_preview' => false,
            'status' => $data['status'],
            'metadata' => json_encode(['product_attach_placeholder' => true, 'file_upload_placeholder' => true]),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $this->auditAdminAction($request, 'lms_lesson_created', 'lms_lessons', $id, ['title' => $data['title']]);

        return back()->with('status', 'Lesson created.');
    }

    public function toggleLmsCourse(Request $request, int $course): RedirectResponse
    {
        $row = DB::table('lms_courses')->where('id', $course)->first();
        if (! $row) return back()->with('error', 'Course not found.');
        $next = $row->status === 'published' ? 'draft' : 'published';
        DB::table('lms_courses')->where('id', $course)->update(['status' => $next, 'published_at' => $next === 'published' ? now() : null, 'updated_at' => now()]);
        $this->auditAdminAction($request, 'lms_course_status_updated', 'lms_courses', $course, ['status' => $next]);
        return back()->with('status', 'Course status updated.');
    }

    // ---- Inventory operations --------------------------------------------

    public function storeWarehouse(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:180'],
            'code' => ['nullable', 'string', 'max:80'],
            'country_id' => ['nullable', 'integer', 'exists:countries,id'],
            'address_line1' => ['nullable', 'string', 'max:255'],
            'contact_name' => ['nullable', 'string', 'max:180'],
            'contact_phone' => ['nullable', 'string', 'max:80'],
            'is_active' => ['nullable', 'boolean'],
        ]);

        $id = DB::table('warehouses')->insertGetId([
            'name' => $data['name'],
            'code' => $data['code'] ?: 'WH-'.Str::upper(Str::random(6)),
            'country_id' => $data['country_id'] ?? null,
            'address_line1' => $data['address_line1'] ?? null,
            'contact_name' => $data['contact_name'] ?? null,
            'contact_phone' => $data['contact_phone'] ?? null,
            'is_active' => (bool) ($data['is_active'] ?? true),
            'is_default' => false,
            'metadata' => json_encode(['saved_via' => 'admin.web']),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $this->auditAdminAction($request, 'warehouse_created', 'warehouses', $id, ['name' => $data['name']]);
        return back()->with('status', 'Warehouse created.');
    }

    public function adjustInventoryStock(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'product_id' => ['required', 'integer', 'exists:products,id'],
            'warehouse_id' => ['required', 'integer', 'exists:warehouses,id'],
            'quantity_available' => ['required', 'integer', 'min:0'],
            'reorder_point' => ['nullable', 'integer', 'min:0'],
            'notes' => ['nullable', 'string', 'max:500'],
        ]);

        $stock = DB::table('inventory_stocks')->where('product_id', $data['product_id'])->where('warehouse_id', $data['warehouse_id'])->first();
        $before = (int) ($stock->quantity_available ?? 0);
        $payload = [
            'product_id' => $data['product_id'],
            'warehouse_id' => $data['warehouse_id'],
            'quantity_available' => $data['quantity_available'],
            'quantity_on_hand' => $data['quantity_available'],
            'reorder_point' => $data['reorder_point'] ?? 5,
            'is_active' => true,
            'status' => 'active',
            'metadata' => json_encode(['saved_via' => 'admin.web']),
            'updated_at' => now(),
        ];

        if ($stock) {
            DB::table('inventory_stocks')->where('id', $stock->id)->update($payload);
            $stockId = $stock->id;
        } else {
            $payload += ['quantity_reserved' => 0, 'quantity_damaged' => 0, 'quantity_incoming' => 0, 'created_at' => now()];
            $stockId = DB::table('inventory_stocks')->insertGetId($payload);
        }

        DB::table('inventory_movements')->insert([
            'inventory_stock_id' => $stockId,
            'product_id' => $data['product_id'],
            'warehouse_id' => $data['warehouse_id'],
            'movement_type' => 'admin_adjustment',
            'quantity_change' => (int) $data['quantity_available'] - $before,
            'quantity_before' => $before,
            'quantity_after' => $data['quantity_available'],
            'notes' => $data['notes'] ?? 'Adjusted via admin console',
            'user_id' => $request->user()?->id,
            'metadata' => json_encode([]),
            'occurred_at' => now(),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $this->auditAdminAction($request, 'inventory_stock_adjusted', 'inventory_stocks', $stockId, $data);
        return back()->with('status', 'Inventory stock adjusted.');
    }

    // ---- POS operations ----------------------------------------------------

    public function storePosTerminal(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'terminal_name' => ['required', 'string', 'max:180'],
            'terminal_code' => ['nullable', 'string', 'max:80'],
            'warehouse_id' => ['nullable', 'integer', 'exists:warehouses,id'],
            'location' => ['nullable', 'string', 'max:255'],
            'status' => ['required', 'string', 'max:60'],
        ]);

        $id = DB::table('pos_terminals')->insertGetId([
            'terminal_name' => $data['terminal_name'],
            'terminal_code' => $data['terminal_code'] ?: 'POS-'.Str::upper(Str::random(6)),
            'warehouse_id' => $data['warehouse_id'] ?? null,
            'location' => $data['location'] ?? null,
            'status' => $data['status'],
            'metadata' => json_encode(['offline_sync_status' => 'placeholder', 'ai_pos_placeholder' => true]),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $this->auditAdminAction($request, 'pos_terminal_created', 'pos_terminals', $id, ['terminal_name' => $data['terminal_name']]);
        return back()->with('status', 'POS terminal created.');
    }

    public function openPosSession(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'pos_terminal_id' => ['required', 'integer', 'exists:pos_terminals,id'],
            'warehouse_id' => ['nullable', 'integer', 'exists:warehouses,id'],
            'opening_cash' => ['nullable', 'numeric', 'min:0'],
        ]);

        $terminal = DB::table('pos_terminals')->where('id', $data['pos_terminal_id'])->first();
        $id = DB::table('pos_sessions')->insertGetId([
            'pos_terminal_id' => $data['pos_terminal_id'],
            'warehouse_id' => $data['warehouse_id'] ?? $terminal->warehouse_id ?? null,
            'user_id' => $request->user()?->id,
            'session_number' => 'POSS-'.now()->format('YmdHis').'-'.Str::upper(Str::random(3)),
            'status' => 'open',
            'opening_cash' => $data['opening_cash'] ?? 0,
            'opened_at' => now(),
            'metadata' => json_encode(['opened_via' => 'admin.web']),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $this->auditAdminAction($request, 'pos_session_opened', 'pos_sessions', $id, $data);
        return back()->with('status', 'POS session opened.');
    }

    public function closePosSession(Request $request, int $session): RedirectResponse
    {
        $data = $request->validate(['closing_cash' => ['nullable', 'numeric', 'min:0'], 'notes' => ['nullable', 'string', 'max:1000']]);
        DB::table('pos_sessions')->where('id', $session)->where('status', 'open')->update([
            'status' => 'closed',
            'closing_cash' => $data['closing_cash'] ?? 0,
            'closed_at' => now(),
            'notes' => $data['notes'] ?? null,
            'updated_at' => now(),
        ]);
        $this->auditAdminAction($request, 'pos_session_closed', 'pos_sessions', $session, $data);
        return back()->with('status', 'POS session closed.');
    }

    // ---- Payments ----------------------------------------------------------

    public function toggleProvider(Request $request, int $provider): RedirectResponse
    {
        $row = DB::table('payment_providers')->where('id', $provider)->first();
        if (! $row) {
            return back()->with('error', 'Provider not found.');
        }

        DB::table('payment_providers')
            ->where('id', $provider)
            ->update(['is_enabled' => ! $row->is_enabled, 'updated_at' => now()]);

        return back()->with('status', "Provider {$row->code} " . ($row->is_enabled ? 'disabled' : 'enabled') . '.');
    }

    public function approvePayout(int $payout): RedirectResponse
    {
        DB::table('vendor_payouts')->where('id', $payout)->where('status', 'pending')
            ->update(['status' => 'approved', 'updated_at' => now()]);

        return back()->with('status', "Payout #{$payout} approved.");
    }

    public function markPayoutPaid(int $payout): RedirectResponse
    {
        DB::table('vendor_payouts')->where('id', $payout)->whereIn('status', ['approved', 'processing'])
            ->update(['status' => 'paid', 'updated_at' => now()]);

        return back()->with('status', "Payout #{$payout} marked paid.");
    }

    // ---- Promotions --------------------------------------------------------

    public function storeCoupon(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'code' => ['required', 'string', 'max:64', 'unique:coupons,code'],
            'type' => ['required', 'in:percentage,fixed'],
            'value' => ['required', 'numeric', 'min:0'],
            'currency' => ['nullable', 'string', 'size:3'],
            'min_order_total' => ['nullable', 'numeric', 'min:0'],
            'usage_limit' => ['nullable', 'integer', 'min:0'],
            'ends_at' => ['nullable', 'date'],
        ]);

        DB::table('coupons')->insert([
            'code' => strtoupper($data['code']),
            'type' => $data['type'],
            'value' => $data['value'],
            'currency' => strtoupper($data['currency'] ?? 'USD'),
            'scope' => 'cart',
            'min_order_total' => $data['min_order_total'] ?? 0,
            'usage_limit' => $data['usage_limit'] ?? null,
            'usage_limit_per_user' => null,
            'used_count' => 0,
            'ends_at' => $data['ends_at'] ?? null,
            'is_active' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        return back()->with('status', "Coupon {$data['code']} created.");
    }

    public function toggleCoupon(int $coupon): RedirectResponse
    {
        $row = DB::table('coupons')->where('id', $coupon)->first();
        if (! $row) {
            return back()->with('error', 'Coupon not found.');
        }

        DB::table('coupons')->where('id', $coupon)
            ->update(['is_active' => ! $row->is_active, 'updated_at' => now()]);

        return back()->with('status', "Coupon {$row->code} " . ($row->is_active ? 'deactivated' : 'activated') . '.');
    }

    // ---- Affiliate ---------------------------------------------------------

    public function approveAffiliate(int $affiliate): RedirectResponse
    {
        DB::table('affiliates')->where('id', $affiliate)->where('status', 'pending')
            ->update(['status' => 'approved', 'updated_at' => now()]);

        return back()->with('status', "Affiliate #{$affiliate} approved.");
    }

    public function approveCommission(int $commission): RedirectResponse
    {
        DB::table('commission_ledger')->where('id', $commission)->where('status', 'pending')
            ->update(['status' => 'approved', 'approved_at' => now(), 'updated_at' => now()]);

        return back()->with('status', "Commission #{$commission} approved.");
    }

    // ---- Orders ---------------------------------------------------------------

    public function updateOrderStatus(Request $request, int $order): RedirectResponse
    {
        // Whitelist mirrors the orders.status DB enum exactly.
        $data = $request->validate([
            'status' => ['required', 'in:pending,confirmed,processing,shipped,delivered,cancelled,refunded,failed'],
            'notes' => ['nullable', 'string', 'max:1000'],
        ]);

        $row = DB::table('orders')->where('id', $order)->first();
        if (! $row) {
            return back()->with('error', 'Order not found.');
        }
        if ($row->status === $data['status']) {
            return back()->with('error', 'Order is already ' . $data['status'] . '.');
        }

        DB::transaction(function () use ($order, $row, $data, $request) {
            DB::table('orders')->where('id', $order)
                ->update(['status' => $data['status'], 'updated_at' => now()]);

            // Audit trail — every admin status change is recorded.
            DB::table('order_status_histories')->insert([
                'order_id' => $order,
                'previous_status' => $row->status,
                'status' => $data['status'],
                'notes' => $data['notes'] ?? 'Changed via admin console',
                'changed_by_user_id' => $request->user()?->id,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        });

        return back()->with('status', "Order {$row->order_number}: {$row->status} → {$data['status']}.");
    }

    public function updateOrderTracking(Request $request, int $order): RedirectResponse
    {
        $data = $request->validate([
            'carrier' => ['nullable', 'string', 'max:120'],
            'tracking_number' => ['nullable', 'string', 'max:190'],
            'vendor_notes' => ['nullable', 'string', 'max:2000'],
            'shipped_at' => ['nullable', 'date'],
            'delivered_at' => ['nullable', 'date'],
        ]);

        $row = DB::table('orders')->where('id', $order)->first();
        if (! $row) {
            return back()->with('error', 'Order not found.');
        }

        DB::table('orders')->where('id', $order)->update([
            'carrier' => $data['carrier'] ?? null,
            'tracking_number' => $data['tracking_number'] ?? null,
            'vendor_notes' => $data['vendor_notes'] ?? null,
            'shipped_at' => $data['shipped_at'] ?? null,
            'delivered_at' => $data['delivered_at'] ?? null,
            'updated_at' => now(),
        ]);

        $this->auditAdminAction($request, 'order_tracking_updated', 'orders', $order, [
            'order_number' => $row->order_number,
            'carrier' => $data['carrier'] ?? null,
            'tracking_number' => $data['tracking_number'] ?? null,
        ]);

        return back()->with('status', "Order {$row->order_number} tracking updated.");
    }

    public function updateRfqStatus(Request $request, int $rfq): RedirectResponse
    {
        $data = $request->validate([
            'status' => ['required', 'in:open,quoted,accepted,closed,cancelled'],
            'notes' => ['nullable', 'string', 'max:3000'],
        ]);

        $row = DB::table('rfq_requests')->where('id', $rfq)->first();
        if (! $row) {
            return back()->with('error', 'RFQ not found.');
        }

        $meta = $this->mergeJsonMeta($row->meta ?? null, [
            'last_admin_status_note' => $data['notes'] ?? null,
            'last_admin_status_by' => $request->user()?->id,
            'last_admin_status_at' => now()->toIso8601String(),
        ]);

        DB::table('rfq_requests')->where('id', $rfq)->update([
            'status' => $data['status'],
            'notes' => $data['notes'] ?? $row->notes,
            'meta' => $meta,
            'updated_at' => now(),
        ]);

        // Timeline audit trail (rendered on /admin/rfqs/{id})
        DB::table('rfq_status_histories')->insert([
            'rfq_request_id' => $rfq,
            'previous_status' => $row->status,
            'status' => $data['status'],
            'notes' => $data['notes'] ?? 'Changed via admin console',
            'changed_by_user_id' => $request->user()?->id,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $this->auditAdminAction($request, 'rfq_status_updated', 'rfq_requests', $rfq, [
            'rfq_number' => $row->rfq_number,
            'from' => $row->status,
            'to' => $data['status'],
            'notes' => $data['notes'] ?? null,
        ]);

        return back()->with('status', "RFQ {$row->rfq_number}: {$row->status} → {$data['status']}.");
    }

    public function storeRfqQuotation(Request $request, int $rfq): RedirectResponse
    {
        $request->merge([
            'items' => array_values(array_filter($request->input('items', []), function ($item) {
                return filled($item['name'] ?? null)
                    || filled($item['quantity'] ?? null)
                    || filled($item['unit_price'] ?? null);
            })),
        ]);

        $data = $request->validate([
            'valid_until' => ['nullable', 'date'],
            'notes' => ['nullable', 'string', 'max:3000'],
            'shipping_total' => ['nullable', 'numeric', 'min:0'],
            'items' => ['required', 'array', 'min:1'],
            'items.*.sku' => ['nullable', 'string', 'max:120'],
            'items.*.name' => ['required', 'string', 'max:255'],
            'items.*.quantity' => ['required', 'numeric', 'min:0.001'],
            'items.*.unit_price' => ['required', 'numeric', 'min:0'],
            'items.*.tax_amount' => ['nullable', 'numeric', 'min:0'],
        ]);

        $rfqRow = DB::table('rfq_requests')->where('id', $rfq)->first();
        if (! $rfqRow) {
            return back()->with('error', 'RFQ not found.');
        }

        $quoteId = null;
        $quoteNumber = null;
        DB::transaction(function () use ($request, $rfq, $rfqRow, $data, &$quoteId, &$quoteNumber) {
            $subtotal = 0.0;
            $taxTotal = 0.0;
            $items = [];

            foreach ($data['items'] as $item) {
                $quantity = (float) $item['quantity'];
                $unitPrice = (float) $item['unit_price'];
                $taxAmount = (float) ($item['tax_amount'] ?? 0);
                $lineTotal = round(($quantity * $unitPrice) + $taxAmount, 2);
                $subtotal += round($quantity * $unitPrice, 2);
                $taxTotal += $taxAmount;
                $items[] = [
                    'product_id' => null,
                    'sku' => $item['sku'] ?? null,
                    'name' => $item['name'],
                    'quantity' => $quantity,
                    'unit_price' => $unitPrice,
                    'tax_amount' => $taxAmount,
                    'line_total' => $lineTotal,
                    'created_at' => now(),
                    'updated_at' => now(),
                ];
            }

            $shipping = (float) ($data['shipping_total'] ?? 0);
            $quoteNumber = 'Q-'.now()->format('Ymd-His').'-'.$rfq;
            $quoteId = DB::table('quotations')->insertGetId([
                'quote_number' => $quoteNumber,
                'rfq_request_id' => $rfq,
                'user_id' => $rfqRow->user_id,
                'currency' => $rfqRow->currency ?: 'USD',
                'status' => 'draft',
                'subtotal' => round($subtotal, 2),
                'tax_total' => round($taxTotal, 2),
                'shipping_total' => round($shipping, 2),
                'grand_total' => round($subtotal + $taxTotal + $shipping, 2),
                'valid_until' => $data['valid_until'] ?? null,
                'created_by' => $request->user()?->id,
                'notes' => $data['notes'] ?? null,
                'meta' => json_encode(['created_via' => 'admin.web', 'source_rfq_number' => $rfqRow->rfq_number]),
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            foreach ($items as &$item) {
                $item['quotation_id'] = $quoteId;
            }
            unset($item);

            DB::table('quotation_items')->insert($items);
            DB::table('rfq_requests')->where('id', $rfq)->update([
                'status' => 'quoted',
                'updated_at' => now(),
            ]);
        });

        $this->auditAdminAction($request, 'quotation_created_from_rfq', 'quotations', $quoteId, [
            'quote_number' => $quoteNumber,
            'rfq_number' => $rfqRow->rfq_number,
        ]);

        return back()->with('status', "Quotation {$quoteNumber} created for RFQ {$rfqRow->rfq_number}.");
    }

    public function updateQuotationStatus(Request $request, int $quotation): RedirectResponse
    {
        $data = $request->validate([
            'status' => ['required', 'in:draft,sent,accepted,rejected,expired'],
            'notes' => ['nullable', 'string', 'max:3000'],
        ]);

        $row = DB::table('quotations')->where('id', $quotation)->first();
        if (! $row) {
            return back()->with('error', 'Quotation not found.');
        }

        $payload = [
            'status' => $data['status'],
            'notes' => $data['notes'] ?? $row->notes,
            'updated_at' => now(),
        ];
        if ($data['status'] === 'sent' && ! $row->sent_at) {
            $payload['sent_at'] = now();
        }
        if ($data['status'] === 'accepted' && ! $row->accepted_at) {
            $payload['accepted_at'] = now();
        }

        DB::table('quotations')->where('id', $quotation)->update($payload);

        $this->auditAdminAction($request, 'quotation_status_updated', 'quotations', $quotation, [
            'quote_number' => $row->quote_number,
            'from' => $row->status,
            'to' => $data['status'],
            'notes' => $data['notes'] ?? null,
        ]);

        return back()->with('status', "Quotation {$row->quote_number}: {$row->status} → {$data['status']}.");
    }

    public function storeQuotationItem(Request $request, int $quotation): RedirectResponse
    {
        $data = $request->validate([
            'sku' => ['nullable', 'string', 'max:120'],
            'name' => ['required', 'string', 'max:255'],
            'quantity' => ['required', 'numeric', 'min:0.001'],
            'unit_price' => ['required', 'numeric', 'min:0'],
            'tax_amount' => ['nullable', 'numeric', 'min:0'],
        ]);

        abort_unless(DB::table('quotations')->where('id', $quotation)->exists(), 404);

        $quantity = (float) $data['quantity'];
        $unitPrice = (float) $data['unit_price'];
        $tax = (float) ($data['tax_amount'] ?? 0);
        DB::table('quotation_items')->insert([
            'quotation_id' => $quotation,
            'sku' => $data['sku'] ?? null,
            'name' => $data['name'],
            'quantity' => $quantity,
            'unit_price' => $unitPrice,
            'tax_amount' => $tax,
            'line_total' => round(($quantity * $unitPrice) + $tax, 2),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $this->recalculateQuotation($quotation);
        $this->auditAdminAction($request, 'quotation_item_created', 'quotation_items', null, ['quotation_id' => $quotation, 'name' => $data['name']]);

        return back()->with('status', 'Quotation line added.');
    }

    public function deleteQuotationItem(Request $request, int $quotation, int $item): RedirectResponse
    {
        DB::table('quotation_items')->where('quotation_id', $quotation)->where('id', $item)->delete();
        $this->recalculateQuotation($quotation);
        $this->auditAdminAction($request, 'quotation_item_deleted', 'quotation_items', $item, ['quotation_id' => $quotation]);

        return back()->with('status', 'Quotation line deleted.');
    }

    public function storeSupportTicket(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'subject' => ['required', 'string', 'max:255'],
            'description' => ['required', 'string', 'max:5000'],
            'priority' => ['required', 'in:low,medium,high,urgent'],
            'category' => ['nullable', 'string', 'max:80'],
            'customer_id' => ['nullable', 'integer', 'exists:customers,id'],
            'assigned_to' => ['nullable', 'integer', 'exists:users,id'],
            'channel' => ['nullable', 'string', 'max:80'],
        ]);

        $ticketNumber = 'SUP-'.now()->format('Ymd-His').'-'.random_int(100, 999);
        $ticketId = DB::table('support_tickets')->insertGetId([
            'ticket_number' => $ticketNumber,
            'customer_id' => $data['customer_id'] ?? null,
            'user_id' => $request->user()?->id,
            'subject' => $data['subject'],
            'description' => $data['description'],
            'priority' => $data['priority'],
            'status' => 'open',
            'category' => $data['category'] ?? 'general',
            'assigned_to' => $data['assigned_to'] ?? null,
            'metadata' => json_encode([
                'created_via' => 'admin.web',
                'channel' => $data['channel'] ?? 'admin',
                'ai_handoff' => false,
            ]),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::table('support_ticket_messages')->insert([
            'support_ticket_id' => $ticketId,
            'user_id' => $request->user()?->id,
            'sender_type' => 'admin',
            'message' => $data['description'],
            'metadata' => json_encode(['event' => 'ticket.created']),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $this->auditAdminAction($request, 'support_ticket_created', 'support_tickets', $ticketId, [
            'ticket_number' => $ticketNumber,
            'subject' => $data['subject'],
        ]);

        return back()->with('status', "Support ticket {$ticketNumber} created.");
    }

    public function updateSupportTicket(Request $request, int $ticket): RedirectResponse
    {
        $data = $request->validate([
            'status' => ['required', 'in:open,in_progress,waiting_customer,resolved,closed'],
            'priority' => ['required', 'in:low,medium,high,urgent'],
            'assigned_to' => ['nullable', 'integer', 'exists:users,id'],
            'resolution_notes' => ['nullable', 'string', 'max:3000'],
            'ai_handoff' => ['nullable', 'boolean'],
        ]);

        $row = DB::table('support_tickets')->where('id', $ticket)->first();
        if (! $row) {
            return back()->with('error', 'Support ticket not found.');
        }

        $meta = $this->mergeJsonMeta($row->metadata ?? null, [
            'ai_handoff' => (bool) ($data['ai_handoff'] ?? false),
            'last_admin_update_by' => $request->user()?->id,
            'last_admin_update_at' => now()->toIso8601String(),
        ]);

        DB::table('support_tickets')->where('id', $ticket)->update([
            'status' => $data['status'],
            'priority' => $data['priority'],
            'assigned_to' => $data['assigned_to'] ?? null,
            'resolution_notes' => $data['resolution_notes'] ?? null,
            'resolved_at' => in_array($data['status'], ['resolved', 'closed'], true) ? now() : null,
            'metadata' => $meta,
            'updated_at' => now(),
        ]);

        $this->auditAdminAction($request, 'support_ticket_updated', 'support_tickets', $ticket, [
            'ticket_number' => $row->ticket_number,
            'from_status' => $row->status,
            'to_status' => $data['status'],
        ]);

        return back()->with('status', "Support ticket {$row->ticket_number} updated.");
    }

    public function storeSupportTicketMessage(Request $request, int $ticket): RedirectResponse
    {
        $data = $request->validate([
            'message' => ['required', 'string', 'max:5000'],
            'sender_type' => ['required', 'in:admin,customer,seller,system,ai'],
            'mark_status' => ['nullable', 'in:open,in_progress,waiting_customer,resolved,closed'],
        ]);

        $row = DB::table('support_tickets')->where('id', $ticket)->first();
        if (! $row) {
            return back()->with('error', 'Support ticket not found.');
        }

        DB::transaction(function () use ($request, $ticket, $row, $data) {
            DB::table('support_ticket_messages')->insert([
                'support_ticket_id' => $ticket,
                'user_id' => $request->user()?->id,
                'sender_type' => $data['sender_type'],
                'message' => $data['message'],
                'metadata' => json_encode(['event' => 'message.created']),
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            DB::table('support_tickets')->where('id', $ticket)->update([
                'status' => $data['mark_status'] ?? $row->status,
                'updated_at' => now(),
            ]);
        });

        $this->auditAdminAction($request, 'support_ticket_message_added', 'support_tickets', $ticket, [
            'ticket_number' => $row->ticket_number,
            'sender_type' => $data['sender_type'],
        ]);

        return back()->with('status', "Message added to {$row->ticket_number}.");
    }

    // ---- Users ----------------------------------------------------------------

    public function sendPasswordReset(int $user): RedirectResponse
    {
        $email = DB::table('users')->where('id', $user)->value('email');
        if (! $email) {
            return back()->with('error', 'User not found.');
        }

        \Illuminate\Support\Facades\Password::sendResetLink(['email' => $email]);

        return back()->with('status', "Password reset link sent to user #{$user}.");
    }

    // ---- Region stock visibility ---------------------------------------------

    public function toggleStockRule(int $rule): RedirectResponse
    {
        $row = DB::table('region_stock_visibilities')->where('id', $rule)->first();
        if (! $row) {
            return back()->with('error', 'Visibility rule not found.');
        }

        DB::table('region_stock_visibilities')->where('id', $rule)
            ->update(['is_visible' => ! $row->is_visible, 'updated_at' => now()]);

        return back()->with('status', "Rule #{$rule} " . ($row->is_visible ? 'hidden' : 'visible') . '.');
    }

    // ---- Onboarding applications --------------------------------------------

    public function updateApplicationStatus(Request $request, string $type, int $id): RedirectResponse
    {
        abort_unless(in_array($type, ['seller', 'distributor'], true), 404);
        $table = $type . '_applications';

        // Same whitelist as the Onboarding module's ApplicationStatusRequest.
        $data = $request->validate([
            'status' => ['required', 'in:pending,contacted,approved_for_onboarding,rejected,archived'],
            'admin_notes' => ['nullable', 'string', 'max:3000'],
        ]);

        $updated = DB::table($table)->where('id', $id)->update([
            'status' => $data['status'],
            'admin_notes' => $data['admin_notes'] ?? DB::raw('admin_notes'),
            'reviewed_at' => now(),
            'updated_at' => now(),
        ]);

        return $updated
            ? back()->with('status', ucfirst($type) . " application #{$id} → {$data['status']}.")
            : back()->with('error', 'Application not found.');
    }

    // ---- Expenses ----------------------------------------------------------

    public function storeExpense(Request $request, DocumentNumberService $docs): RedirectResponse
    {
        $data = $request->validate([
            'category' => ['required', 'string', 'max:64'],
            'amount' => ['required', 'numeric', 'min:0'],
            'currency' => ['nullable', 'string', 'size:3'],
            'expense_date' => ['required', 'date'],
            'description' => ['nullable', 'string', 'max:255'],
        ]);

        DB::table('expenses')->insert([
            'expense_number' => $docs->next('expense', 'EXP-'),
            'category' => $data['category'],
            'amount' => $data['amount'],
            'tax_amount' => 0,
            'currency' => strtoupper($data['currency'] ?? 'USD'),
            'status' => 'recorded',
            'expense_date' => $data['expense_date'],
            'description' => $data['description'] ?? null,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        return back()->with('status', 'Expense recorded.');
    }

    private function auditAdminAction(Request $request, string $action, string $table, ?int $id, array $values): void
    {
        try {
            DB::table('audit_logs')->insert([
                'user_id' => $request->user()?->id,
                'action' => $action,
                'model_type' => $table,
                'model_id' => $id,
                'model_display_name' => $values['key'] ?? $values['url_path'] ?? $values['from_path'] ?? $values['path'] ?? null,
                'old_values' => null,
                'new_values' => json_encode($values),
                'ip_address' => $request->ip(),
                'user_agent' => substr((string) $request->userAgent(), 0, 1000),
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        } catch (\Throwable) {
            // Audit logging must not block admin operations.
        }
    }

    private function vendorAudit(Request $request, int $vendorId, string $action, array $values): void
    {
        try {
            DB::table('vendor_audit_logs')->insert([
                'vendor_id' => $vendorId,
                'user_id' => $request->user()?->id,
                'action' => $action,
                'entity_type' => 'vendor',
                'entity_id' => $vendorId,
                'old_values' => null,
                'new_values' => json_encode($values),
                'ip_address' => $request->ip(),
                'user_agent' => substr((string) $request->userAgent(), 0, 1000),
                'notes' => 'Changed via admin console',
                'occurred_at' => now(),
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        } catch (\Throwable) {
            // Seller audit logging must not block admin operations.
        }
    }

    private function jsonOrEmpty(?string $value): string
    {
        if (! $value) {
            return json_encode([]);
        }

        json_decode($value);

        return json_last_error() === JSON_ERROR_NONE ? $value : json_encode(['raw' => $value]);
    }

    private function mergeJsonMeta(?string $current, array $values): string
    {
        $decoded = $current ? json_decode($current, true) : [];
        if (! is_array($decoded)) {
            $decoded = ['original_meta' => $current];
        }

        return json_encode(array_filter(array_merge($decoded, $values), fn ($value) => $value !== null));
    }

    private function recalculateQuotation(int $quotation): void
    {
        $items = DB::table('quotation_items')->where('quotation_id', $quotation)->get();
        $subtotal = $items->sum(fn ($item) => ((float) $item->quantity) * ((float) $item->unit_price));
        $tax = $items->sum(fn ($item) => (float) $item->tax_amount);
        $shipping = (float) DB::table('quotations')->where('id', $quotation)->value('shipping_total');

        DB::table('quotations')->where('id', $quotation)->update([
            'subtotal' => round($subtotal, 2),
            'tax_total' => round($tax, 2),
            'grand_total' => round($subtotal + $tax + $shipping, 2),
            'updated_at' => now(),
        ]);
    }
}
