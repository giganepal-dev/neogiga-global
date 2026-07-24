@extends('admin.layout')
@section('title', 'Articles & News')
@section('crumb', 'AI & Robotics / Articles')
@section('content')
<div class="page-head"><div><h2>Articles & News</h2></div><div class="page-actions"><a href="/admin/ai-robotics/articles/create" class="btn btn-primary">Add Article</a></div></div>
@if(session('status'))<div class="note" style="background:#dcfce7;border-color:#86efac;color:#166534">{{ session('status') }}</div>@endif
<div class="card">
    <div class="card-h"><h2>Articles ({{ $articles->total() }})</h2>
        <form method="GET" style="display:flex;gap:8px"><select class="control" name="type" style="width:140px"><option value="">All Types</option>@foreach(['news','product_launch','research','case_study','press_release'] as $t)<option value="{{ $t }}" {{ request('type')===$t ? 'selected' : '' }}>{{ str_replace('_',' ',ucfirst($t)) }}</option>@endforeach</select><button class="btn btn-ghost" type="submit">Filter</button></form>
    </div>
    <div class="scroll-x">
        <table class="tbl"><thead><tr><th>Title</th><th>Type</th><th>Status</th><th>Published</th><th></th></tr></thead>
        <tbody>@forelse($articles as $a)<tr><td style="font-weight:600">{{ $a->title }}</td><td><span class="badge b-muted">{{ str_replace('_',' ',$a->article_type) }}</span></td><td><span class="badge {{ $a->status==='published' ? 'b-ok' : 'b-muted' }}">{{ ucfirst($a->status) }}</span></td><td style="font-size:.82rem">{{ $a->published_at?->format('M d, Y') ?? '—' }}</td><td><a href="/admin/ai-robotics/articles/{{ $a->id }}/edit" class="btn btn-ghost btn-sm">Edit</a></td></tr>@empty<tr><td colspan="5" class="empty">No articles yet.</td></tr>@endforelse</tbody></table>
    </div>
    {{ $articles->links() }}
</div>
@endsection
