@extends('admin.layout')
@section('title','Category Detail')
@section('crumb','Catalog / Category Detail')

@section('page_actions')
<a class="btn btn-ghost" href="/admin/categories">Back to Categories</a>
<form method="post" action="/admin/categories/{{ $category->id }}/toggle">@csrf<button class="btn btn-ghost" type="submit">{{ $category->is_active ? 'Deactivate' : 'Activate' }}</button></form>
@endsection

@section('content')
<div class="grid kpis">
    <div class="kpi"><div class="t">Category</div><div class="v">{{ $category->name }}</div><div class="s mono">{{ $category->slug }}</div></div>
    <div class="kpi"><div class="t">Products</div><div class="v tnum">{{ number_format($productCount) }}</div><div class="s">directly assigned</div></div>
    <div class="kpi"><div class="t">Children</div><div class="v tnum">{{ number_format($children->count()) }}</div><div class="s">subcategories</div></div>
    <div class="kpi"><div class="t">Status</div><div class="v">{{ $category->is_active ? 'Active' : 'Hidden' }}</div><div class="s">{{ $category->is_featured ? 'featured' : 'standard' }}</div></div>
</div>

<div class="grid split">
    <section class="card">
        <div class="card-h"><h2>Edit Category</h2><span class="badge b-info">core fields</span></div>
        <form class="form-stack" method="post" action="/admin/categories" style="padding:16px">
            @csrf
            <input type="hidden" name="id" value="{{ $category->id }}">
            <div class="form-grid">
                <div class="field"><label>Name</label><input class="control" name="name" value="{{ $category->name }}" required></div>
                <div class="field"><label>Slug</label><input class="control mono" name="slug" value="{{ $category->slug }}"></div>
                <div class="field"><label>Parent</label><select class="control" name="parent_id"><option value="">Root category</option>@foreach($allCategories as $cat)<option value="{{ $cat->id }}" @selected($category->parent_id===$cat->id)>{{ $cat->name }}</option>@endforeach</select></div>
                <div class="field"><label>Sort order</label><input class="control" type="number" name="sort_order" value="{{ $category->sort_order }}"></div>
            </div>
            <div class="field"><label>Description</label><textarea class="control" name="description">{{ $category->description }}</textarea></div>
            <div class="form-grid">
                <div class="field"><label>Icon path</label><input class="control" name="icon_path" value="{{ $category->icon_path }}"></div>
                <div class="field"><label>Image path</label><input class="control" name="image_path" value="{{ $category->image_path }}"></div>
                <div class="field"><label>Media asset</label><select class="control" name="media_asset_id"><option value="">Keep current media</option>@foreach($mediaAssets as $asset)<option value="{{ $asset->id }}">{{ $asset->title ?: $asset->original_name }}</option>@endforeach</select></div>
                <div class="field"><label>Country visibility</label><input class="control" name="country_visibility" value="{{ $seo->country_visibility ?? $visibility->country_visibility ?? '' }}"></div>
            </div>
            <div class="form-grid">
                <div class="field"><label>SEO title</label><input class="control" name="seo_title" value="{{ $seo->title ?? '' }}"></div>
                <div class="field"><label>LMS topic</label><input class="control" name="lms_topic" value="{{ $seo->lms_topic ?? '' }}"></div>
            </div>
            <div class="field"><label>SEO description</label><textarea class="control" name="seo_description">{{ $seo->description ?? '' }}</textarea></div>
            <label><input type="checkbox" name="is_active" value="1" @checked($category->is_active)> Active</label>
            <label><input type="checkbox" name="is_featured" value="1" @checked($category->is_featured)> Featured</label>
            <div class="sub">source_notes: {{ $seo->source_notes ?? 'manual admin metadata' }} · confidence_level: {{ $seo->confidence_level ?? 'manual' }} · last_updated: {{ $seo->last_updated ?? $category->updated_at }} · Advisory only</div>
            <button class="btn btn-primary" type="submit">Save Category</button>
        </form>
    </section>

    <section class="card">
        <div class="card-h"><h2>LMS Topic Links</h2><span class="badge b-info">courses + projects</span></div>
        <div style="padding:16px">
            @forelse($lmsLinks as $link)
                <div style="display:flex;justify-content:space-between;gap:10px;padding:8px 0;border-bottom:1px solid var(--line)">
                    <span><strong>{{ $link->title }}</strong><div class="sub">{{ $link->relation_type }} · {{ $link->course_title ?: $link->project_title ?: 'manual topic' }}</div></span>
                    <form method="post" action="/admin/categories/{{ $category->id }}/lms-links/{{ $link->id }}">@csrf @method('DELETE')<button class="btn btn-ghost danger" type="submit">Deactivate</button></form>
                </div>
            @empty
                <div class="empty"><h3>No LMS links</h3></div>
            @endforelse
            <form class="form-stack" method="post" action="/admin/categories/{{ $category->id }}/lms-links" style="margin-top:14px">
                @csrf
                <div class="form-grid">
                    <div class="field"><label>Title</label><input class="control" name="title" required></div>
                    <div class="field"><label>Course</label><select class="control" name="lms_course_id"><option value="">None</option>@foreach($courses as $course)<option value="{{ $course->id }}">{{ $course->title }}</option>@endforeach</select></div>
                    <div class="field"><label>Project</label><select class="control" name="lms_project_id"><option value="">None</option>@foreach($projects as $project)<option value="{{ $project->id }}">{{ $project->title }}</option>@endforeach</select></div>
                    <div class="field"><label>Relation</label><select class="control" name="relation_type"><option>topic</option><option>course</option><option>project</option><option>lab_kit</option></select></div>
                </div>
                <div class="field"><label>Notes</label><textarea class="control" name="notes"></textarea></div>
                <button class="btn" type="submit">Add LMS Link</button>
            </form>
        </div>
    </section>
