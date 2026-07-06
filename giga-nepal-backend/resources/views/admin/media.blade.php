@extends('admin.layout')

@section('title','Media')
@section('crumb','Admin Console / Media')

@section('content')
<div class="grid kpis">
    <div class="kpi"><div class="t">Assets</div><div class="v tnum">{{ number_format($assets->total()) }}</div><div class="s">Managed files</div></div>
    <div class="kpi"><div class="t">Folders</div><div class="v tnum">{{ number_format($folders->count()) }}</div><div class="s">Logical groups</div></div>
</div>

<div class="note">Uploads are available through the protected media API with MIME and size validation. Keep public pages selective: use descriptive media titles and alt text rather than exposing internal file metadata.</div>

<section class="card">
    <div class="card-h"><h2>Media Assets</h2><span class="sub">API: /api/v1/admin/console/media</span></div>
    <div class="scroll-x">
        <table class="tbl">
            <thead><tr><th>ID</th><th>Folder</th><th>Title</th><th>Original name</th><th>Type</th><th class="num">Size</th><th>Created</th></tr></thead>
            <tbody>
            @forelse($assets as $asset)
                <tr>
                    <td class="tnum">{{ $asset->id }}</td>
                    <td>{{ $asset->folder ?: 'general' }}</td>
                    <td>{{ $asset->title ?: 'Untitled' }}</td>
                    <td class="mono">{{ $asset->original_name }}</td>
                    <td>{{ $asset->mime_type }}</td>
                    <td class="num">{{ number_format($asset->size) }}</td>
                    <td>{{ $asset->created_at }}</td>
                </tr>
            @empty
                <tr><td colspan="7"><div class="empty"><h3>No media uploaded</h3><p>Use the protected media API to add validated admin assets.</p></div></td></tr>
            @endforelse
            </tbody>
        </table>
    </div>
</section>

<div style="margin-top:14px">{{ $assets->links() }}</div>
@endsection

