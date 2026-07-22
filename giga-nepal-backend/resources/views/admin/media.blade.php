@extends('admin.layout')

@section('title','Media Manager')
@section('crumb','Admin Console / Media')
@section('page_actions')
<details class="modal">
    <summary class="btn btn-primary">Upload Asset</summary>
    <div class="modal-panel">
        <div class="modal-h"><h3>Upload Media</h3><span class="badge b-info">50 MB max</span></div>
        <form class="modal-b form-stack" method="post" action="/admin/media/assets" enctype="multipart/form-data">@csrf
            <div class="dropzone"><strong>Choose file</strong><br><input name="file" type="file" required></div>
            <div class="form-grid">
                <div class="field"><label>Folder</label><input class="control" name="folder" placeholder="datasheets, cad, firmware"></div>
                <div class="field"><label>Title</label><input class="control" name="title"></div>
            </div>
            <div class="field"><label>Alt text</label><textarea class="control" name="alt_text"></textarea></div>
            <div class="field"><label>SEO title</label><input class="control" name="seo_title"></div>
            <button class="btn btn-primary" type="submit">Upload</button>
        </form>
    </div>
</details>
@endsection

@section('content')
@php $viewMode = request('view', 'grid') === 'list' ? 'list' : 'grid'; @endphp
<div class="grid kpis">
    <div class="kpi"><div class="t">Assets</div><div class="v tnum">{{ number_format($assets->total()) }}</div><div class="s">managed files</div></div>
    <div class="kpi"><div class="t">Folders</div><div class="v tnum">{{ number_format($folders->count()) }}</div><div class="s">logical groups</div></div>
    <div class="kpi"><div class="t">Datasheets</div><div class="v tnum">{{ number_format($assets->where('folder','datasheets')->count()) }}</div><div class="s">current page</div></div>
    <div class="kpi"><div class="t">Mode</div><div class="v">{{ ucfirst($viewMode) }}</div><div class="s">active library view</div></div>
</div>

<section class="card">
    <div class="card-h"><div><h2>Media Library</h2><div class="sub">Upload, preview, copy URL and delete assets</div></div><nav class="actions" aria-label="Media view"><a class="tab {{ $viewMode === 'grid' ? 'active' : '' }}" href="{{ request()->fullUrlWithQuery(['view' => 'grid']) }}">Grid</a><a class="tab {{ $viewMode === 'list' ? 'active' : '' }}" href="{{ request()->fullUrlWithQuery(['view' => 'list']) }}">List</a></nav></div>
    <form class="filters" method="get">
        <select class="control" name="folder"><option value="">All folders</option>@foreach($folders as $folder)<option value="{{ $folder->folder }}" @selected($filters['folder']===$folder->folder)>{{ $folder->folder }}</option>@endforeach</select>
        <select class="control" name="type"><option value="">All types</option><option value="image" @selected($filters['type']==='image')>Images</option><option value="pdf" @selected($filters['type']==='pdf')>PDF/Datasheets</option><option value="zip" @selected($filters['type']==='zip')>CAD/Firmware</option></select>
        <input class="control" name="q" value="{{ $filters['q'] }}" placeholder="Search title, filename, alt text">
        <button class="btn btn-ghost" type="submit">Filter</button>
    </form>
    <nav class="tabs" aria-label="Media filters"><a class="tab {{ !$filters['folder'] && !$filters['type'] ? 'active' : '' }}" href="/admin/media?view={{ $viewMode }}">All</a><a class="tab {{ $filters['folder'] === 'datasheets' ? 'active' : '' }}" href="/admin/media?folder=datasheets&amp;view={{ $viewMode }}">Datasheets</a><a class="tab {{ $filters['folder'] === 'cad' ? 'active' : '' }}" href="/admin/media?folder=cad&amp;view={{ $viewMode }}">CAD</a><a class="tab {{ $filters['folder'] === 'firmware' ? 'active' : '' }}" href="/admin/media?folder=firmware&amp;view={{ $viewMode }}">Firmware</a><a class="tab" href="/admin/products">Attach to products</a></nav>
    <div class="grid" style="grid-template-columns:{{ $viewMode === 'list' ? '1fr' : 'repeat(auto-fill,minmax(220px,1fr))' }};padding:16px">
        @forelse($assets as $asset)
            @php $url = \Illuminate\Support\Facades\Storage::disk($asset->disk ?: 'public')->url($asset->path); @endphp
            <article class="card" style="box-shadow:none">
                <div style="height:130px;background:#f1f5f9;display:grid;place-items:center">
                    @if(str_starts_with((string) $asset->mime_type, 'image/'))
                        <img src="{{ $url }}" alt="{{ $asset->alt_text ?: $asset->title }}" style="max-width:100%;max-height:130px;object-fit:contain">
                    @else
                        <span class="badge b-info">{{ $asset->mime_type }}</span>
                    @endif
                </div>
                <div style="padding:12px">
                    <strong>{{ $asset->title ?: $asset->original_name }}</strong>
                    <div class="sub mono">{{ $asset->folder ?: 'general' }} · {{ number_format($asset->size) }} bytes</div>
                    <div class="actions" style="margin-top:10px">
                        <button class="btn btn-ghost" type="button" data-copy="{{ $url }}">Copy URL</button>
                        <details class="modal"><summary class="btn btn-ghost">View</summary><div class="modal-panel"><div class="modal-h"><h3>{{ $asset->original_name }}</h3><span class="badge b-info">metadata</span></div><div class="modal-b"><p class="mono">{{ $url }}</p><p>{{ $asset->alt_text ?: 'No alt text yet.' }}</p><p class="mono">{{ $asset->metadata }}</p></div></div></details>
                        <form method="post" action="/admin/media/assets/{{ $asset->id }}" onsubmit="return confirm('Delete this asset?')">@csrf @method('DELETE')<button class="btn btn-ghost danger" type="submit">Delete</button></form>
                    </div>
                </div>
            </article>
        @empty
            <div class="empty" style="grid-column:1/-1"><h3>No media uploaded</h3><p>Upload product images, datasheets, CAD files, firmware, CSVs or SEO assets from the upload action.</p></div>
        @endforelse
    </div>
</section>

<div style="margin-top:14px">{{ $assets->links() }}</div>
@endsection
