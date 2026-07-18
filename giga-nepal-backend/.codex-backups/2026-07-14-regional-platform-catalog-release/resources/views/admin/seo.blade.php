@extends('admin.layout')

@section('title','SEO Console')
@section('crumb','Admin Console / SEO')
@section('page_actions')
<details class="modal"><summary class="btn btn-primary">Add SEO Page</summary>
    <div class="modal-panel"><div class="modal-h"><h3>Create SEO Page</h3><span class="badge b-info">source-aware</span></div>
        <form class="modal-b form-stack" method="post" action="/admin/seo/pages">@csrf
            <div class="form-grid">
                <div class="field"><label>Path</label><input class="control mono" name="url_path" placeholder="/products" required></div>
                <div class="field"><label>Robots</label><select class="control" name="robots"><option>index,follow</option><option>noindex,nofollow</option></select></div>
            </div>
            <div class="field"><label>Meta title</label><input class="control" name="title"></div>
            <div class="field"><label>Meta description</label><textarea class="control" name="meta_description"></textarea></div>
            <div class="form-grid">
                <div class="field"><label>Canonical URL</label><input class="control" name="canonical_url"></div>
                <div class="field"><label>OpenGraph image</label><input class="control" name="og_image"></div>
                <div class="field"><label>Schema type</label><input class="control" name="schema_type" value="WebPage"></div>
                <div class="field"><label>Confidence</label><input class="control" name="confidence_level" value="manual"></div>
            </div>
            <div class="form-grid"><div class="field"><label>Source name</label><input class="control" name="source_name" value="manual"></div><div class="field"><label>Source URL</label><input class="control" name="source_url"></div></div>
            <button class="btn btn-primary" type="submit">Save SEO Page</button>
        </form>
    </div>
</details>
<details class="modal"><summary class="btn btn-ghost">Create Redirect</summary>
    <div class="modal-panel"><div class="modal-h"><h3>Create Redirect</h3><span class="badge b-warn">confirm paths</span></div>
        <form class="modal-b form-stack" method="post" action="/admin/seo/redirects">@csrf
            <div class="field"><label>From path</label><input class="control mono" name="from_path" placeholder="/old-page" required></div>
            <div class="field"><label>To URL</label><input class="control mono" name="to_url" placeholder="/new-page" required></div>
            <div class="form-grid"><div class="field"><label>Status code</label><select class="control" name="status_code"><option>301</option><option>302</option><option>307</option><option>308</option></select></div><div class="field"><label>Active</label><select class="control" name="is_active"><option value="1">Active</option><option value="0">Inactive</option></select></div></div>
            <div class="field"><label>Notes</label><textarea class="control" name="notes"></textarea></div>
            <button class="btn btn-primary" type="submit">Save Redirect</button>
        </form>
    </div>
</details>
@endsection

@section('content')
<div class="grid kpis">
    <div class="kpi"><div class="t">SEO pages</div><div class="v tnum">{{ number_format($pages->total()) }}</div><div class="s">managed metadata</div></div>
    <div class="kpi"><div class="t">Redirects</div><div class="v tnum">{{ number_format($redirects->count()) }}</div><div class="s">recent rules</div></div>
    <div class="kpi"><div class="t">Product meta</div><div class="v tnum">{{ number_format($productMetaCount) }}</div><div class="s">product SEO rows</div></div>
    <div class="kpi"><div class="t">SEO score</div><div class="v">78</div><div class="s">placeholder score</div></div>
</div>

<section class="card">
    <div class="card-h"><div><h2>SEO Pages</h2><div class="sub">Meta, canonical, robots, schema and source confidence</div></div><button class="btn btn-ghost" type="button">Regenerate Sitemap</button></div>
    <form class="filters" method="get"><select class="control" name="robots"><option value="">All robots</option><option value="index,follow" @selected($filters['robots']==='index,follow')>index,follow</option><option value="noindex,nofollow" @selected($filters['robots']==='noindex,nofollow')>noindex,nofollow</option></select><input class="control" name="q" value="{{ $filters['q'] }}" placeholder="Search path or title"><button class="btn btn-ghost" type="submit">Filter</button></form>
    <div class="tabs"><span class="tab active">Pages</span><span class="tab">Product SEO</span><span class="tab">Category SEO</span><span class="tab">llms.txt preview</span></div>
    <div class="scroll-x"><table class="tbl">
        <thead><tr><th>Path</th><th>Title</th><th>Description</th><th>Robots</th><th>Source</th><th>Confidence</th><th>Actions</th></tr></thead>
        <tbody>
        @forelse($pages as $page)
            <tr>
                <td class="mono">{{ $page->url_path }}</td>
                <td>{{ $page->title }}</td>
                <td>{{ \Illuminate\Support\Str::limit($page->meta_description, 70) }}</td>
                <td><span class="badge {{ $page->is_indexable ? 'b-ok':'b-warn' }}">{{ $page->robots }}</span></td>
                <td>{{ $page->source_name ?: 'manual' }}</td>
                <td>{{ $page->confidence_level }}</td>
                <td><details class="modal"><summary class="btn btn-ghost">Edit</summary><div class="modal-panel"><div class="modal-h"><h3>Edit {{ $page->url_path }}</h3></div><form class="modal-b form-stack" method="post" action="/admin/seo/pages">@csrf<input type="hidden" name="url_path" value="{{ $page->url_path }}"><div class="field"><label>Title</label><input class="control" name="title" value="{{ $page->title }}"></div><div class="field"><label>Description</label><textarea class="control" name="meta_description">{{ $page->meta_description }}</textarea></div><div class="field"><label>Robots</label><input class="control" name="robots" value="{{ $page->robots }}"></div><button class="btn btn-primary" type="submit">Save</button></form></div></details></td>
            </tr>
        @empty
            <tr><td colspan="7"><div class="empty"><h3>No SEO pages configured</h3><p>Add page metadata from the action button.</p></div></td></tr>
        @endforelse
        </tbody>
    </table></div>
</section>

<section class="card stack-gap">
    <div class="card-h"><div><h2>Redirect Manager</h2><div class="sub">Create, edit and delete redirect rules</div></div><span class="mono">{{ $sitemapUrl }}</span></div>
    <div class="scroll-x"><table class="tbl">
        <thead><tr><th>From</th><th>To</th><th>Status</th><th>Active</th><th>Actions</th></tr></thead>
        <tbody>
        @forelse($redirects as $redirect)
            <tr>
                <td class="mono">{{ $redirect->from_path }}</td>
                <td class="mono">{{ $redirect->to_url }}</td>
                <td>{{ $redirect->status_code }}</td>
                <td><span class="badge {{ $redirect->is_active ? 'b-ok':'b-muted' }}">{{ $redirect->is_active ? 'active':'inactive' }}</span></td>
                <td><form method="post" action="/admin/seo/redirects/{{ $redirect->id }}" onsubmit="return confirm('Delete this redirect?')">@csrf @method('DELETE')<button class="btn btn-ghost danger" type="submit">Delete</button></form></td>
            </tr>
        @empty
            <tr><td colspan="5"><div class="empty"><h3>No redirects</h3><p>Redirect rules will appear here after they are added.</p></div></td></tr>
        @endforelse
        </tbody>
    </table></div>
</section>

<div style="margin-top:14px">{{ $pages->links() }}</div>
@endsection
