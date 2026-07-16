<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Jobs\RebuildApprovedImportSearchIndexJob;
use App\Models\Bom\BomImport;
use App\Models\Bom\BomImportLine;
use App\Models\Marketplace\VendorProduct;
use App\Models\Marketplace\Product;
use App\Services\Bom\BomImportService;
use App\Models\Marketplace\ProductSeoMeta;
use App\Services\Erp\DocumentNumberService;
use App\Services\Inventory\TransferService;
use App\Services\Catalog\CatalogSearchRebuildService;
use App\Services\Catalog\JlcpcbQualifiedPublicationService;
use App\Services\Product\ProductApprovalService;
use App\Services\Marketing\OrderNotificationService;
use App\Services\Seo\CatalogSeoTemplateService;
use App\Support\ProductLifecycle;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schema;
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
            'image_path' => ['nullable', 'string', 'max:500'],
            'media_asset_id' => ['nullable', 'integer', 'exists:admin_media_assets,id'],
            'sort_order' => ['nullable', 'integer', 'min:0'],
            'is_active' => ['nullable', 'boolean'],
            'is_featured' => ['nullable', 'boolean'],
            'seo_title' => ['nullable', 'string', 'max:255'],
            'seo_description' => ['nullable', 'string', 'max:500'],
            'country_visibility' => ['nullable', 'string', 'max:255'],
            'lms_topic' => ['nullable', 'string', 'max:255'],
        ]);

        $slug = $data['slug'] ?: Str::slug($data['name']);
        $parentId = isset($data['parent_id']) ? (int) $data['parent_id'] : null;
        if (! empty($data['id']) && $parentId !== null) {
            $cursor = $parentId;
            while ($cursor > 0) {
                if ($cursor === (int) $data['id']) {
                    return back()->with('error', 'A category cannot be its own parent or descendant.');
                }
                $cursor = (int) (DB::table('product_categories')->where('id', $cursor)->value('parent_id') ?? 0);
            }
        }
        $mediaAsset = ! empty($data['media_asset_id'])
            ? DB::table('admin_media_assets')->where('id', $data['media_asset_id'])->first()
            : null;
        $mediaUrl = $mediaAsset ? Storage::disk($mediaAsset->disk ?: 'public')->url($mediaAsset->path) : null;

        $payload = [
            'parent_id' => $parentId,
            'name' => $data['name'],
            'slug' => $slug,
            'description' => $data['description'] ?? null,
            'icon_path' => $data['icon_path'] ?? $mediaUrl,
            'image_path' => $data['image_path'] ?? $mediaUrl,
            'sort_order' => $data['sort_order'] ?? 100,
            'is_active' => (bool) ($data['is_active'] ?? false),
            'is_featured' => (bool) ($data['is_featured'] ?? false),
            'seo_meta' => json_encode([
                'title' => $data['seo_title'] ?? null,
                'description' => $data['seo_description'] ?? null,
                'country_visibility' => $data['country_visibility'] ?? null,
                'lms_topic' => $data['lms_topic'] ?? null,
                'source_notes' => 'manual admin metadata',
                'confidence_level' => 'manual',
                'last_updated' => now()->toDateTimeString(),
                'disclaimer' => 'Advisory only',
            ]),
            'marketplace_visibility' => json_encode([
                'country_visibility' => $data['country_visibility'] ?? null,
                'media_asset_id' => $data['media_asset_id'] ?? null,
            ]),
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
            if ($parentId !== null && Schema::hasTable('category_creation_audits')) {
                DB::table('category_creation_audits')->insert([
                    'category_id' => $id, 'parent_category_id' => $parentId, 'source_name' => 'admin',
                    'imported_at' => now(), 'confidence_level' => 'manual', 'original_raw_value' => $data['name'],
                    'normalized_value' => $slug, 'created_by_type' => 'admin', 'created_at' => now(), 'updated_at' => now(),
                ]);
            }
        }

        Cache::forget('categories:tree');
        $this->auditAdminAction($request, 'category_'.$verb, 'product_categories', $id, $payload + ['id' => $id]);

        return back()->with('status', "Category {$verb}.");
    }

    public function storeCategoryLmsLink(Request $request, int $category): RedirectResponse
    {
        $data = $request->validate([
            'title' => ['required', 'string', 'max:190'],
            'lms_course_id' => ['nullable', 'integer', 'exists:lms_courses,id'],
            'lms_project_id' => ['nullable', 'integer', 'exists:lms_projects,id'],
            'relation_type' => ['required', 'string', 'max:80'],
            'notes' => ['nullable', 'string', 'max:1000'],
        ]);

        if (! DB::table('product_categories')->where('id', $category)->exists()) {
            return back()->with('error', 'Category not found.');
        }

        $id = DB::table('category_lms_links')->insertGetId([
            'product_category_id' => $category,
            'lms_course_id' => $data['lms_course_id'] ?? null,
            'lms_project_id' => $data['lms_project_id'] ?? null,
            'title' => $data['title'],
            'relation_type' => $data['relation_type'],
            'notes' => $data['notes'] ?? null,
            'is_active' => true,
            'metadata' => json_encode(['saved_via' => 'admin.web']),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $this->auditAdminAction($request, 'category_lms_link_created', 'category_lms_links', $id, ['product_category_id' => $category, 'title' => $data['title']]);

        return back()->with('status', 'Category LMS link added.');
    }

    public function deleteCategoryLmsLink(Request $request, int $category, int $link): RedirectResponse
    {
        DB::table('category_lms_links')
            ->where('product_category_id', $category)
            ->where('id', $link)
            ->update(['is_active' => false, 'updated_at' => now()]);

        $this->auditAdminAction($request, 'category_lms_link_deactivated', 'category_lms_links', $link, ['product_category_id' => $category]);

        return back()->with('status', 'Category LMS link deactivated.');
    }

    public function storeCategorySpecTemplate(Request $request, int $category): RedirectResponse
    {
        abort_unless(Schema::hasTable('category_spec_templates'), 404);
        abort_unless(DB::table('product_categories')->where('id', $category)->exists(), 404);

        $data = $request->validate([
            'id' => ['nullable', 'integer', 'exists:category_spec_templates,id'],
            'name' => ['required', 'string', 'max:190'],
            'description' => ['nullable', 'string', 'max:1000'],
            'is_required' => ['nullable', 'boolean'],
            'sort_order' => ['nullable', 'integer', 'min:0'],
        ]);

        $payload = [
            'category_id' => $category,
            'name' => $data['name'],
            'description' => $data['description'] ?? null,
            'is_required' => (bool) ($data['is_required'] ?? false),
            'sort_order' => $data['sort_order'] ?? 0,
            'metadata' => json_encode([
                'source_notes' => 'Manual admin category spec template',
                'confidence_level' => 'manual',
                'last_updated' => now()->toISOString(),
                'advisory' => 'Advisory only',
            ]),
            'updated_at' => now(),
        ];

        if (! empty($data['id'])) {
            DB::table('category_spec_templates')->where('id', $data['id'])->where('category_id', $category)->update($payload);
            $id = (int) $data['id'];
            $action = 'category_spec_template_updated';
        } else {
            $payload['created_at'] = now();
            $id = DB::table('category_spec_templates')->insertGetId($payload);
            $action = 'category_spec_template_created';
        }

        $this->auditAdminAction($request, $action, 'category_spec_templates', $id, ['category_id' => $category, 'name' => $data['name']]);

        return back()->with('status', 'Spec template saved.');
    }

    public function deleteCategorySpecTemplate(Request $request, int $category, int $template): RedirectResponse
    {
        DB::table('category_spec_templates')->where('category_id', $category)->where('id', $template)->delete();
        $this->auditAdminAction($request, 'category_spec_template_deleted', 'category_spec_templates', $template, ['category_id' => $category]);

        return back()->with('status', 'Spec template deleted.');
    }

    public function storeCategorySpecField(Request $request, int $category, int $template): RedirectResponse
    {
        abort_unless(Schema::hasTable('spec_template_fields'), 404);
        abort_unless(DB::table('category_spec_templates')->where('category_id', $category)->where('id', $template)->exists(), 404);

        $data = $request->validate([
            'field_name' => ['required', 'string', 'max:120'],
            'field_label' => ['required', 'string', 'max:190'],
            'field_type' => ['required', 'string', 'max:40'],
            'unit' => ['nullable', 'string', 'max:80'],
            'options' => ['nullable', 'string', 'max:1000'],
            'validation_rules' => ['nullable', 'string', 'max:255'],
            'help_text' => ['nullable', 'string', 'max:1000'],
            'is_required' => ['nullable', 'boolean'],
            'sort_order' => ['nullable', 'integer', 'min:0'],
        ]);

        $id = DB::table('spec_template_fields')->insertGetId([
            'template_id' => $template,
            'field_name' => Str::slug($data['field_name'], '_'),
            'field_label' => $data['field_label'],
            'field_type' => $data['field_type'],
            'unit' => $data['unit'] ?? null,
            'options' => $this->jsonArrayFromTextarea($data['options'] ?? null),
            'validation_rules' => $data['validation_rules'] ?? null,
            'help_text' => $data['help_text'] ?? null,
            'is_required' => (bool) ($data['is_required'] ?? false),
            'sort_order' => $data['sort_order'] ?? 0,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $this->auditAdminAction($request, 'category_spec_field_created', 'spec_template_fields', $id, ['category_id' => $category, 'template_id' => $template]);

        return back()->with('status', 'Spec field added.');
    }

    public function deleteCategorySpecField(Request $request, int $category, int $template, int $field): RedirectResponse
    {
        DB::table('spec_template_fields')->where('template_id', $template)->where('id', $field)->delete();
        $this->auditAdminAction($request, 'category_spec_field_deleted', 'spec_template_fields', $field, ['category_id' => $category, 'template_id' => $template]);

        return back()->with('status', 'Spec field deleted.');
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

    public function moveCategory(Request $request, int $category): RedirectResponse
    {
        $data = $request->validate(['parent_id' => ['required', 'integer', 'different:category', 'exists:product_categories,id']]);
        $node = DB::table('product_categories')->where('id', $category)->first();
        if (! $node) {
            return back()->with('error', 'Category not found.');
        }
        $parentId = (int) $data['parent_id'];
        $cursor = $parentId;
        while ($cursor > 0) {
            if ($cursor === $category) {
                return back()->with('error', 'A category cannot be moved into its own descendant.');
            }
            $cursor = (int) (DB::table('product_categories')->where('id', $cursor)->value('parent_id') ?? 0);
        }
        DB::table('product_categories')->where('id', $category)->update(['parent_id' => $parentId, 'updated_at' => now()]);
        Cache::forget('categories:tree');
        $this->auditAdminAction($request, 'category_moved', 'product_categories', $category, ['old_parent_id' => $node->parent_id, 'parent_id' => $parentId]);

        return back()->with('status', 'Category moved. Product associations and category ID were preserved.');
    }

    public function mergeCategory(Request $request, int $category): RedirectResponse
    {
        $data = $request->validate(['target_category_id' => ['required', 'integer', 'different:category', 'exists:product_categories,id']]);
        $target = (int) $data['target_category_id'];
        if ($target === $category) {
            return back()->with('error', 'Choose a different canonical category to merge into.');
        }
        DB::transaction(function () use ($category, $target, $request): void {
            $source = DB::table('product_categories')->lockForUpdate()->find($category);
            $destination = DB::table('product_categories')->lockForUpdate()->find($target);
            if (! $source || ! $destination) {
                throw new \InvalidArgumentException('Category merge target was not found.');
            }
            DB::table('products')->where('category_id', $category)->update(['category_id' => $target, 'updated_at' => now()]);
            DB::table('product_categories')->where('parent_id', $category)->update(['parent_id' => $target, 'updated_at' => now()]);
            DB::table('product_categories')->where('id', $category)->update(['is_active' => false, 'updated_at' => now()]);
            $this->auditAdminAction($request, 'category_merged', 'product_categories', $category, ['target_category_id' => $target]);
        });
        Cache::forget('categories:tree');

        return back()->with('status', 'Category merged. Products were reassigned and the source category was retained inactive for audit.');
    }

    public function storeCategorySynonym(Request $request, int $category): RedirectResponse
    {
        abort_unless(Schema::hasTable('category_synonyms'), 404);
        $data = $request->validate(['synonym' => ['required', 'string', 'max:190'], 'confidence' => ['nullable', 'numeric', 'min:0', 'max:1']]);
        $normalized = trim(preg_replace('/\s+/', ' ', strtolower((string) preg_replace('/[^a-z0-9]+/i', ' ', $data['synonym']))) ?? '');
        if ($normalized === '') {
            return back()->with('error', 'Synonym must contain letters or numbers.');
        }
        DB::table('category_synonyms')->updateOrInsert(['normalized_synonym' => $normalized], [
            'category_id' => $category, 'synonym' => $data['synonym'], 'source' => 'admin',
            'confidence' => $data['confidence'] ?? 1, 'created_at' => now(), 'updated_at' => now(),
        ]);
        $this->auditAdminAction($request, 'category_synonym_saved', 'category_synonyms', null, ['category_id' => $category, 'normalized_synonym' => $normalized]);

        return back()->with('status', 'Category synonym saved.');
    }

    public function deleteCategorySynonym(Request $request, int $category, int $synonym): RedirectResponse
    {
        DB::table('category_synonyms')->where('id', $synonym)->where('category_id', $category)->delete();
        $this->auditAdminAction($request, 'category_synonym_deleted', 'category_synonyms', $synonym, ['category_id' => $category]);

        return back()->with('status', 'Category synonym deleted.');
    }

    public function reviewCategoryImport(Request $request, int $review): RedirectResponse
    {
        abort_unless(Schema::hasTable('category_import_reviews'), 404);
        $data = $request->validate([
            'decision' => ['required', 'in:approved,rejected'],
            'category_id' => ['nullable', 'integer', 'exists:product_categories,id'],
        ]);
        if ($data['decision'] === 'approved' && empty($data['category_id'])) {
            return back()->with('error', 'Choose a canonical category before approving a mapping.');
        }
        $row = DB::table('category_import_reviews')->where('id', $review)->first();
        if (! $row) {
            return back()->with('error', 'Category import review not found.');
        }
        DB::transaction(function () use ($data, $row, $review): void {
            DB::table('category_import_reviews')->where('id', $review)->update([
                'proposed_category_id' => $data['category_id'] ?? null,
                'status' => $data['decision'], 'reviewed_at' => now(), 'updated_at' => now(),
            ]);
            if ($data['decision'] === 'approved' && $row->catalog_source_id && $row->source_key) {
                DB::table('supplier_category_mappings')->updateOrInsert(
                    ['catalog_source_id' => $row->catalog_source_id, 'source_category_key' => $row->source_key],
                    ['category_id' => $data['category_id'], 'confidence' => 1, 'mapping_status' => 'approved_manual', 'reviewed_at' => now(), 'created_at' => now(), 'updated_at' => now()],
                );
            }
        });
        $this->auditAdminAction($request, 'category_import_reviewed', 'category_import_reviews', $review, $data);

        return back()->with('status', 'Category import review updated. Future imports will use approved mappings.');
    }

    private function jsonArrayFromTextarea(?string $value): ?string
    {
        $lines = collect(preg_split('/\r\n|\r|\n/', (string) $value))
            ->map(fn ($line) => trim($line))
            ->filter()
            ->values();

        return $lines->isEmpty() ? null : json_encode($lines->all());
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
            'lifecycle_status' => ['nullable', 'string', 'max:60'],
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

        if (array_key_exists('lifecycle_status', $data)) {
            $payload['lifecycle_status'] = ProductLifecycle::normalize($data['lifecycle_status']);
        }

        $existing = null;
        if (! empty($data['id'])) {
            $existing = DB::table('products')->where('id', $data['id'])->first(['lifecycle_status']);
            DB::table('products')->where('id', $data['id'])->update($payload);
            $id = (int) $data['id'];
            $verb = 'updated';
        } else {
            $payload['created_by'] = $request->user()?->id;
            $payload['created_at'] = now();
            $id = DB::table('products')->insertGetId($payload);
            $verb = 'created';
        }

        $this->auditAdminAction($request, 'product_'.$verb, 'products', $id, [
            'sku' => $data['sku'],
            'name' => $data['name'],
            'previous_lifecycle_status' => $existing->lifecycle_status ?? null,
            'lifecycle_status' => $payload['lifecycle_status'] ?? ($existing->lifecycle_status ?? null),
        ]);

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

    public function updateProductLifecycle(Request $request, int $product): RedirectResponse
    {
        if (! Schema::hasColumn('products', 'lifecycle_status')) {
            return back()->with('error', 'Product lifecycle status is not available in this environment.');
        }

        $data = $request->validate([
            'lifecycle_status' => ['nullable', 'string', 'max:60'],
        ]);
        $row = DB::table('products')->where('id', $product)->first(['id', 'lifecycle_status', 'sku', 'name']);

        if (! $row) {
            return back()->with('error', 'Product not found.');
        }

        $lifecycle = ProductLifecycle::normalize($data['lifecycle_status'] ?? null);
        if ($lifecycle === $row->lifecycle_status) {
            return back()->with('status', 'Product lifecycle status is unchanged.');
        }

        DB::table('products')->where('id', $product)->update([
            'lifecycle_status' => $lifecycle,
            'updated_at' => now(),
        ]);

        $this->auditAdminAction($request, 'product_lifecycle_updated', 'products', $product, [
            'sku' => $row->sku,
            'name' => $row->name,
            'previous_lifecycle_status' => $row->lifecycle_status,
            'lifecycle_status' => $lifecycle,
        ]);

        return back()->with('status', 'Product lifecycle status updated.');
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

    public function storeProductRegionalStock(Request $request, int $product): RedirectResponse
    {
        $data = $request->validate([
            'inventory_stock_id' => ['nullable', 'integer', 'exists:inventory_stocks,id'],
            'warehouse_id' => ['required', 'integer', 'exists:warehouses,id'],
            'country_id' => ['nullable', 'integer', 'exists:countries,id'],
            'quantity_available' => ['required', 'integer', 'min:0'],
            'quantity_reserved' => ['nullable', 'integer', 'min:0'],
            'quantity_incoming' => ['nullable', 'integer', 'min:0'],
            'reorder_point' => ['nullable', 'integer', 'min:0'],
            'reorder_quantity' => ['nullable', 'integer', 'min:0'],
            'unit_cost' => ['nullable', 'numeric', 'min:0'],
            'status' => ['required', 'string', 'max:40'],
            'backorder_allowed' => ['nullable', 'boolean'],
            'quote_only' => ['nullable', 'boolean'],
            'notes' => ['nullable', 'string', 'max:1000'],
        ]);

        $productRow = DB::table('products')->where('id', $product)->first();
        if (! $productRow) {
            return back()->with('error', 'Product not found.');
        }

        $stock = null;
        if (! empty($data['inventory_stock_id'])) {
            $stock = DB::table('inventory_stocks')
                ->where('id', $data['inventory_stock_id'])
                ->where('product_id', $product)
                ->first();
        }

        if (! $stock) {
            $stock = DB::table('inventory_stocks')
                ->where('product_id', $product)
                ->where('warehouse_id', $data['warehouse_id'])
                ->where(function ($query) use ($data) {
                    if (! empty($data['country_id'])) {
                        $query->where('country_id', $data['country_id']);
                    } else {
                        $query->whereNull('country_id');
                    }
                })
                ->first();
        }

        $before = (int) ($stock->quantity_available ?? 0);
        $reserved = (int) ($data['quantity_reserved'] ?? ($stock->quantity_reserved ?? 0));
        $available = (int) $data['quantity_available'];
        $onHand = $available + $reserved + (int) ($stock->quantity_damaged ?? 0);

        $payload = [
            'product_id' => $product,
            'warehouse_id' => $data['warehouse_id'],
            'country_id' => $data['country_id'] ?? null,
            'vendor_id' => $productRow->vendor_id ?? null,
            'sku' => $productRow->sku ?? null,
            'quantity_available' => $available,
            'quantity_reserved' => $reserved,
            'quantity_incoming' => (int) ($data['quantity_incoming'] ?? ($stock->quantity_incoming ?? 0)),
            'quantity_on_hand' => $onHand,
            'reorder_point' => (int) ($data['reorder_point'] ?? ($stock->reorder_point ?? $productRow->low_stock_threshold ?? 5)),
            'reorder_quantity' => (int) ($data['reorder_quantity'] ?? ($stock->reorder_quantity ?? 0)),
            'unit_cost' => $data['unit_cost'] ?? ($stock->unit_cost ?? null),
            'backorder_allowed' => (bool) ($data['backorder_allowed'] ?? false),
            'quote_only' => (bool) ($data['quote_only'] ?? false),
            'status' => $data['status'],
            'is_active' => $data['status'] !== 'inactive',
            'last_movement_at' => now(),
            'metadata' => json_encode(['saved_via' => 'admin.product_detail', 'note' => $data['notes'] ?? null]),
            'updated_at' => now(),
        ];

        if ($stock) {
            DB::table('inventory_stocks')->where('id', $stock->id)->update($payload);
            $stockId = (int) $stock->id;
        } else {
            $payload += ['quantity_damaged' => 0, 'created_at' => now()];
            $stockId = DB::table('inventory_stocks')->insertGetId($payload);
        }

        DB::table('inventory_movements')->insert([
            'inventory_stock_id' => $stockId,
            'product_id' => $product,
            'warehouse_id' => $data['warehouse_id'],
            'vendor_id' => $productRow->vendor_id ?? null,
            'movement_type' => $stock ? 'regional_stock_update' : 'regional_stock_create',
            'quantity_change' => $available - $before,
            'quantity_before' => $before,
            'quantity_after' => $available,
            'reference_type' => 'admin_product_regional_stock',
            'reference_id' => $stockId,
            'notes' => $data['notes'] ?? 'Regional stock saved via product admin',
            'user_id' => $request->user()?->id,
            'metadata' => json_encode(['country_id' => $data['country_id'] ?? null, 'reorder_point' => $payload['reorder_point']]),
            'occurred_at' => now(),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $totalAvailable = (int) DB::table('inventory_stocks')
            ->where('product_id', $product)
            ->where('is_active', true)
            ->sum('quantity_available');

        DB::table('products')->where('id', $product)->update([
            'stock_quantity' => $totalAvailable,
            'low_stock_threshold' => $payload['reorder_point'],
            'updated_at' => now(),
        ]);

        $this->auditAdminAction($request, 'product_regional_stock_saved', 'inventory_stocks', $stockId, $data + ['product_id' => $product]);

        return back()->with('status', 'Regional inventory saved.');
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

    public function storeAdvancedProductSpec(Request $request, int $product): RedirectResponse
    {
        abort_unless(Schema::hasTable('product_specifications') && Schema::hasTable('spec_template_fields'), 404);
        abort_unless(DB::table('products')->where('id', $product)->exists(), 404);

        $data = $request->validate([
            'template_field_id' => ['required', 'integer', 'exists:spec_template_fields,id'],
            'value' => ['required', 'string', 'max:4000'],
            'unit_override' => ['nullable', 'string', 'max:80'],
            'is_visible' => ['nullable', 'boolean'],
        ]);

        DB::table('product_specifications')->updateOrInsert(
            [
                'product_id' => $product,
                'template_field_id' => $data['template_field_id'],
            ],
            [
                'value' => $data['value'],
                'unit_override' => $data['unit_override'] ?? null,
                'is_visible' => (bool) ($data['is_visible'] ?? true),
                'updated_at' => now(),
                'created_at' => now(),
            ]
        );

        $this->auditAdminAction($request, 'advanced_product_spec_saved', 'product_specifications', $data['template_field_id'], ['product_id' => $product]);

        return back()->with('status', 'Advanced product specification saved.');
    }

    public function deleteAdvancedProductSpec(Request $request, int $product, int $spec): RedirectResponse
    {
        DB::table('product_specifications')->where('product_id', $product)->where('id', $spec)->delete();
        $this->auditAdminAction($request, 'advanced_product_spec_deleted', 'product_specifications', $spec, ['product_id' => $product]);

        return back()->with('status', 'Advanced product specification deleted.');
    }

    public function updateProductReview(Request $request, int $product, int $review): RedirectResponse
    {
        if (! Schema::hasTable('product_reviews')) {
            return back()->with('error', 'Product reviews table is not available.');
        }

        $data = $request->validate([
            'status' => ['required', 'in:pending,approved,rejected,hidden'],
            'moderation_note' => ['nullable', 'string', 'max:1000'],
        ]);

        $row = DB::table('product_reviews')
            ->where('id', $review)
            ->where('product_id', $product)
            ->first();

        if (! $row) {
            return back()->with('error', 'Review not found.');
        }

        DB::table('product_reviews')->where('id', $review)->update([
            'status' => $data['status'],
            'moderated_by' => $request->user()?->id,
            'moderated_at' => now(),
            'moderation_note' => $data['moderation_note'] ?? null,
            'updated_at' => now(),
        ]);

        $this->auditAdminAction($request, 'product_review_'.$data['status'], 'product_reviews', $review, [
            'product_id' => $product,
            'previous_status' => $row->status,
            'status' => $data['status'],
        ]);

        return back()->with('status', 'Review moderation saved.');
    }

    public function storeMarketplaceProductPrice(Request $request, int $product): RedirectResponse
    {
        abort_unless(Schema::hasTable('marketplace_product_prices'), 404);
        abort_unless(DB::table('products')->where('id', $product)->exists(), 404);

        $data = $request->validate([
            'id' => ['nullable', 'integer', 'exists:marketplace_product_prices,id'],
            'marketplace_id' => ['required', 'integer', 'exists:marketplaces,id'],
            'base_price' => ['required', 'numeric', 'min:0'],
            'sale_price' => ['nullable', 'numeric', 'min:0'],
            'cost_price' => ['nullable', 'numeric', 'min:0'],
            'currency_code' => ['required', 'string', 'max:3', 'exists:currencies,code'],
            'tax_rate' => ['nullable', 'numeric', 'min:0', 'max:100'],
            'is_tax_inclusive' => ['nullable', 'boolean'],
            'is_active' => ['nullable', 'boolean'],
        ]);

        $payload = [
            'product_id' => $product,
            'marketplace_id' => $data['marketplace_id'],
            'base_price' => $data['base_price'],
            'sale_price' => $data['sale_price'] ?? null,
            'cost_price' => $data['cost_price'] ?? null,
            'currency_code' => strtoupper($data['currency_code']),
            'tax_rate' => $data['tax_rate'] ?? 0,
            'is_tax_inclusive' => (bool) ($data['is_tax_inclusive'] ?? false),
            'is_active' => (bool) ($data['is_active'] ?? true),
            'updated_at' => now(),
        ];

        if (! empty($data['id'])) {
            DB::table('marketplace_product_prices')->where('id', $data['id'])->where('product_id', $product)->update($payload);
            $id = (int) $data['id'];
            $action = 'marketplace_product_price_updated';
        } else {
            $payload['created_at'] = now();
            $id = DB::table('marketplace_product_prices')->insertGetId($payload);
            $action = 'marketplace_product_price_created';
        }

        $this->auditAdminAction($request, $action, 'marketplace_product_prices', $id, ['product_id' => $product, 'marketplace_id' => $data['marketplace_id']]);

        return back()->with('status', 'Marketplace price saved.');
    }

    public function toggleMarketplaceProductPrice(Request $request, int $product, int $price): RedirectResponse
    {
        abort_unless(Schema::hasTable('marketplace_product_prices'), 404);

        $row = DB::table('marketplace_product_prices')->where('product_id', $product)->where('id', $price)->first();
        if (! $row) {
            return back()->with('error', 'Marketplace price not found.');
        }

        DB::table('marketplace_product_prices')->where('id', $price)->update(['is_active' => ! (bool) $row->is_active, 'updated_at' => now()]);
        $this->auditAdminAction($request, 'marketplace_product_price_toggled', 'marketplace_product_prices', $price, ['product_id' => $product, 'is_active' => ! (bool) $row->is_active]);

        return back()->with('status', 'Marketplace price status updated.');
    }

    public function storeVendorProductPrice(Request $request, int $product): RedirectResponse
    {
        abort_unless(Schema::hasTable('vendor_product_prices'), 404);
        abort_unless(DB::table('products')->where('id', $product)->exists(), 404);

        $data = $request->validate([
            'id' => ['nullable', 'integer', 'exists:vendor_product_prices,id'],
            'vendor_id' => ['required', 'integer', 'exists:vendors,id'],
            'selling_price' => ['required', 'numeric', 'min:0'],
            'min_price' => ['nullable', 'numeric', 'min:0'],
            'cost_price' => ['nullable', 'numeric', 'min:0'],
            'currency_code' => ['required', 'string', 'max:3', 'exists:currencies,code'],
            'is_active' => ['nullable', 'boolean'],
        ]);

        $payload = [
            'vendor_id' => $data['vendor_id'],
            'product_id' => $product,
            'cost_price' => $data['cost_price'] ?? null,
            'selling_price' => $data['selling_price'],
            'min_price' => $data['min_price'] ?? null,
            'currency_code' => strtoupper($data['currency_code']),
            'is_active' => (bool) ($data['is_active'] ?? true),
            'updated_at' => now(),
        ];

        if (! empty($data['id'])) {
            DB::table('vendor_product_prices')->where('id', $data['id'])->where('product_id', $product)->update($payload);
            $id = (int) $data['id'];
            $action = 'vendor_product_price_updated';
        } else {
            $payload['created_at'] = now();
            $id = DB::table('vendor_product_prices')->insertGetId($payload);
            $action = 'vendor_product_price_created';
        }

        $this->auditAdminAction($request, $action, 'vendor_product_prices', $id, ['product_id' => $product, 'vendor_id' => $data['vendor_id']]);

        return back()->with('status', 'Seller offer saved.');
    }

    public function toggleVendorProductPrice(Request $request, int $product, int $price): RedirectResponse
    {
        abort_unless(Schema::hasTable('vendor_product_prices'), 404);

        $row = DB::table('vendor_product_prices')->where('product_id', $product)->where('id', $price)->first();
        if (! $row) {
            return back()->with('error', 'Seller offer not found.');
        }

        DB::table('vendor_product_prices')->where('id', $price)->update(['is_active' => ! (bool) $row->is_active, 'updated_at' => now()]);
        $this->auditAdminAction($request, 'vendor_product_price_toggled', 'vendor_product_prices', $price, ['product_id' => $product, 'is_active' => ! (bool) $row->is_active]);

        return back()->with('status', 'Seller offer status updated.');
    }

    public function storeProductDocument(Request $request, int $product): RedirectResponse
    {
        $data = $request->validate([
            'title' => ['required', 'string', 'max:190'],
            'document_type' => ['required', 'string', 'max:80'],
            'media_asset_id' => ['nullable', 'integer', 'exists:admin_media_assets,id'],
            'file' => ['nullable', 'file', 'max:51200', 'mimes:jpg,jpeg,png,webp,pdf,csv,txt,zip'],
            'file_url' => ['nullable', 'string', 'max:1000'],
            'source_url' => ['nullable', 'string', 'max:1000'],
        ]);

        abort_unless(DB::table('products')->where('id', $product)->exists(), 404);

        $fileUrl = $data['file_url'] ?? null;
        $filePath = null;
        $mimeType = null;
        $fileSize = null;
        $mediaAssetId = $data['media_asset_id'] ?? null;
        $metadata = ['saved_via' => 'admin.web'];

        if ($request->hasFile('file')) {
            $file = $request->file('file');
            $filePath = $file->storeAs('product-documents/'.$product, Str::uuid().'.'.$file->getClientOriginalExtension(), 'public');
            $fileUrl = Storage::disk('public')->url($filePath);
            $mimeType = $file->getClientMimeType();
            $fileSize = $file->getSize();
            $metadata['original_name'] = $file->getClientOriginalName();
            $metadata['storage_disk'] = 'public';
        } elseif ($mediaAssetId) {
            $asset = DB::table('admin_media_assets')->where('id', $mediaAssetId)->first();
            if ($asset) {
                $filePath = $asset->path;
                $fileUrl = Storage::disk($asset->disk ?: 'public')->url($asset->path);
                $mimeType = $asset->mime_type;
                $fileSize = $asset->size;
                $metadata['media_asset_id'] = $asset->id;
                $metadata['media_asset_name'] = $asset->title ?: $asset->original_name;
                $metadata['storage_disk'] = $asset->disk ?: 'public';
            }
        }

        $id = DB::table('product_documents')->insertGetId([
            'product_id' => $product,
            'media_asset_id' => $mediaAssetId ?: null,
            'title' => $data['title'],
            'document_type' => $data['document_type'],
            'file_url' => $fileUrl,
            'file_path' => $filePath,
            'mime_type' => $mimeType,
            'file_size' => $fileSize,
            'source_url' => $data['source_url'] ?? null,
            'uploaded_by' => $request->user()?->id,
            'status' => 'approved',
            'is_public' => true,
            'metadata' => json_encode($metadata),
            'is_active' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $this->auditAdminAction($request, 'product_document_created', 'product_documents', $id, ['product_id' => $product, 'title' => $data['title'], 'media_asset_id' => $mediaAssetId]);

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
            'robots_reason' => ['nullable', 'string', 'max:1000'],
            'is_locked' => ['nullable', 'boolean'],
        ]);

        $catalogProduct = Product::findOrFail($product);
        $templates = app(CatalogSeoTemplateService::class);

        DB::transaction(function () use ($request, $catalogProduct, $data, $templates) {
            $record = ProductSeoMeta::firstOrNew(['product_id' => $catalogProduct->id]);
            $metadata = is_array($record->metadata) ? $record->metadata : [];
            $record->fill([
                'title' => $data['meta_title'] ?? null,
                'meta_title' => $data['meta_title'] ?? null,
                'meta_description' => $data['meta_description'] ?? null,
                'canonical_url' => $data['canonical_url'] ?? null,
                'robots' => $data['robots'],
                'robots_reason' => $data['robots_reason'] ?? 'Manual admin SEO override.',
                'schema_type' => $data['schema_type'],
                'confidence_level' => $data['confidence_level'],
                'is_manual_override' => true,
                'is_locked' => (bool) ($data['is_locked'] ?? false),
                'active_source' => 'manual',
                'modified_by' => $request->user()?->id,
                'metadata' => array_merge($metadata, [
                    'saved_via' => 'admin.web',
                    'source' => 'manual_admin_override',
                    'source_notes' => 'Manual product SEO saved through the permission-gated admin panel.',
                    'confidence_level' => $data['confidence_level'],
                    'last_updated' => now()->toIso8601String(),
                    'advisory_disclaimer' => CatalogSeoTemplateService::DISCLAIMER,
                ]),
            ]);
            $record->save();

            $existing = is_array($catalogProduct->seo_meta) ? $catalogProduct->seo_meta : [];
            $catalogProduct->update(['seo_meta' => array_merge($existing, [
                'title' => $data['meta_title'] ?? null,
                'description' => $data['meta_description'] ?? null,
                'canonical_url' => $data['canonical_url'] ?? null,
                'robots' => $data['robots'],
                'robots_reason' => $data['robots_reason'] ?? 'Manual admin SEO override.',
                'manual_override' => true,
                'locked' => (bool) ($data['is_locked'] ?? false),
                'active_source' => 'manual',
                'source' => 'manual_admin_override',
                'last_updated' => now()->toIso8601String(),
            ])]);

            $templates->recordVersion('product', $catalogProduct->id, [
                'title' => $data['meta_title'] ?? null,
                'description' => $data['meta_description'] ?? null,
                'canonical' => $data['canonical_url'] ?? null,
                'robots' => $data['robots'],
                'robots_reason' => $data['robots_reason'] ?? 'Manual admin SEO override.',
                'active_source' => 'manual',
                'template_version' => $record->template_version,
                'source_notes' => 'Manual product SEO saved through the admin panel.',
                'confidence_level' => $data['confidence_level'],
            ], 'manual_override', $request->user()?->id);
            $templates->invalidate('product', $catalogProduct->id);
        }, 3);
        $this->auditAdminAction($request, 'product_seo_saved', 'product_seo_meta', null, ['product_id' => $product]);

        return back()->with('status', 'Product SEO saved.');
    }

    public function rollbackProductSeo(Request $request, int $product, int $version): RedirectResponse
    {
        try {
            app(CatalogSeoTemplateService::class)->rollbackVersion('product', $product, $version, $request->user()?->id);
        } catch (\RuntimeException $exception) {
            return back()->with('error', $exception->getMessage());
        }

        $this->auditAdminAction($request, 'product_seo_rolled_back', 'catalog_seo_versions', $version, ['product_id' => $product]);

        return back()->with('status', 'Product SEO restored from version history; a pre-rollback snapshot was retained.');
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

    public function updateVendorDocumentStatus(Request $request, int $document): RedirectResponse
    {
        $data = $request->validate([
            'status' => ['required', 'in:pending,approved,rejected,expired'],
            'rejection_reason' => ['nullable', 'string', 'max:1000'],
        ]);

        $row = DB::table('vendor_documents')->where('id', $document)->first();
        if (! $row) {
            return back()->with('error', 'KYC document not found.');
        }

        DB::table('vendor_documents')->where('id', $document)->update([
            'status' => $data['status'],
            'rejection_reason' => $data['status'] === 'rejected' ? ($data['rejection_reason'] ?? null) : null,
            'verified_by' => $data['status'] === 'approved' ? $request->user()?->id : $row->verified_by,
            'verified_at' => $data['status'] === 'approved' ? now() : $row->verified_at,
            'updated_at' => now(),
        ]);

        $this->vendorAudit($request, (int) $row->vendor_id, 'vendor_document_status_updated', ['document_id' => $document, 'status' => $data['status']]);
        $this->auditAdminAction($request, 'vendor_document_status_updated', 'vendor_documents', $document, $data);

        return back()->with('status', 'KYC document status updated.');
    }

    public function approveVendorProduct(Request $request, int $product, ProductApprovalService $approval): RedirectResponse
    {
        $vendorProduct = VendorProduct::find($product);
        if (! $vendorProduct) {
            return back()->with('error', 'Seller product submission not found.');
        }

        $approval->approveVendorProduct($vendorProduct, $request);
        $this->auditAdminAction($request, 'vendor_product_approved', 'vendor_products', $product, [
            'vendor_id' => $vendorProduct->vendor_id,
            'product_id' => $vendorProduct->product_id,
        ]);

        return back()->with('status', 'Seller product approved.');
    }

    public function rejectVendorProduct(Request $request, int $product, ProductApprovalService $approval): RedirectResponse
    {
        $data = $request->validate([
            'reason' => ['required', 'string', 'max:1000'],
        ]);

        $vendorProduct = VendorProduct::find($product);
        if (! $vendorProduct) {
            return back()->with('error', 'Seller product submission not found.');
        }

        $approval->rejectVendorProduct($vendorProduct, $request, $data['reason']);
        $this->auditAdminAction($request, 'vendor_product_rejected', 'vendor_products', $product, [
            'vendor_id' => $vendorProduct->vendor_id,
            'product_id' => $vendorProduct->product_id,
            'reason' => $data['reason'],
        ]);

        return back()->with('status', 'Seller product rejected.');
    }

    public function approveJlcpcbImport(Request $request, int $source, CatalogSearchRebuildService $rebuilds, JlcpcbQualifiedPublicationService $qualifiedPublication): RedirectResponse
    {
        $data = $request->validate([
            'publish_public' => ['nullable', 'boolean'],
            'queue_rebuild' => ['nullable', 'boolean'],
            'note' => ['nullable', 'string', 'max:1000'],
        ]);

        $row = $this->jlcpcbSourceRow($source);
        if (! $row) {
            return back()->with('error', 'Imported product source row not found.');
        }

        if (! empty($data['publish_public']) && ! $this->jlcpcbPublicationReady($qualifiedPublication, (int) $row->id)) {
            return back()->with('error', 'Public publication is held until the JLCPCB qualification checks pass. Approve without publishing remains available.');
        }

        $this->approveImportedProduct($request, (int) $row->id, (int) $row->product_id, (bool) ($data['publish_public'] ?? false), $data['note'] ?? null);
        $jobId = ! empty($data['queue_rebuild']) ? $this->queueJlcpcbSearchRebuildJob($request, $rebuilds) : null;

        return back()->with('status', $jobId
            ? "Imported product approved and search/facet rebuild queued as job #{$jobId}."
            : 'Imported product approved for catalog review.');
    }

    public function bulkApproveJlcpcbImports(Request $request, CatalogSearchRebuildService $rebuilds, JlcpcbQualifiedPublicationService $qualifiedPublication): RedirectResponse
    {
        $data = $request->validate([
            'source_ids' => ['required', 'array', 'min:1', 'max:100'],
            'source_ids.*' => ['integer'],
            'publish_public' => ['nullable', 'boolean'],
            'queue_rebuild' => ['nullable', 'boolean'],
            'note' => ['nullable', 'string', 'max:1000'],
        ]);

        $sourceIds = array_values(array_unique($data['source_ids']));
        $readiness = ! empty($data['publish_public'])
            ? $qualifiedPublication->readinessForSourceLinks($sourceIds)
            : [];
        $approved = 0;
        $publicationHeld = 0;
        foreach ($sourceIds as $sourceId) {
            $row = $this->jlcpcbSourceRow((int) $sourceId);
            if (! $row) {
                continue;
            }
            $publishPublic = ! empty($data['publish_public']) && (bool) ($readiness[(int) $row->id]['ready'] ?? false);
            if (! empty($data['publish_public']) && ! $publishPublic) {
                $publicationHeld++;
            }
            $this->approveImportedProduct($request, (int) $row->id, (int) $row->product_id, $publishPublic, $data['note'] ?? null);
            $approved++;
        }
        $jobId = ($approved > 0 && ! empty($data['queue_rebuild']))
            ? $this->queueJlcpcbSearchRebuildJob($request, $rebuilds)
            : null;

        $message = $jobId
            ? "{$approved} imported products approved and search/facet rebuild queued as job #{$jobId}."
            : "{$approved} imported products approved.";
        if ($publicationHeld > 0) {
            $message .= " {$publicationHeld} were approved but held hidden because they did not pass publication qualification.";
        }

        return back()->with('status', $message);
    }

    public function bulkPublishJlcpcbImports(Request $request, CatalogSearchRebuildService $rebuilds, JlcpcbQualifiedPublicationService $qualifiedPublication): RedirectResponse
    {
        $data = $request->validate([
            'source_ids' => ['required', 'array', 'min:1', 'max:100'],
            'source_ids.*' => ['integer'],
            'queue_rebuild' => ['nullable', 'boolean'],
            'note' => ['nullable', 'string', 'max:1000'],
        ]);

        $sourceIds = array_values(array_unique($data['source_ids']));
        $readiness = $qualifiedPublication->readinessForSourceLinks($sourceIds);
        $published = 0;
        $skipped = 0;
        $publicationHeld = 0;
        foreach ($sourceIds as $sourceId) {
            $row = $this->jlcpcbSourceRow((int) $sourceId);
            if (! $row || $row->review_status !== 'approved') {
                $skipped++;
                continue;
            }

            $product = DB::table('products')->where('id', $row->product_id)->select('visibility_status')->first();
            if (($product->visibility_status ?? null) === 'public') {
                $skipped++;
                continue;
            }
            if (! (bool) ($readiness[(int) $row->id]['ready'] ?? false)) {
                $publicationHeld++;
                continue;
            }

            $this->publishImportedProduct($request, (int) $row->id, (int) $row->product_id, $data['note'] ?? null);
            $published++;
        }

        $jobId = ($published > 0 && ! empty($data['queue_rebuild']))
            ? $this->queueJlcpcbSearchRebuildJob($request, $rebuilds)
            : null;

        $message = "{$published} approved imported products published";
        if ($skipped > 0) {
            $message .= "; {$skipped} skipped because they were not approved or were already public";
        }
        if ($publicationHeld > 0) {
            $message .= "; {$publicationHeld} held because qualification checks did not pass";
        }

        return back()->with('status', $jobId
            ? "{$message}. Search/facet rebuild queued as job #{$jobId}."
            : "{$message}.");
    }

    public function publishJlcpcbImport(Request $request, int $source, CatalogSearchRebuildService $rebuilds, JlcpcbQualifiedPublicationService $qualifiedPublication): RedirectResponse
    {
        $data = $request->validate([
            'queue_rebuild' => ['nullable', 'boolean'],
            'note' => ['nullable', 'string', 'max:1000'],
        ]);

        $row = $this->jlcpcbSourceRow($source);
        if (! $row) {
            return back()->with('error', 'Imported product source row not found.');
        }
        if ($row->review_status !== 'approved') {
            return back()->with('error', 'Only approved imported products can be published.');
        }
        if (! $this->jlcpcbPublicationReady($qualifiedPublication, (int) $row->id)) {
            return back()->with('error', 'Public publication is held until the JLCPCB qualification checks pass. Review the qualification blockers and complete the product first.');
        }

        $this->publishImportedProduct($request, (int) $row->id, (int) $row->product_id, $data['note'] ?? null);
        $jobId = ! empty($data['queue_rebuild']) ? $this->queueJlcpcbSearchRebuildJob($request, $rebuilds) : null;

        return back()->with('status', $jobId
            ? "Approved imported product published and search/facet rebuild queued as job #{$jobId}."
            : 'Approved imported product published.');
    }

    public function rejectJlcpcbImport(Request $request, int $source): RedirectResponse
    {
        $data = $request->validate([
            'reason' => ['required', 'string', 'max:1000'],
        ]);

        $row = $this->jlcpcbSourceRow($source);
        if (! $row) {
            return back()->with('error', 'Imported product source row not found.');
        }

        DB::transaction(function () use ($request, $row, $data) {
            $now = now();
            DB::table('catalog_product_sources')->where('id', $row->id)->update([
                'review_status' => 'rejected',
                'last_synced_at' => $now,
                'updated_at' => $now,
            ]);

            DB::table('catalog_distributor_offers')->where('product_id', $row->product_id)->update([
                'review_status' => 'rejected',
                'updated_at' => $now,
            ]);

            $productUpdate = [
                'status' => 'rejected',
                'updated_at' => $now,
            ];
            if (Schema::hasColumn('products', 'approval_status')) {
                $productUpdate['approval_status'] = 'rejected';
            }
            if (Schema::hasColumn('products', 'visibility_status')) {
                $productUpdate['visibility_status'] = 'hidden';
            }
            if (Schema::hasColumn('products', 'rejection_reason')) {
                $productUpdate['rejection_reason'] = $data['reason'];
            }
            if (Schema::hasColumn('products', 'metadata')) {
                $product = DB::table('products')->where('id', $row->product_id)->first();
                $metadata = json_decode((string) ($product->metadata ?? '{}'), true) ?: [];
                $metadata['jlcpcb_admin_review'] = [
                    'status' => 'rejected',
                    'reviewed_by' => $request->user()?->id,
                    'reviewed_at' => $now->toIso8601String(),
                    'reason' => $data['reason'],
                ];
                $productUpdate['metadata'] = json_encode($metadata);
            }
            DB::table('products')->where('id', $row->product_id)->update($productUpdate);

            $this->auditAdminAction($request, 'jlcpcb_import_rejected', 'catalog_product_sources', (int) $row->id, [
                'product_id' => (int) $row->product_id,
                'source_part_id' => $row->source_part_id,
                'reason' => $data['reason'],
            ]);
        });
        Cache::forget('seo:sitemap');

        return back()->with('status', 'Imported product rejected and hidden.');
    }

    public function queueJlcpcbSearchRebuild(Request $request, CatalogSearchRebuildService $rebuilds): RedirectResponse
    {
        abort_unless(Schema::hasTable('catalog_index_rebuild_jobs'), 404);

        $jobId = $this->queueJlcpcbSearchRebuildJob($request, $rebuilds);

        return back()->with('status', "Search/facet rebuild queued as job #{$jobId}.");
    }

    // ---- BOM procurement imports ------------------------------------------

    /** Re-runs the existing matcher while retaining manual line decisions. */
    public function rematchBomImport(Request $request, BomImport $import, BomImportService $imports): RedirectResponse
    {
        if ($import->status === 'converted') {
            return back()->with('error', 'Converted BOM imports are immutable so their RFQ history remains accurate.');
        }

        $before = [
            'matched_lines' => (int) $import->matched_lines,
            'unmatched_lines' => (int) $import->unmatched_lines,
        ];
        $updated = $imports->rematch($import);

        $this->auditAdminAction($request, 'bom_import_rematched', 'bom_imports', (int) $import->id, [
            'name' => $import->name,
            'before' => $before,
            'after' => [
                'matched_lines' => (int) $updated->matched_lines,
                'unmatched_lines' => (int) $updated->unmatched_lines,
            ],
        ]);

        return back()->with('status', "BOM #{$import->id} rematched. Manual line decisions were preserved.");
    }

    /** Sets or clears one line match through the existing BOM import service. */
    public function setBomImportLineMatch(Request $request, BomImport $import, BomImportLine $line, BomImportService $imports): RedirectResponse
    {
        if ((int) $line->bom_import_id !== (int) $import->id) {
            abort(404);
        }
        if ($import->status === 'converted') {
            return back()->with('error', 'Converted BOM imports are immutable so their RFQ history remains accurate.');
        }

        $data = $request->validate([
            'matched_product_id' => ['nullable', 'integer', 'exists:products,id'],
        ]);
        $before = [
            'matched_product_id' => $line->matched_product_id,
            'match_status' => $line->match_status,
            'match_confidence' => $line->match_confidence,
        ];

        try {
            $updated = $imports->setLineMatch($line, $data['matched_product_id'] ?? null);
        } catch (\RuntimeException $exception) {
            return back()->with('error', $exception->getMessage());
        }

        $this->auditAdminAction($request, 'bom_import_line_match_set', 'bom_import_lines', (int) $line->id, [
            'bom_import_id' => (int) $import->id,
            'line_no' => (int) $line->line_no,
            'before' => $before,
            'after' => [
                'matched_product_id' => $updated->matched_product_id,
                'match_status' => $updated->match_status,
                'match_confidence' => $updated->match_confidence,
            ],
        ]);

        return back()->with('status', "BOM line {$line->line_no} updated.");
    }

    private function queueJlcpcbSearchRebuildJob(Request $request, CatalogSearchRebuildService $rebuilds): int
    {
        abort_unless(Schema::hasTable('catalog_index_rebuild_jobs'), 404);

        $jobId = $rebuilds->createJob($request->user()?->id, 'jlcpcb_parts_database');
        RebuildApprovedImportSearchIndexJob::dispatch($jobId, 'jlcpcb_parts_database')
            ->onConnection(RebuildApprovedImportSearchIndexJob::CONNECTION)
            ->onQueue(RebuildApprovedImportSearchIndexJob::QUEUE);

        $this->auditAdminAction($request, 'jlcpcb_search_rebuild_queued', 'catalog_index_rebuild_jobs', $jobId, [
            'source_code' => 'jlcpcb_parts_database',
            'scope' => 'approved_imports',
        ]);

        return $jobId;
    }

    private function jlcpcbSourceRow(int $sourceId): ?object
    {
        if (! Schema::hasTable('catalog_product_sources')) {
            return null;
        }

        return DB::table('catalog_product_sources as cps')
            ->join('catalog_sources as cs', 'cs.id', '=', 'cps.source_id')
            ->where('cs.code', 'jlcpcb_parts_database')
            ->where('cps.id', $sourceId)
            ->select('cps.*')
            ->first();
    }

    private function jlcpcbPublicationReady(JlcpcbQualifiedPublicationService $qualifiedPublication, int $sourceId): bool
    {
        return (bool) ($qualifiedPublication->readinessForSourceLinks([$sourceId])[$sourceId]['ready'] ?? false);
    }

    private function approveImportedProduct(Request $request, int $sourceId, int $productId, bool $publishPublic, ?string $note): void
    {
        DB::transaction(function () use ($request, $sourceId, $productId, $publishPublic, $note) {
            $now = now();
            DB::table('catalog_product_sources')->where('id', $sourceId)->update([
                'review_status' => 'approved',
                'last_synced_at' => $now,
                'updated_at' => $now,
            ]);

            DB::table('catalog_distributor_offers')->where('product_id', $productId)->update([
                'review_status' => 'approved',
                'updated_at' => $now,
            ]);

            if (Schema::hasTable('product_documents')) {
                $documentUpdate = ['updated_at' => $now];
                if (Schema::hasColumn('product_documents', 'status')) {
                    $documentUpdate['status'] = 'approved';
                }
                if (Schema::hasColumn('product_documents', 'is_public')) {
                    $documentUpdate['is_public'] = $publishPublic;
                }
                DB::table('product_documents')->where('product_id', $productId)->where('document_type', 'datasheet')->update($documentUpdate);
            }

            $product = DB::table('products')->where('id', $productId)->first();
            $metadata = json_decode((string) ($product->metadata ?? '{}'), true) ?: [];
            $metadata['jlcpcb_admin_review'] = [
                'status' => 'approved',
                'reviewed_by' => $request->user()?->id,
                'reviewed_at' => $now->toIso8601String(),
                'publish_public' => $publishPublic,
                'note' => $note,
            ];

            $productUpdate = [
                'status' => 'approved',
                'metadata' => json_encode($metadata),
                'updated_at' => $now,
            ];
            if (Schema::hasColumn('products', 'approval_status')) {
                $productUpdate['approval_status'] = 'approved';
            }
            if (Schema::hasColumn('products', 'approved_by')) {
                $productUpdate['approved_by'] = $request->user()?->id;
            }
            if (Schema::hasColumn('products', 'approved_at')) {
                $productUpdate['approved_at'] = $now;
            }
            if (Schema::hasColumn('products', 'visibility_status')) {
                $productUpdate['visibility_status'] = $publishPublic ? 'public' : 'hidden';
            }
            if (Schema::hasColumn('products', 'rejection_reason')) {
                $productUpdate['rejection_reason'] = null;
            }
            DB::table('products')->where('id', $productId)->update($productUpdate);

            $this->auditAdminAction($request, 'jlcpcb_import_approved', 'catalog_product_sources', $sourceId, [
                'product_id' => $productId,
                'publish_public' => $publishPublic,
                'note' => $note,
            ]);
        });
        Cache::forget('seo:sitemap');
    }

    private function publishImportedProduct(Request $request, int $sourceId, int $productId, ?string $note): void
    {
        DB::transaction(function () use ($request, $sourceId, $productId, $note) {
            $now = now();
            $product = DB::table('products')->where('id', $productId)->first();
            $metadata = json_decode((string) ($product->metadata ?? '{}'), true) ?: [];
            $metadata['jlcpcb_publication'] = [
                'status' => 'published',
                'published_by' => $request->user()?->id,
                'published_at' => $now->toIso8601String(),
                'note' => $note,
            ];

            $productUpdate = [
                'status' => 'approved',
                'metadata' => json_encode($metadata),
                'updated_at' => $now,
            ];
            if (Schema::hasColumn('products', 'approval_status')) {
                $productUpdate['approval_status'] = 'approved';
            }
            if (Schema::hasColumn('products', 'visibility_status')) {
                $productUpdate['visibility_status'] = 'public';
            }
            if (Schema::hasColumn('products', 'approved_at')) {
                $productUpdate['approved_at'] = $product->approved_at ?: $now;
            }
            DB::table('products')->where('id', $productId)->update($productUpdate);

            DB::table('catalog_product_sources')->where('id', $sourceId)->update([
                'last_synced_at' => $now,
                'updated_at' => $now,
            ]);

            if (Schema::hasTable('product_documents')) {
                $documentUpdate = ['updated_at' => $now];
                if (Schema::hasColumn('product_documents', 'is_public')) {
                    $documentUpdate['is_public'] = true;
                }
                DB::table('product_documents')->where('product_id', $productId)->where('document_type', 'datasheet')->update($documentUpdate);
            }

            $this->auditAdminAction($request, 'jlcpcb_import_published', 'catalog_product_sources', $sourceId, [
                'product_id' => $productId,
                'note' => $note,
            ]);
        });
        Cache::forget('seo:sitemap');
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

    public function storeAdminInvitation(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'name' => ['nullable', 'string', 'max:180'],
            'email' => ['required', 'email', 'max:255'],
            'role_id' => ['nullable', 'integer', 'exists:roles,id'],
            'expires_days' => ['nullable', 'integer', 'min:1', 'max:90'],
        ]);

        $id = DB::table('admin_invitations')->insertGetId([
            'name' => $data['name'] ?? null,
            'email' => $data['email'],
            'role_id' => $data['role_id'] ?? null,
            'token' => Str::random(48),
            'status' => 'pending',
            'invited_by' => $request->user()?->id,
            'expires_at' => now()->addDays((int) ($data['expires_days'] ?? 7)),
            'metadata' => json_encode(['delivery' => 'logged_only', 'saved_via' => 'admin.web']),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $this->auditAdminAction($request, 'admin_invitation_created', 'admin_invitations', $id, ['email' => $data['email'], 'role_id' => $data['role_id'] ?? null]);

        return back()->with('status', 'Invitation logged. Email delivery provider is not enabled for this action.');
    }

    public function storePermission(Request $request): RedirectResponse
    {
        abort_unless($request->user()?->hasRole('super_admin'), 403);

        $data = $request->validate([
            'key' => ['required', 'string', 'max:160', 'regex:/^[a-z0-9_.-]+$/i'],
            'name' => ['required', 'string', 'max:180'],
            'group' => ['required', 'string', 'max:80'],
            'description' => ['nullable', 'string', 'max:1000'],
        ]);

        DB::table('permissions')->updateOrInsert(
            ['key' => $data['key']],
            [
                'key' => $data['key'],
                'name' => $data['name'],
                'group' => $data['group'],
                'description' => $data['description'] ?? null,
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ]
        );

        $this->auditAdminAction($request, 'permission_saved', 'permissions', null, ['key' => $data['key']]);

        return back()->with('status', 'Permission saved.');
    }

    public function toggleRolePermission(Request $request, int $role, int $permission): RedirectResponse
    {
        abort_unless($request->user()?->hasRole('super_admin'), 403);

        try {
            $enabled = DB::transaction(function () use ($role, $permission): bool {
                $roleRow = DB::table('roles')->where('id', $role)->lockForUpdate()->first();
                $permissionRow = DB::table('permissions')->where('id', $permission)->where('is_active', true)->lockForUpdate()->first();
                if (! $roleRow || ! $permissionRow) {
                    throw new \RuntimeException('Role or active permission not found.');
                }

                $permissionKeys = $this->permissionKeys($roleRow->permissions ?? null);
                if (in_array('*', $permissionKeys, true)) {
                    throw new \RuntimeException('Wildcard roles are immutable in the permission matrix.');
                }

                $pivot = DB::table('role_permissions')
                    ->where('role_id', $role)
                    ->where('permission_id', $permission)
                    ->lockForUpdate()
                    ->first();
                $wasEnabled = $pivot !== null || in_array((string) $permissionRow->key, $permissionKeys, true);

                if ($wasEnabled) {
                    DB::table('role_permissions')->where('role_id', $role)->where('permission_id', $permission)->delete();
                    $permissionKeys = array_values(array_filter(
                        $permissionKeys,
                        static fn (string $key): bool => $key !== (string) $permissionRow->key
                    ));
                } else {
                    DB::table('role_permissions')->insert([
                        'role_id' => $role,
                        'permission_id' => $permission,
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]);
                    $permissionKeys[] = (string) $permissionRow->key;
                    $permissionKeys = array_values(array_unique($permissionKeys));
                }

                DB::table('roles')->where('id', $role)->update([
                    'permissions' => json_encode($permissionKeys),
                    'updated_at' => now(),
                ]);

                return ! $wasEnabled;
            }, 3);
        } catch (\RuntimeException $exception) {
            return back()->with('error', $exception->getMessage());
        }

        $this->auditAdminAction($request, 'role_permission_toggled', 'role_permissions', null, ['role_id' => $role, 'permission_id' => $permission, 'enabled' => $enabled]);

        return back()->with('status', 'Role permission updated.');
    }

    public function assignUserCountryAccess(Request $request, int $user): RedirectResponse
    {
        $data = $request->validate(['country_id' => ['required', 'integer', 'exists:countries,id']]);
        DB::table('user_country_access')->updateOrInsert(
            ['user_id' => $user, 'country_id' => $data['country_id']],
            ['assigned_by' => $request->user()?->id, 'metadata' => json_encode(['saved_via' => 'admin.web']), 'created_at' => now(), 'updated_at' => now()]
        );
        $this->auditAdminAction($request, 'user_country_access_assigned', 'user_country_access', null, ['user_id' => $user, 'country_id' => $data['country_id']]);
        return back()->with('status', 'Country access assigned.');
    }

    public function assignUserSellerAccess(Request $request, int $user): RedirectResponse
    {
        $data = $request->validate([
            'vendor_id' => ['required', 'integer', 'exists:vendors,id'],
            'access_level' => ['required', 'string', 'max:80'],
        ]);
        DB::table('user_seller_access')->updateOrInsert(
            ['user_id' => $user, 'vendor_id' => $data['vendor_id']],
            ['access_level' => $data['access_level'], 'assigned_by' => $request->user()?->id, 'metadata' => json_encode(['saved_via' => 'admin.web']), 'created_at' => now(), 'updated_at' => now()]
        );
        $this->auditAdminAction($request, 'user_seller_access_assigned', 'user_seller_access', null, ['user_id' => $user, 'vendor_id' => $data['vendor_id'], 'access_level' => $data['access_level']]);
        return back()->with('status', 'Seller access assigned.');
    }

    // ---- LMS operations ----------------------------------------------------

    public function storeLmsCourse(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'id' => ['nullable', 'integer', 'exists:lms_courses,id'],
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

        $payload = [
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
            'updated_at' => now(),
        ];

        if (! empty($data['id'])) {
            DB::table('lms_courses')->where('id', $data['id'])->update($payload);
            $id = (int) $data['id'];
            $verb = 'updated';
        } else {
            $payload['created_at'] = now();
            $id = DB::table('lms_courses')->insertGetId($payload);
            $verb = 'created';
        }

        $this->auditAdminAction($request, 'lms_course_'.$verb, 'lms_courses', $id, ['title' => $data['title']]);

        return back()->with('status', "Course {$verb}.");
    }

    public function storeLmsModule(Request $request, int $course): RedirectResponse
    {
        $data = $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'summary' => ['nullable', 'string', 'max:1000'],
            'sort_order' => ['nullable', 'integer', 'min:0'],
            'status' => ['required', 'string', 'max:60'],
        ]);

        $id = DB::table('lms_modules')->insertGetId([
            'lms_course_id' => $course,
            'title' => $data['title'],
            'slug' => Str::slug($data['title']).'-'.Str::lower(Str::random(4)),
            'summary' => $data['summary'] ?? null,
            'sort_order' => $data['sort_order'] ?? 100,
            'status' => $data['status'],
            'metadata' => json_encode(['saved_via' => 'admin.web']),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $this->auditAdminAction($request, 'lms_module_created', 'lms_modules', $id, ['lms_course_id' => $course, 'title' => $data['title']]);

        return back()->with('status', 'Module created.');
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

    public function storeLmsProject(Request $request, int $course): RedirectResponse
    {
        $data = $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'summary' => ['nullable', 'string', 'max:1000'],
            'description' => ['nullable', 'string'],
            'difficulty_level' => ['nullable', 'string', 'max:60'],
            'estimated_minutes' => ['nullable', 'integer', 'min:0'],
            'status' => ['required', 'string', 'max:60'],
            'thumbnail_url' => ['nullable', 'string', 'max:1000'],
        ]);

        $id = DB::table('lms_projects')->insertGetId([
            'lms_course_id' => $course,
            'title' => $data['title'],
            'slug' => Str::slug($data['title']).'-'.Str::lower(Str::random(4)),
            'summary' => $data['summary'] ?? null,
            'description' => $data['description'] ?? null,
            'difficulty_level' => $data['difficulty_level'] ?? 'beginner',
            'estimated_minutes' => $data['estimated_minutes'] ?? null,
            'status' => $data['status'],
            'thumbnail_url' => $data['thumbnail_url'] ?? null,
            'published_at' => $data['status'] === 'published' ? now() : null,
            'metadata' => json_encode(['saved_via' => 'admin.web']),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $this->auditAdminAction($request, 'lms_project_created', 'lms_projects', $id, ['lms_course_id' => $course, 'title' => $data['title']]);

        return back()->with('status', 'Project created.');
    }

    public function storeLmsProductLink(Request $request, int $course): RedirectResponse
    {
        $data = $request->validate([
            'product_id' => ['required', 'integer', 'exists:products,id'],
            'lms_project_id' => ['nullable', 'integer', 'exists:lms_projects,id'],
            'lms_lesson_id' => ['nullable', 'integer', 'exists:lms_lessons,id'],
            'title' => ['nullable', 'string', 'max:190'],
            'link_type' => ['required', 'string', 'max:80'],
            'is_required' => ['nullable', 'boolean'],
            'notes' => ['nullable', 'string', 'max:1000'],
        ]);

        $product = DB::table('products')->where('id', $data['product_id'])->first();
        $id = DB::table('product_lms_links')->insertGetId([
            'lms_course_id' => $course,
            'lms_project_id' => $data['lms_project_id'] ?? null,
            'lms_lesson_id' => $data['lms_lesson_id'] ?? null,
            'product_id' => $data['product_id'],
            'title' => $data['title'] ?: $product?->name,
            'link_type' => $data['link_type'],
            'relation_type' => $data['link_type'],
            'is_required' => (bool) ($data['is_required'] ?? false),
            'sort_order' => 100,
            'notes' => $data['notes'] ?? null,
            'is_active' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $this->auditAdminAction($request, 'lms_product_link_created', 'product_lms_links', $id, ['lms_course_id' => $course, 'product_id' => $data['product_id']]);

        return back()->with('status', 'Product/lab kit linked.');
    }

    public function storeLmsLessonFile(Request $request, int $course, int $lesson): RedirectResponse
    {
        $data = $request->validate([
            'title' => ['required', 'string', 'max:190'],
            'admin_media_asset_id' => ['nullable', 'integer', 'exists:admin_media_assets,id'],
            'file' => ['nullable', 'file', 'max:51200', 'mimes:jpg,jpeg,png,webp,pdf,mp4,mov,zip,csv,txt'],
            'file_url' => ['nullable', 'string', 'max:1000'],
            'file_type' => ['required', 'string', 'max:80'],
            'is_downloadable' => ['nullable', 'boolean'],
        ]);

        if (! DB::table('lms_lessons')->where('id', $lesson)->where('lms_course_id', $course)->exists()) {
            return back()->with('error', 'Lesson not found for this course.');
        }

        $fileUrl = $data['file_url'] ?? null;
        $mimeType = null;
        $fileSize = null;
        $metadata = ['saved_via' => 'admin.web'];

        if ($request->hasFile('file')) {
            $file = $request->file('file');
            $path = $file->storeAs('lms-files/'.$lesson, Str::uuid().'.'.$file->getClientOriginalExtension(), 'public');
            $fileUrl = Storage::disk('public')->url($path);
            $mimeType = $file->getClientMimeType();
            $fileSize = $file->getSize();
            $metadata['path'] = $path;
            $metadata['disk'] = 'public';
            $metadata['original_name'] = $file->getClientOriginalName();
        } elseif (! empty($data['admin_media_asset_id'])) {
            $asset = DB::table('admin_media_assets')->where('id', $data['admin_media_asset_id'])->first();
            if ($asset) {
                $fileUrl = Storage::disk($asset->disk ?: 'public')->url($asset->path);
                $mimeType = $asset->mime_type;
                $fileSize = $asset->size;
                $metadata['media_asset_id'] = $asset->id;
            }
        }

        $id = DB::table('lms_lesson_files')->insertGetId([
            'lms_lesson_id' => $lesson,
            'admin_media_asset_id' => $data['admin_media_asset_id'] ?? null,
            'title' => $data['title'],
            'file_url' => $fileUrl,
            'file_type' => $data['file_type'],
            'mime_type' => $mimeType,
            'file_size' => $fileSize,
            'is_downloadable' => (bool) ($data['is_downloadable'] ?? true),
            'is_active' => true,
            'metadata' => json_encode($metadata),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $this->auditAdminAction($request, 'lms_lesson_file_created', 'lms_lesson_files', $id, ['lms_course_id' => $course, 'lms_lesson_id' => $lesson]);

        return back()->with('status', 'Lesson file attached.');
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

    public function issueLmsCertificate(Request $request, int $enrollment): RedirectResponse
    {
        $row = DB::table('lms_enrollments')->where('id', $enrollment)->first();
        if (! $row) {
            return back()->with('error', 'Enrollment not found.');
        }
        if ((float) $row->progress_percent < 100) {
            return back()->with('error', 'Enrollment must be 100% complete before issuing a certificate.');
        }

        $existing = DB::table('lms_certificates')->where('lms_enrollment_id', $enrollment)->first();
        if ($existing) {
            return back()->with('status', 'Certificate already exists: '.$existing->certificate_number);
        }

        $number = 'NG-LMS-'.now()->format('Ymd').'-'.Str::upper(Str::random(8));
        $id = DB::table('lms_certificates')->insertGetId([
            'lms_enrollment_id' => $enrollment,
            'lms_course_id' => $row->lms_course_id,
            'user_id' => $row->user_id,
            'email' => $row->email,
            'certificate_number' => $number,
            'status' => 'issued',
            'issued_at' => now(),
            'metadata' => json_encode(['issued_via' => 'admin.web']),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $this->auditAdminAction($request, 'lms_certificate_issued', 'lms_certificates', $id, ['certificate_number' => $number]);

        return back()->with('status', 'Certificate issued: '.$number);
    }

    public function revokeLmsCertificate(Request $request, int $certificate): RedirectResponse
    {
        DB::table('lms_certificates')->where('id', $certificate)->update([
            'status' => 'revoked',
            'updated_at' => now(),
        ]);

        $this->auditAdminAction($request, 'lms_certificate_revoked', 'lms_certificates', $certificate, []);

        return back()->with('status', 'Certificate revoked.');
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

    public function transferInventoryStock(Request $request, TransferService $transfers): RedirectResponse
    {
        $data = $request->validate([
            'product_id' => ['required', 'integer', 'exists:products,id'],
            'from_warehouse_id' => ['required', 'integer', 'exists:warehouses,id'],
            'to_warehouse_id' => ['required', 'integer', 'different:from_warehouse_id', 'exists:warehouses,id'],
            'quantity' => ['required', 'integer', 'min:1'],
            'notes' => ['nullable', 'string', 'max:1000'],
        ]);

        try {
            $transfers->transfer($data + ['metadata' => ['saved_via' => 'admin.web']]);
        } catch (\Throwable $e) {
            return back()->with('error', $e->getMessage());
        }

        $this->auditAdminAction($request, 'inventory_stock_transferred', 'inventory_movements', null, $data);

        return back()->with('status', 'Inventory transfer posted.');
    }

    public function reserveInventoryStock(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'product_id' => ['required', 'integer', 'exists:products,id'],
            'warehouse_id' => ['required', 'integer', 'exists:warehouses,id'],
            'quantity' => ['required', 'integer', 'min:1'],
            'reference_type' => ['nullable', 'string', 'max:80'],
            'reference_id' => ['nullable', 'integer', 'min:1'],
            'notes' => ['nullable', 'string', 'max:1000'],
        ]);

        try {
            $reservationId = DB::transaction(function () use ($request, $data) {
                $stock = DB::table('inventory_stocks')
                    ->where('product_id', $data['product_id'])
                    ->where('warehouse_id', $data['warehouse_id'])
                    ->lockForUpdate()
                    ->first();

                if (! $stock) {
                    throw new \RuntimeException('No stock row exists for this product and warehouse.');
                }

                if ((int) $stock->quantity_available < (int) $data['quantity']) {
                    throw new \RuntimeException('Insufficient available stock to reserve.');
                }

                $beforeAvailable = (int) $stock->quantity_available;
                $afterAvailable = $beforeAvailable - (int) $data['quantity'];
                $afterReserved = (int) $stock->quantity_reserved + (int) $data['quantity'];

                DB::table('inventory_stocks')->where('id', $stock->id)->update([
                    'quantity_available' => $afterAvailable,
                    'quantity_reserved' => $afterReserved,
                    'last_movement_at' => now(),
                    'updated_at' => now(),
                ]);

                $reservationId = DB::table('inventory_reservations')->insertGetId([
                    'inventory_stock_id' => $stock->id,
                    'product_id' => $data['product_id'],
                    'warehouse_id' => $data['warehouse_id'],
                    'variant_id' => $stock->variant_id,
                    'quantity' => $data['quantity'],
                    'status' => 'active',
                    'reference_type' => $data['reference_type'] ?? 'admin_hold',
                    'reference_id' => $data['reference_id'] ?? null,
                    'notes' => $data['notes'] ?? null,
                    'created_by' => $request->user()?->id,
                    'metadata' => json_encode(['saved_via' => 'admin.web']),
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);

                DB::table('inventory_movements')->insert([
                    'inventory_stock_id' => $stock->id,
                    'product_id' => $data['product_id'],
                    'variant_id' => $stock->variant_id,
                    'warehouse_id' => $data['warehouse_id'],
                    'marketplace_id' => $stock->marketplace_id,
                    'vendor_id' => $stock->vendor_id,
                    'movement_type' => 'reservation',
                    'quantity_change' => -abs((int) $data['quantity']),
                    'quantity_before' => $beforeAvailable,
                    'quantity_after' => $afterAvailable,
                    'reference_type' => 'inventory_reservation',
                    'reference_id' => $reservationId,
                    'notes' => $data['notes'] ?? 'Reserved via admin console',
                    'user_id' => $request->user()?->id,
                    'metadata' => json_encode(['reserved_quantity_after' => $afterReserved]),
                    'occurred_at' => now(),
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);

                return $reservationId;
            });
        } catch (\Throwable $e) {
            return back()->with('error', $e->getMessage());
        }

        $this->auditAdminAction($request, 'inventory_stock_reserved', 'inventory_reservations', $reservationId, $data);

        return back()->with('status', 'Inventory stock reserved.');
    }

    public function releaseInventoryReservation(Request $request, int $reservation): RedirectResponse
    {
        try {
            DB::transaction(function () use ($request, $reservation) {
                $row = DB::table('inventory_reservations')->where('id', $reservation)->lockForUpdate()->first();
                if (! $row) {
                    throw new \RuntimeException('Reservation not found.');
                }
                if ($row->status !== 'active') {
                    throw new \RuntimeException('Reservation is already released or closed.');
                }

                $stock = DB::table('inventory_stocks')->where('id', $row->inventory_stock_id)->lockForUpdate()->first();
                if (! $stock) {
                    throw new \RuntimeException('Reservation stock row not found.');
                }

                $beforeAvailable = (int) $stock->quantity_available;
                $afterAvailable = $beforeAvailable + (int) $row->quantity;
                $afterReserved = max(0, (int) $stock->quantity_reserved - (int) $row->quantity);

                DB::table('inventory_stocks')->where('id', $stock->id)->update([
                    'quantity_available' => $afterAvailable,
                    'quantity_reserved' => $afterReserved,
                    'last_movement_at' => now(),
                    'updated_at' => now(),
                ]);

                DB::table('inventory_reservations')->where('id', $reservation)->update([
                    'status' => 'released',
                    'released_by' => $request->user()?->id,
                    'released_at' => now(),
                    'updated_at' => now(),
                ]);

                DB::table('inventory_movements')->insert([
                    'inventory_stock_id' => $stock->id,
                    'product_id' => $row->product_id,
                    'variant_id' => $row->variant_id,
                    'warehouse_id' => $row->warehouse_id,
                    'marketplace_id' => $stock->marketplace_id,
                    'vendor_id' => $stock->vendor_id,
                    'movement_type' => 'reservation_release',
                    'quantity_change' => abs((int) $row->quantity),
                    'quantity_before' => $beforeAvailable,
                    'quantity_after' => $afterAvailable,
                    'reference_type' => 'inventory_reservation',
                    'reference_id' => $reservation,
                    'notes' => 'Reservation released via admin console',
                    'user_id' => $request->user()?->id,
                    'metadata' => json_encode(['reserved_quantity_after' => $afterReserved]),
                    'occurred_at' => now(),
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            });
        } catch (\Throwable $e) {
            return back()->with('error', $e->getMessage());
        }

        $this->auditAdminAction($request, 'inventory_reservation_released', 'inventory_reservations', $reservation, []);

        return back()->with('status', 'Inventory reservation released.');
    }

    public function generateLowStockAlerts(Request $request): RedirectResponse
    {
        if (! Schema::hasTable('low_stock_alerts')) {
            return back()->with('error', 'Low-stock alert table is not available yet.');
        }

        $created = 0;
        $updated = 0;
        $resolved = 0;

        try {
            DB::transaction(function () use (&$created, &$updated, &$resolved) {
                DB::table('inventory_stocks')
                    ->where('reorder_point', '>', 0)
                    ->whereColumn('quantity_available', '<=', 'reorder_point')
                    ->lockForUpdate()
                    ->chunkById(500, function ($rows) use (&$created, &$updated): void {
                        foreach ($rows as $stock) {
                            $severity = (int) $stock->quantity_available <= 0 ? 'critical' : 'warning';
                            $existing = DB::table('low_stock_alerts')
                                ->where('inventory_stock_id', $stock->id)
                                ->whereIn('status', ['open', 'active', 'acknowledged', 'reorder_queued'])
                                ->lockForUpdate()
                                ->first();

                            $payload = [
                                'inventory_stock_id' => $stock->id,
                                'product_id' => $stock->product_id,
                                'warehouse_id' => $stock->warehouse_id,
                                'vendor_id' => $stock->vendor_id,
                                'marketplace_id' => $stock->marketplace_id,
                                'available_quantity' => $stock->quantity_available,
                                'threshold' => $stock->reorder_point,
                                'status' => $existing?->status ?: 'open',
                                'severity' => $severity,
                                'metadata' => json_encode(['generated_via' => 'admin.web', 'source_table' => 'inventory_stocks']),
                                'updated_at' => now(),
                            ];

                            if ($existing) {
                                DB::table('low_stock_alerts')->where('id', $existing->id)->update($payload);
                                $updated++;
                            } else {
                                $payload['created_at'] = now();
                                DB::table('low_stock_alerts')->insert($payload);
                                $created++;
                            }
                        }
                    });

                $resolved = DB::table('low_stock_alerts')
                    ->whereIn('status', ['open', 'active', 'acknowledged', 'reorder_queued'])
                    ->whereNotNull('inventory_stock_id')
                    ->whereNotExists(function ($query): void {
                        $query->selectRaw('1')
                            ->from('inventory_stocks as current_stock')
                            ->whereColumn('current_stock.id', 'low_stock_alerts.inventory_stock_id')
                            ->where('current_stock.reorder_point', '>', 0)
                            ->whereColumn('current_stock.quantity_available', '<=', 'current_stock.reorder_point');
                    })
                    ->update([
                        'status' => 'resolved',
                        'resolved_at' => now(),
                        'action_note' => 'Automatically resolved after authoritative low-stock scan.',
                        'updated_at' => now(),
                    ]);
            });
        } catch (\Throwable $e) {
            return back()->with('error', $e->getMessage());
        }

        $this->auditAdminAction($request, 'low_stock_alerts_generated', 'low_stock_alerts', null, compact('created', 'updated', 'resolved'));

        return back()->with('status', "Low-stock scan complete: {$created} created, {$updated} refreshed, {$resolved} resolved.");
    }

    public function updateLowStockAlert(Request $request, int $alert): RedirectResponse
    {
        $data = $request->validate([
            'action' => ['required', 'in:acknowledge,reorder_queued,resolve,ignore'],
            'note' => ['nullable', 'string', 'max:1000'],
        ]);

        $row = DB::table('low_stock_alerts')->where('id', $alert)->first();
        if (! $row) {
            return back()->with('error', 'Low-stock alert not found.');
        }

        $status = match ($data['action']) {
            'acknowledge' => 'acknowledged',
            'reorder_queued' => 'reorder_queued',
            'resolve' => 'resolved',
            'ignore' => 'ignored',
        };

        $payload = [
            'status' => $status,
            'action_note' => $data['note'] ?? null,
            'updated_at' => now(),
        ];

        if ($data['action'] === 'acknowledge') {
            $payload['acknowledged_by'] = $request->user()?->id;
            $payload['acknowledged_at'] = now();
        }

        if (in_array($data['action'], ['resolve', 'ignore'], true)) {
            $payload['resolved_by'] = $request->user()?->id;
            $payload['resolved_at'] = now();
        }

        DB::table('low_stock_alerts')->where('id', $alert)->update($payload);

        if (Schema::hasTable('stock_alert_actions')) {
            DB::table('stock_alert_actions')->insert([
                'low_stock_alert_id' => $alert,
                'action' => $data['action'],
                'note' => $data['note'] ?? null,
                'created_by' => $request->user()?->id,
                'metadata' => json_encode(['previous_status' => $row->status ?? null, 'new_status' => $status]),
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        $this->auditAdminAction($request, 'low_stock_alert_'.$data['action'], 'low_stock_alerts', $alert, ['status' => $status, 'note' => $data['note'] ?? null]);

        return back()->with('status', 'Low-stock alert updated.');
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

    public function storePosPaymentMethod(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'id' => ['nullable', 'integer', 'exists:pos_payment_methods,id'],
            'name' => ['required', 'string', 'max:120'],
            'code' => ['required', 'string', 'max:80', 'regex:/^[a-z0-9_.-]+$/i'],
            'type' => ['required', 'string', 'max:80'],
            'requires_reference' => ['nullable', 'boolean'],
            'is_active' => ['nullable', 'boolean'],
        ]);

        $payload = [
            'name' => $data['name'],
            'code' => Str::lower($data['code']),
            'type' => $data['type'],
            'requires_reference' => (bool) ($data['requires_reference'] ?? false),
            'is_active' => (bool) ($data['is_active'] ?? true),
            'metadata' => json_encode(['saved_via' => 'admin.web']),
            'updated_at' => now(),
        ];

        if (! empty($data['id'])) {
            DB::table('pos_payment_methods')->where('id', $data['id'])->update($payload);
            $id = (int) $data['id'];
            $verb = 'updated';
        } else {
            $payload['created_at'] = now();
            $id = DB::table('pos_payment_methods')->insertGetId($payload);
            $verb = 'created';
        }

        $this->auditAdminAction($request, 'pos_payment_method_'.$verb, 'pos_payment_methods', $id, $payload);

        return back()->with('status', 'POS payment method saved.');
    }

    public function togglePosPaymentMethod(Request $request, int $method): RedirectResponse
    {
        $row = DB::table('pos_payment_methods')->where('id', $method)->first();
        if (! $row) {
            return back()->with('error', 'Payment method not found.');
        }

        DB::table('pos_payment_methods')->where('id', $method)->update([
            'is_active' => ! (bool) $row->is_active,
            'updated_at' => now(),
        ]);

        $this->auditAdminAction($request, 'pos_payment_method_toggled', 'pos_payment_methods', $method, ['is_active' => ! (bool) $row->is_active]);

        return back()->with('status', 'POS payment method updated.');
    }

    public function storePosRefund(Request $request, int $sale): RedirectResponse
    {
        if (! $request->filled('idempotency_key') && trim((string) $request->header('Idempotency-Key')) !== '') {
            $request->merge(['idempotency_key' => trim((string) $request->header('Idempotency-Key'))]);
        }

        $data = $request->validate([
            'amount' => ['required', 'numeric', 'decimal:0,4', 'min:0.01'],
            'refund_method' => ['required', 'string', 'max:80'],
            'reason' => ['required', 'string', 'max:1000'],
            'idempotency_key' => ['required', 'string', 'max:120', 'regex:/^[A-Za-z0-9._:-]+$/'],
        ]);

        $amount = $this->formatPosAmount($this->posAmountUnits($data['amount']));
        $idempotencyKey = trim((string) ($data['idempotency_key'] ?? ''));
        $requestFingerprint = hash('sha256', json_encode([
            'pos_sale_id' => $sale,
            'processed_by' => $request->user()?->id,
            'amount' => $amount,
            'refund_method' => $data['refund_method'],
            'reason' => trim($data['reason']),
        ], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE));

        try {
            $result = DB::transaction(function () use ($request, $sale, $data, $amount, $idempotencyKey, $requestFingerprint): array {
                $row = DB::table('pos_sales')->where('id', $sale)->lockForUpdate()->first();
                if (! $row) {
                    throw new \RuntimeException('POS sale not found.');
                }

                $duplicateQuery = DB::table('pos_refunds')
                    ->where('pos_sale_id', $sale)
                    ->where('metadata->idempotency_key', $idempotencyKey);
                $existing = $duplicateQuery->lockForUpdate()->first();
                if ($existing) {
                    $metadata = json_decode((string) $existing->metadata, true) ?: [];
                    if (($metadata['request_fingerprint'] ?? null) !== $requestFingerprint) {
                        throw new \RuntimeException('Idempotency key was already used for a different refund request.');
                    }

                    return ['refund_id' => (int) $existing->id, 'replayed' => true];
                }

                $saleTotal = $this->posAmountUnits($row->total_amount);
                $alreadyRefunded = $this->posAmountUnits(DB::table('pos_refunds')
                    ->where('pos_sale_id', $sale)
                    ->whereIn('status', ['recorded', 'processed'])
                    ->sum('amount'));
                $requested = $this->posAmountUnits($amount);
                $remaining = max(0, $saleTotal - $alreadyRefunded);
                if ($requested > $remaining) {
                    throw new \RuntimeException('Refund exceeds remaining sale total.');
                }

                $refundId = DB::table('pos_refunds')->insertGetId([
                    'pos_sale_id' => $sale,
                    'amount' => $amount,
                    'currency_code' => $row->currency_code ?? 'USD',
                    'refund_method' => $data['refund_method'],
                    'reason' => $data['reason'],
                    'status' => 'recorded',
                    'processed_by' => $request->user()?->id,
                    'processed_at' => now(),
                    'metadata' => json_encode([
                        'saved_via' => 'admin.web',
                        'gateway_action' => 'not_sent',
                        'idempotency_key' => $idempotencyKey,
                        'request_fingerprint' => $requestFingerprint,
                    ]),
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);

                $newRefunded = $alreadyRefunded + $requested;
                DB::table('pos_sales')->where('id', $sale)->update([
                    'payment_status' => $newRefunded >= $saleTotal ? 'refunded' : 'partial_refund',
                    'status' => $newRefunded >= $saleTotal ? 'refunded' : $row->status,
                    'updated_at' => now(),
                ]);

                return ['refund_id' => $refundId, 'replayed' => false];
            }, 3);
        } catch (\RuntimeException $exception) {
            return back()->with('error', $exception->getMessage());
        }

        if ($result['replayed']) {
            return back()->with('status', 'POS refund was already recorded; duplicate request ignored.');
        }

        $this->auditAdminAction($request, 'pos_refund_recorded', 'pos_refunds', $result['refund_id'], ['pos_sale_id' => $sale, 'amount' => $amount, 'refund_method' => $data['refund_method'], 'idempotency_key' => $idempotencyKey ?: null]);

        return back()->with('status', 'POS refund recorded.');
    }

    public function storePosOfflineSyncEvent(Request $request): RedirectResponse
    {
        if (! Schema::hasTable('pos_offline_sync_events')) {
            return back()->with('error', 'POS offline sync event table is not available yet.');
        }

        $data = $request->validate([
            'pos_terminal_id' => ['nullable', 'integer', 'exists:pos_terminals,id'],
            'pos_session_id' => ['nullable', 'integer', 'exists:pos_sessions,id'],
            'event_uuid' => ['nullable', 'string', 'max:120'],
            'event_type' => ['required', 'string', 'max:80'],
            'payload' => ['nullable', 'string', 'max:20000'],
            'occurred_at' => ['nullable', 'date'],
            'note' => ['nullable', 'string', 'max:1000'],
        ]);

        $payload = null;
        if (! empty($data['payload'])) {
            $decoded = json_decode($data['payload'], true);
            if (json_last_error() !== JSON_ERROR_NONE) {
                return back()->with('error', 'Payload must be valid JSON.');
            }
            $payload = json_encode($decoded);
        }

        $id = DB::table('pos_offline_sync_events')->insertGetId([
            'pos_terminal_id' => $data['pos_terminal_id'] ?? null,
            'pos_session_id' => $data['pos_session_id'] ?? null,
            'event_uuid' => $data['event_uuid'] ?: 'manual-'.Str::uuid(),
            'event_type' => $data['event_type'],
            'status' => 'pending',
            'attempts' => 0,
            'payload' => $payload,
            'occurred_at' => $data['occurred_at'] ?? now(),
            'metadata' => json_encode(['created_via' => 'admin.web', 'note' => $data['note'] ?? null]),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $this->auditAdminAction($request, 'pos_offline_sync_event_created', 'pos_offline_sync_events', $id, ['event_type' => $data['event_type']]);

        return back()->with('status', 'POS offline sync event queued.');
    }

    public function updatePosOfflineSyncEvent(Request $request, int $event): RedirectResponse
    {
        if (! Schema::hasTable('pos_offline_sync_events')) {
            return back()->with('error', 'POS offline sync event table is not available yet.');
        }

        $data = $request->validate([
            'action' => ['required', 'in:mark_processing,mark_processed,mark_failed,ignore,retry'],
            'error_message' => ['nullable', 'string', 'max:2000'],
        ]);

        $row = DB::table('pos_offline_sync_events')->where('id', $event)->first();
        if (! $row) {
            return back()->with('error', 'POS offline sync event not found.');
        }

        $status = match ($data['action']) {
            'mark_processing' => 'processing',
            'mark_processed' => 'processed',
            'mark_failed' => 'failed',
            'ignore' => 'ignored',
            'retry' => 'pending',
        };

        DB::table('pos_offline_sync_events')->where('id', $event)->update([
            'status' => $status,
            'attempts' => $data['action'] === 'retry' ? (int) $row->attempts + 1 : $row->attempts,
            'error_message' => $data['error_message'] ?? ($data['action'] === 'retry' ? null : $row->error_message),
            'processed_at' => in_array($data['action'], ['mark_processed', 'ignore'], true) ? now() : $row->processed_at,
            'processed_by' => in_array($data['action'], ['mark_processed', 'ignore'], true) ? $request->user()?->id : $row->processed_by,
            'updated_at' => now(),
        ]);

        $this->auditAdminAction($request, 'pos_offline_sync_event_'.$data['action'], 'pos_offline_sync_events', $event, ['status' => $status]);

        return back()->with('status', 'POS offline sync event updated.');
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

        $notifications = app(OrderNotificationService::class);
        if ($email = $notifications->recipient($row)) {
            $notifications->orderStatus($email, $row->order_number, $data['status'], $row->id, $row->marketplace_id);
        }

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

        $updatedOrder = DB::table('orders')->where('id', $order)->first();
        $notifications = app(OrderNotificationService::class);
        if ($updatedOrder && $updatedOrder->tracking_number && ($email = $notifications->recipient($updatedOrder))) {
            $notifications->tracking($email, $updatedOrder);
        }

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
            'related_product_id' => ['nullable', 'integer', 'exists:products,id'],
            'related_order_id' => ['nullable', 'integer', 'exists:orders,id'],
        ]);

        $ticketNumber = 'SUP-'.now()->format('Ymd-His').'-'.random_int(100, 999);
        [$firstResponseDue, $slaDue] = $this->supportDueDates($data['priority']);
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
            'support_channel' => $data['channel'] ?? 'admin',
            'first_response_due_at' => $firstResponseDue,
            'sla_due_at' => $slaDue,
            'related_product_id' => $data['related_product_id'] ?? null,
            'related_order_id' => $data['related_order_id'] ?? null,
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
            'related_product_id' => ['nullable', 'integer', 'exists:products,id'],
            'related_order_id' => ['nullable', 'integer', 'exists:orders,id'],
            'support_channel' => ['nullable', 'string', 'max:80'],
            'sla_due_at' => ['nullable', 'date'],
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
            'support_channel' => $data['support_channel'] ?? $row->support_channel ?? 'admin',
            'related_product_id' => $data['related_product_id'] ?? null,
            'related_order_id' => $data['related_order_id'] ?? null,
            'sla_due_at' => $data['sla_due_at'] ?? $row->sla_due_at ?? null,
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

    public function escalateSupportTicket(Request $request, int $ticket): RedirectResponse
    {
        $data = $request->validate([
            'escalation_level' => ['required', 'integer', 'min:1', 'max:5'],
            'reason' => ['nullable', 'string', 'max:1000'],
        ]);

        $row = DB::table('support_tickets')->where('id', $ticket)->first();
        if (! $row) {
            return back()->with('error', 'Support ticket not found.');
        }

        DB::table('support_tickets')->where('id', $ticket)->update([
            'status' => $row->status === 'open' ? 'in_progress' : $row->status,
            'escalation_level' => $data['escalation_level'],
            'escalated_at' => now(),
            'metadata' => $this->mergeJsonMeta($row->metadata ?? null, [
                'last_escalation_reason' => $data['reason'] ?? null,
                'last_escalated_by' => $request->user()?->id,
                'last_escalated_at' => now()->toIso8601String(),
            ]),
            'updated_at' => now(),
        ]);

        DB::table('support_ticket_messages')->insert([
            'support_ticket_id' => $ticket,
            'user_id' => $request->user()?->id,
            'sender_type' => 'system',
            'message' => 'Escalated to level '.$data['escalation_level'].($data['reason'] ? ': '.$data['reason'] : '.'),
            'metadata' => json_encode(['event' => 'ticket.escalated']),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $this->auditAdminAction($request, 'support_ticket_escalated', 'support_tickets', $ticket, [
            'ticket_number' => $row->ticket_number,
            'level' => $data['escalation_level'],
        ]);

        return back()->with('status', "Support ticket {$row->ticket_number} escalated.");
    }

    public function storeSupportTicketMessage(Request $request, int $ticket): RedirectResponse
    {
        $data = $request->validate([
            'message' => ['required', 'string', 'max:5000'],
            'sender_type' => ['required', 'in:admin,customer,seller,system,ai'],
            'mark_status' => ['nullable', 'in:open,in_progress,waiting_customer,resolved,closed'],
            'attachment' => ['nullable', 'file', 'max:51200', 'mimes:jpg,jpeg,png,webp,pdf,csv,txt,zip'],
        ]);

        $row = DB::table('support_tickets')->where('id', $ticket)->first();
        if (! $row) {
            return back()->with('error', 'Support ticket not found.');
        }

        $attachment = ['disk' => null, 'path' => null, 'original_name' => null, 'mime_type' => null, 'size' => null];
        if ($request->hasFile('attachment')) {
            $file = $request->file('attachment');
            $attachment = [
                'disk' => 'public',
                'path' => $file->storeAs('support-attachments/'.$ticket, Str::uuid().'.'.$file->getClientOriginalExtension(), 'public'),
                'original_name' => $file->getClientOriginalName(),
                'mime_type' => $file->getClientMimeType(),
                'size' => $file->getSize(),
            ];
        }

        DB::transaction(function () use ($request, $ticket, $row, $data, $attachment) {
            DB::table('support_ticket_messages')->insert([
                'support_ticket_id' => $ticket,
                'user_id' => $request->user()?->id,
                'sender_type' => $data['sender_type'],
                'message' => $data['message'],
                'metadata' => json_encode([
                    'event' => 'message.created',
                    'attachment_url' => $attachment['path'] ? Storage::disk('public')->url($attachment['path']) : null,
                ]),
                'attachment_disk' => $attachment['disk'],
                'attachment_path' => $attachment['path'],
                'attachment_original_name' => $attachment['original_name'],
                'attachment_mime_type' => $attachment['mime_type'],
                'attachment_size' => $attachment['size'],
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            $update = [
                'status' => $data['mark_status'] ?? $row->status,
                'updated_at' => now(),
            ];
            if (! $row->first_responded_at && $data['sender_type'] === 'admin') {
                $update['first_responded_at'] = now();
            }
            DB::table('support_tickets')->where('id', $ticket)->update($update);
        });

        $this->auditAdminAction($request, 'support_ticket_message_added', 'support_tickets', $ticket, [
            'ticket_number' => $row->ticket_number,
            'sender_type' => $data['sender_type'],
        ]);

        return back()->with('status', "Message added to {$row->ticket_number}.");
    }

    // ---- Users ----------------------------------------------------------------

    public function sendPasswordReset(int $user, \App\Services\Marketing\AccountCommunicationService $communications): RedirectResponse
    {
        $record = \App\Models\User::find($user);
        if (! $record) {
            return back()->with('error', 'User not found.');
        }

        $token = \Illuminate\Support\Facades\Password::broker()->createToken($record);
        $communications->passwordReset($record, $token, route('password.reset', ['token' => $token, 'email' => $record->email]));

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

    public function updateApplicationStatus(Request $request, string $type, int $id, \App\Services\Marketing\TransactionalCommunicationService $communications): RedirectResponse
    {
        abort_unless(in_array($type, ['seller', 'distributor'], true), 404);
        $table = $type . '_applications';

        // Same whitelist as the Onboarding module's ApplicationStatusRequest.
        $data = $request->validate([
            'status' => ['required', 'in:pending,contacted,approved_for_onboarding,rejected,archived'],
            'admin_notes' => ['nullable', 'string', 'max:3000'],
        ]);

        $application = DB::table($table)->where('id', $id)->first();
        $updated = $application ? DB::table($table)->where('id', $id)->update([
            'status' => $data['status'],
            'admin_notes' => $data['admin_notes'] ?? DB::raw('admin_notes'),
            'reviewed_at' => now(),
            'updated_at' => now(),
        ]) : 0;
        if ($updated && $data['status'] === 'approved_for_onboarding' && filter_var($application->email ?? null, FILTER_VALIDATE_EMAIL)) {
            $communications->queue($type.'_application_approved', $application->email, [
                'customer_name' => $application->contact_name ?? $application->business_name ?? 'Applicant',
                'related_type' => $type.'_application', 'related_id' => $id,
                'country_id' => $application->country_id ?? null, 'event_id' => 'approved-'.$id,
            ]);
        }

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

    /**
     * Normalize the legacy JSON permission list before synchronizing it with the
     * role_permissions pivot table.
     *
     * @return list<string>
     */
    private function permissionKeys(mixed $value): array
    {
        if (is_string($value)) {
            $value = json_decode($value, true);
        }

        if (! is_array($value)) {
            return [];
        }

        $keys = array_map(
            static fn (mixed $key): string => trim((string) $key),
            $value
        );

        return array_values(array_unique(array_filter(
            $keys,
            static fn (string $key): bool => $key !== ''
        )));
    }

    /**
     * Convert a POS decimal amount into four-decimal fixed-point units so the
     * refund limit is never checked with floating-point arithmetic.
     */
    private function posAmountUnits(mixed $value): int
    {
        $decimal = trim((string) $value);
        if (! preg_match('/^(\d+)(?:\.(\d+))?$/', $decimal, $matches)) {
            throw new \RuntimeException('POS amount must be a non-negative decimal value.');
        }

        $fraction = $matches[2] ?? '';
        if (strlen($fraction) > 4 && trim(substr($fraction, 4), '0') !== '') {
            throw new \RuntimeException('POS amount supports at most four decimal places.');
        }

        $whole = (int) $matches[1];
        $fractionUnits = (int) str_pad(substr($fraction, 0, 4), 4, '0');

        return ($whole * 10000) + $fractionUnits;
    }

    private function formatPosAmount(int $units): string
    {
        return intdiv($units, 10000).'.'.str_pad((string) ($units % 10000), 4, '0', STR_PAD_LEFT);
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

    private function supportDueDates(string $priority): array
    {
        return match ($priority) {
            'urgent' => [now()->addMinutes(30), now()->addHours(4)],
            'high' => [now()->addHours(2), now()->addHours(12)],
            'medium' => [now()->addHours(8), now()->addDays(2)],
            default => [now()->addDay(), now()->addDays(5)],
        };
    }
}
