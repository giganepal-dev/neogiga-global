@extends('admin.layout')
@section('title', 'Education Projects')
@section('crumb', 'Admin / Education / Projects')

@section('content')
<div class="page-head">
    <div>
        <h2>Education Projects</h2>
        <p>{{ $projects->total() }} projects total</p>
    </div>
</div>

<form class="filters" method="GET" action="/admin/education/projects">
    <div class="field">
        <label>Search</label>
        <input class="control" name="q" value="{{ request('q') }}" placeholder="Title, controller, slug...">
    </div>
    <div class="field">
        <label>Category</label>
        <select class="control" name="category">
            <option value="">All Categories</option>
            @foreach($categories as $cat)
                <option value="{{ $cat['category'] }}" {{ request('category') === $cat['category'] ? 'selected' : '' }}>
                    {{ ucfirst($cat['category'] ?? 'Uncategorized') }} ({{ $cat['count'] }})
                </option>
            @endforeach
        </select>
    </div>
    <div class="field">
        <label>Status</label>
        <select class="control" name="status">
            <option value="">All Statuses</option>
            @foreach(['draft','ai_generated','review_required','technically_reviewed','published','needs_update','archived'] as $s)
                <option value="{{ $s }}" {{ request('status') === $s ? 'selected' : '' }}>{{ ucfirst(str_replace('_',' ',$s)) }}</option>
            @endforeach
        </select>
    </div>
    <div class="field" style="align-self:end">
        <button class="btn btn-primary" type="submit">Filter</button>
    </div>
</form>

<div class="card" style="margin-top:16px">
    <div class="scroll-x">
        <table class="tbl">
            <thead>
                <tr><th>Title</th><th>Category</th><th>Controller</th><th>Level</th><th>Status</th><th class="num">Views</th><th>Author</th><th>Created</th><th></th></tr>
            </thead>
            <tbody>
                @forelse($projects as $proj)
                <tr>
                    <td style="max-width:260px">
                        <a href="/admin/education/projects/{{ $proj->id }}" style="font-weight:600;color:var(--primary)">{{ $proj->title }}</a>
                        <br><span style="font-size:.78rem;color:var(--muted)">{{ Str::limit($proj->summary, 60) }}</span>
                    </td>
                    <td><span class="badge b-info">{{ ucfirst($proj->category) }}</span></td>
                    <td>{{ $proj->main_controller ?? '-' }}</td>
                    <td><span class="badge b-muted">{{ $proj->skill_level }}</span></td>
                    <td><span class="badge {{ match($proj->verification_status) {
                        'published' => 'b-ok',
                        'draft','ai_generated' => 'b-muted',
                        'review_required','needs_update' => 'b-warn',
                        'archived' => 'b-danger',
                        default => 'b-muted'
                    } }}">{{ str_replace('_', ' ', $proj->verification_status) }}</span></td>
                    <td class="num">{{ number_format($proj->view_count) }}</td>
                    <td>{{ $proj->author?->name ?? '-' }}</td>
                    <td style="white-space:nowrap">{{ $proj->created_at->format('M d, Y') }}</td>
                    <td>
                        <div class="actions">
                            <a href="/admin/education/projects/{{ $proj->id }}" class="btn btn-ghost icon-btn" title="View">
                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/></svg>
                            </a>
                            @if($proj->verification_status !== 'published')
                            <form method="POST" action="/admin/education/projects/{{ $proj->id }}/approve" style="display:inline">
                                @csrf <button class="btn btn-ghost icon-btn" title="Publish" type="submit">
                                    <svg viewBox="0 0 24 24" fill="none" stroke="var(--ok)" stroke-width="2"><path d="M20 6L9 17l-5-5" stroke-linecap="round" stroke-linejoin="round"/></svg>
                                </button>
                            </form>
                            @endif
                        </div>
                    </td>
                </tr>
                @empty
                <tr><td colspan="9" class="empty"><h3>No projects found</h3><p>Adjust your filters or create new projects via the API.</p></td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>

<div style="margin-top:16px">{{ $projects->links() }}</div>
@endsection
