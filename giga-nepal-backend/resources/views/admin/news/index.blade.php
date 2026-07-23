@extends('admin.layout')
@section('title', 'News & Blog')
@section('crumb', 'Admin / Marketing / News & Blog')

@section('content')
<div class="page-head">
    <div>
        <h2>News & Blog</h2>
        <p>{{ $stats['total'] }} articles total</p>
    </div>
    <div class="page-actions">
        <a href="/admin/news/categories" class="btn btn-ghost">Categories</a>
        <a href="/admin/news/modal" class="btn btn-ghost">Announcement Modal</a>
    </div>
</div>

<div class="grid kpis">
    <div class="kpi"><div class="t">Total Articles</div><div class="v">{{ $stats['total'] }}</div></div>
    <div class="kpi"><div class="t">Published</div><div class="v" style="color:var(--ok)">{{ $stats['published'] }}</div></div>
    <div class="kpi"><div class="t">Drafts</div><div class="v" style="color:var(--muted)">{{ $stats['draft'] }}</div></div>
    <div class="kpi"><div class="t">Scheduled</div><div class="v" style="color:var(--info)">{{ $stats['scheduled'] }}</div></div>
    <div class="kpi"><div class="t">Total Views</div><div class="v">{{ number_format($stats['total_views']) }}</div></div>
</div>

<form class="filters" method="GET" action="/admin/news">
    <div class="field"><label>Search</label><input class="control" name="q" value="{{ request('q') }}" placeholder="Title, slug..."></div>
    <div class="field"><label>Type</label>
        <select class="control" name="type"><option value="">All Types</option>
            @foreach(['blog','news_release','tutorial','case_study','event','press'] as $t)<option value="{{ $t }}" {{ request('type')===$t?'selected' : '' }}>{{ ucfirst(str_replace('_',' ',$t)) }}</option>@endforeach
        </select>
    </div>
    <div class="field"><label>Status</label>
        <select class="control" name="status"><option value="">All Statuses</option>
            @foreach(['draft','scheduled','published','archived'] as $s)<option value="{{ $s }}" {{ request('status')===$s?'selected' : '' }}>{{ ucfirst($s) }}</option>@endforeach
        </select>
    </div>
    <div class="field" style="align-self:end"><button class="btn btn-primary" type="submit">Filter</button></div>
</form>

<div class="card" style="margin-top:16px">
    <div style="overflow-x:auto">
        <table class="tbl">
            <thead><tr><th>Title</th><th>Type</th><th>Category</th><th>Status</th><th class="num">Views</th><th>Published</th><th></th></tr></thead>
            <tbody>
                @forelse($posts as $post)
                <tr>
                    <td style="max-width:280px">
                        <a href="/admin/news/{{ $post->id }}" style="font-weight:600;color:var(--primary)">{{ $post->title }}</a>
                        @if($post->is_featured)<span class="badge b-info" style="margin-left:6px">Featured</span>@endif
                        @if($post->is_pinned)<span class="badge b-warn" style="margin-left:6px">Pinned</span>@endif
                    </td>
                    <td><span class="badge b-muted">{{ str_replace('_',' ',$post->post_type) }}</span></td>
                    <td>{{ $post->category?->name ?? '-' }}</td>
                    <td><span class="badge {{ match($post->status) {
                        'published' => 'b-ok', 'scheduled' => 'b-info', 'archived' => 'b-danger', default => 'b-muted'
                    } }}">{{ ucfirst($post->status) }}</span></td>
                    <td class="num">{{ number_format($post->view_count) }}</td>
                    <td class="sub">{{ $post->published_at?->diffForHumans() ?? '-' }}</td>
                    <td>
                        <div class="actions">
                            <a href="/admin/news/{{ $post->id }}" class="btn btn-ghost" style="height:30px;font-size:.78rem">Edit</a>
                            @if($post->status !== 'published')
                            <form method="POST" action="/admin/news/{{ $post->id }}/publish" style="display:inline">@csrf<button class="btn btn-ghost" style="height:30px;font-size:.78rem;color:var(--ok)">Publish</button></form>
                            @else
                            <form method="POST" action="/admin/news/{{ $post->id }}/unpublish" style="display:inline">@csrf<button class="btn btn-ghost" style="height:30px;font-size:.78rem;color:var(--warn)">Unpublish</button></form>
                            @endif
                        </div>
                    </td>
                </tr>
                @empty
                <tr><td colspan="7" class="empty"><h3>No articles yet</h3><p>Create your first news release or blog post.</p></td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
<div style="margin-top:16px">{{ $posts->links() }}</div>
@endsection
