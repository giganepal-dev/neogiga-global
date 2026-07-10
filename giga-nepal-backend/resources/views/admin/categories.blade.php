@extends('admin.layout')
@section('title','Categories')
@section('crumb','Catalog / Category Manager')
@section('page_actions')
<details class="modal">
    <summary class="btn btn-primary">Add Category</summary>
    <div class="modal-panel">
        <div class="modal-h"><h3>Add Category</h3><span class="badge b-info">root or child</span></div>
        <form class="modal-b form-stack" method="post" action="/admin/categories">@csrf
            <div class="form-grid">
                <div class="field"><label>Name</label><input class="control" name="name" required></div>
                <div class="field"><label>Slug</label><input class="control mono" name="slug"></div>
                <div class="field"><label>Parent</label><select class="control" name="parent_id"><option value="">Root category</option>@foreach($allCategories as $cat)<option value="{{ $cat->id }}">{{ $cat->name }}</option>@endforeach</select></div>
                <div class="field"><label>Sort order</label><input class="control" type="number" name="sort_order" value="100"></div>
            </div>
            <div class="field"><label>Description</label><textarea class="control" name="description"></textarea></div>
            <div class="form-grid">
                <div class="field"><label>Icon path</label><input class="control" name="icon_path" placeholder="/storage/icons/..."></div>
                <div class="field"><label>Country visibility</label><input class="control" name="country_visibility" placeholder="Global, Nepal, India"></div>
                <div class="field"><label>SEO title</label><input class="control" name="seo_title"></div>
                <div class="field"><label>LMS topic link</label><input class="control" name="lms_topic"></div>
            </div>
            <div class="field"><label>SEO description</label><textarea class="control" name="seo_description"></textarea></div>
            <label><input type="checkbox" name="is_active" value="1" checked> Active</label>
            <label><input type="checkbox" name="is_featured" value="1"> Featured</label>
            <button class="btn btn-primary" type="submit">Save Category</button>
        </form>
    </div>
</details>
<button class="btn btn-ghost" type="button">Import CSV</button>
<button class="btn btn-ghost" type="button">Export CSV</button>
@endsection
@section('content')

<div class="grid kpis">
    <div class="kpi"><div class="t">Total categories</div><div class="v tnum">{{ number_format($total) }}</div><div class="s">taxonomy nodes</div></div>
    <div class="kpi"><div class="t">Root branches</div><div class="v tnum">{{ number_format($roots->count()) }}</div><div class="s">top level</div></div>
    <div class="kpi"><div class="t">Active manager</div><div class="v">CRUD</div><div class="s">create/edit/toggle</div></div>
    <div class="kpi"><div class="t">Ordering</div><div class="v">Ready</div><div class="s">drag/drop placeholder</div></div>
</div>

<section class="card">
    <div class="card-h"><div><h2>Category Manager</h2><div class="sub">Tree, filters, SEO fields, visibility and LMS link placeholders</div></div><span class="badge b-info">NeoGiga taxonomy</span></div>
    <form class="filters" method="get">
        <input class="control" name="q" value="{{ $filters['q'] }}" placeholder="Search name or slug">
        <select class="control" name="status"><option value="">All status</option><option value="active" @selected($filters['status']==='active')>Active</option><option value="inactive" @selected($filters['status']==='inactive')>Inactive</option></select>
        <button class="btn btn-ghost" type="submit">Filter</button>
        <button class="btn btn-ghost" type="button">Expand/Collapse</button>
    </form>
    @if ($roots->isEmpty())
        <div class="empty"><h3>No categories found</h3><p>Create a root category from the Add Category action.</p></div>
    @else
        <ul class="tree">
            @foreach ($roots as $root)
                <li>
                    <div class="row">
                        <span class="dot"></span>
                        <strong>{{ $root->name }}</strong>
                        @if($root->is_featured)<span class="badge b-info">Featured</span>@endif
                        <span class="badge {{ $root->is_active ? 'b-ok':'b-muted' }}">{{ $root->is_active ? 'Active':'Hidden' }}</span>
                        <span class="cnt mono">{{ $root->slug }}</span>
                        <div class="actions">
                            <details class="modal"><summary class="btn btn-ghost">Edit</summary>
                                <div class="modal-panel"><div class="modal-h"><h3>Edit {{ $root->name }}</h3></div>
                                    <form class="modal-b form-stack" method="post" action="/admin/categories">@csrf
                                        <input type="hidden" name="id" value="{{ $root->id }}">
                                        <div class="form-grid"><div class="field"><label>Name</label><input class="control" name="name" value="{{ $root->name }}" required></div><div class="field"><label>Slug</label><input class="control mono" name="slug" value="{{ $root->slug }}"></div></div>
                                        <div class="field"><label>Description</label><textarea class="control" name="description">{{ $root->description }}</textarea></div>
                                        <div class="form-grid"><div class="field"><label>Sort</label><input class="control" type="number" name="sort_order" value="{{ $root->sort_order }}"></div><div class="field"><label>Icon</label><input class="control" name="icon_path" value="{{ $root->icon_path }}"></div></div>
                                        <label><input type="checkbox" name="is_active" value="1" @checked($root->is_active)> Active</label>
                                        <label><input type="checkbox" name="is_featured" value="1" @checked($root->is_featured)> Featured</label>
                                        <button class="btn btn-primary" type="submit">Save</button>
                                    </form>
                                </div>
                            </details>
                            <form method="post" action="/admin/categories/{{ $root->id }}/toggle">@csrf<button class="btn btn-ghost" type="submit">{{ $root->is_active ? 'Deactivate':'Activate' }}</button></form>
                        </div>
                    </div>
                    @if ($root->children->isNotEmpty())
                        <ul class="kids">
                            @foreach ($root->children as $child)
                                <li>
                                    <div class="row">
                                        {{ $child->name }}
                                        <span class="badge {{ $child->is_active ? 'b-ok':'b-muted' }}">{{ $child->is_active ? 'Active':'Hidden' }}</span>
                                        <span class="cnt">{{ $child->children_count ? number_format($child->children_count).' sub' : $child->slug }}</span>
                                        <div class="actions">
                                            <details class="modal"><summary class="btn btn-ghost">View/Edit</summary><div class="modal-panel"><div class="modal-h"><h3>{{ $child->name }}</h3><span class="badge b-info">detail drawer</span></div><form class="modal-b form-stack" method="post" action="/admin/categories">@csrf<input type="hidden" name="id" value="{{ $child->id }}"><input type="hidden" name="parent_id" value="{{ $root->id }}"><div class="field"><label>Name</label><input class="control" name="name" value="{{ $child->name }}" required></div><div class="field"><label>Slug</label><input class="control mono" name="slug" value="{{ $child->slug }}"></div><div class="field"><label>Description</label><textarea class="control" name="description">{{ $child->description }}</textarea></div><label><input type="checkbox" name="is_active" value="1" @checked($child->is_active)> Active</label><label><input type="checkbox" name="is_featured" value="1" @checked($child->is_featured)> Featured</label><button class="btn btn-primary" type="submit">Save</button></form></div></details>
                                        </div>
                                    </div>
                                </li>
                            @endforeach
                        </ul>
                    @endif
                </li>
            @endforeach
        </ul>
    @endif
</section>

<div class="note stack-gap">Drag/drop order, icon picker, country visibility rules, LMS topic linkage, and CSV import/export controls are staged as UI placeholders; create/edit/toggle workflows are active.</div>

@endsection