</div>

<div class="grid split stack-gap">
    <section class="card">
        <div class="card-h"><h2>Hierarchy Controls</h2><span class="badge b-info">audited</span></div>
        <div style="padding:16px">
            <form class="form-stack" method="post" action="/admin/categories/{{ $category->id }}/move">@csrf
                <div class="field"><label>Move under existing parent</label><select class="control" name="parent_id" required><option value="">Select parent</option>@foreach($allCategories as $cat)<option value="{{ $cat->id }}" @selected($category->parent_id===$cat->id)>{{ $cat->name }}</option>@endforeach</select></div>
                <button class="btn" type="submit">Move Category</button>
            </form>
            <form class="form-stack" method="post" action="/admin/categories/{{ $category->id }}/merge" style="margin-top:16px">@csrf
                <div class="field"><label>Merge duplicate into canonical category</label><select class="control" name="target_category_id" required><option value="">Select canonical category</option>@foreach($allCategories as $cat)<option value="{{ $cat->id }}">{{ $cat->name }}</option>@endforeach</select></div>
                <button class="btn btn-ghost danger" type="submit">Merge and Deactivate Source</button>
            </form>
        </div>
    </section>
    <section class="card">
        <div class="card-h"><h2>Synonyms</h2><span class="badge b-info">{{ number_format($categorySynonyms->count()) }}</span></div>
        <div style="padding:16px">
            @forelse($categorySynonyms as $synonym)
                <div style="display:flex;justify-content:space-between;gap:10px;padding:8px 0;border-bottom:1px solid var(--line)"><span>{{ $synonym->synonym }}<div class="sub mono">{{ $synonym->normalized_synonym }}</div></span><form method="post" action="/admin/categories/{{ $category->id }}/synonyms/{{ $synonym->id }}">@csrf @method('DELETE')<button class="btn btn-ghost danger" type="submit">Delete</button></form></div>
            @empty
                <div class="sub">No aliases configured.</div>
            @endforelse
            <form class="form-stack" method="post" action="/admin/categories/{{ $category->id }}/synonyms" style="margin-top:14px">@csrf
                <div class="form-grid"><div class="field"><label>Synonym</label><input class="control" name="synonym" placeholder="op amp" required></div><div class="field"><label>Confidence</label><input class="control" type="number" name="confidence" min="0" max="1" step="0.01" value="1"></div></div>
                <button class="btn" type="submit">Add Synonym</button>
            </form>
        </div>
    </section>
</div>

<div class="grid split stack-gap">
    <section class="card">
        <div class="card-h"><h2>Spec Templates</h2><span class="badge b-info">{{ number_format($specTemplates->count()) }}</span></div>
        <div style="padding:16px">
            @forelse($specTemplates as $template)
                <details style="border-bottom:1px solid var(--line);padding:10px 0" open>
                    <summary style="cursor:pointer"><strong>{{ $template->name }}</strong> <span class="sub">sort {{ $template->sort_order }} · {{ $template->is_required ? 'required' : 'optional' }}</span></summary>
                    <p class="sub">{{ $template->description ?: 'No description.' }}</p>
                    <div class="scroll-x"><table class="tbl"><thead><tr><th>Field</th><th>Type</th><th>Unit</th><th>Required</th><th></th></tr></thead><tbody>
                        @forelse($template->fields as $field)
                            <tr><td><strong>{{ $field->field_label }}</strong><div class="sub mono">{{ $field->field_name }}</div></td><td>{{ $field->field_type }}</td><td>{{ $field->unit ?: '—' }}</td><td>{{ $field->is_required ? 'Yes' : 'No' }}</td><td><form method="post" action="/admin/categories/{{ $category->id }}/spec-templates/{{ $template->id }}/fields/{{ $field->id }}">@csrf @method('DELETE')<button class="btn btn-ghost danger" type="submit">Delete</button></form></td></tr>
                        @empty
                            <tr><td colspan="5"><div class="sub">No fields yet.</div></td></tr>
                        @endforelse
                    </tbody></table></div>
                    <form class="form-stack" method="post" action="/admin/categories/{{ $category->id }}/spec-templates/{{ $template->id }}/fields" style="margin-top:12px">@csrf
                        <div class="form-grid">
                            <div class="field"><label>Field name</label><input class="control mono" name="field_name" placeholder="voltage" required></div>
                            <div class="field"><label>Label</label><input class="control" name="field_label" placeholder="Voltage" required></div>
                            <div class="field"><label>Type</label><select class="control" name="field_type"><option>text</option><option>number</option><option>select</option><option>boolean</option><option>range</option></select></div>
                            <div class="field"><label>Unit</label><input class="control" name="unit" placeholder="V"></div>
                            <div class="field"><label>Sort</label><input class="control" type="number" name="sort_order" value="0"></div>
                        </div>
                        <div class="field"><label>Select options (one per line)</label><textarea class="control" name="options" rows="3"></textarea></div>
                        <div class="form-grid"><div class="field"><label>Validation rules</label><input class="control" name="validation_rules" placeholder="nullable|numeric"></div><label><input type="checkbox" name="is_required" value="1"> Required field</label></div>
                        <div class="field"><label>Help text</label><textarea class="control" name="help_text"></textarea></div>
                        <button class="btn" type="submit">Add Field</button>
                    </form>
                    <form method="post" action="/admin/categories/{{ $category->id }}/spec-templates/{{ $template->id }}" style="margin-top:12px">@csrf @method('DELETE')<button class="btn btn-ghost danger" type="submit">Delete Template</button></form>
                </details>
            @empty
                <div class="empty"><h3>No spec templates</h3><p class="sub">Create a template so products in this category can use structured technical specifications.</p></div>
            @endforelse
        </div>
    </section>

    <section class="card">
        <div class="card-h"><h2>Create Spec Template</h2><span class="badge b-info">category schema</span></div>
        <form class="form-stack" method="post" action="/admin/categories/{{ $category->id }}/spec-templates" style="padding:16px">@csrf
            <div class="form-grid">
                <div class="field"><label>Name</label><input class="control" name="name" placeholder="Electrical Specifications" required></div>
                <div class="field"><label>Sort order</label><input class="control" type="number" name="sort_order" value="0"></div>
            </div>
            <div class="field"><label>Description</label><textarea class="control" name="description" placeholder="Voltage, current, package, protocol and other fields for this category."></textarea></div>
            <label><input type="checkbox" name="is_required" value="1"> Required for products in this category</label>
            <div class="sub">source_notes: manual admin category schema · confidence_level: manual · Advisory only</div>
            <button class="btn btn-primary" type="submit">Create Template</button>
        </form>
    </section>
</div>

<div class="grid split stack-gap">
    <section class="card">
        <div class="card-h"><h2>Child Categories</h2><span class="badge b-info">{{ number_format($children->count()) }}</span></div>
        <div class="scroll-x"><table class="tbl"><thead><tr><th>Name</th><th>Slug</th><th>Status</th><th></th></tr></thead><tbody>@forelse($children as $child)<tr><td>{{ $child->name }}</td><td class="mono">{{ $child->slug }}</td><td><span class="badge {{ $child->is_active ? 'b-ok':'b-muted' }}">{{ $child->is_active ? 'Active':'Hidden' }}</span></td><td><a class="btn btn-ghost" href="/admin/categories/{{ $child->id }}">Open</a></td></tr>@empty<tr><td colspan="4"><div class="empty"><h3>No child categories</h3></div></td></tr>@endforelse</tbody></table></div>
    </section>
    <section class="card">
        <div class="card-h"><h2>Products</h2><span class="badge b-info">{{ number_format($productCount) }}</span></div>
        <div class="scroll-x"><table class="tbl"><thead><tr><th>Product</th><th>SKU</th><th>Status</th><th></th></tr></thead><tbody>@forelse($products as $p)<tr><td>{{ $p->name }}</td><td class="mono">{{ $p->sku }}</td><td><span class="badge b-muted">{{ $p->status }}</span></td><td><a class="btn btn-ghost" href="/admin/products/{{ $p->id }}">Open</a></td></tr>@empty<tr><td colspan="4"><div class="empty"><h3>No products assigned</h3></div></td></tr>@endforelse</tbody></table></div>
    </section>
</div>
@endsection
