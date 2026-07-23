@extends('admin.layout')
@section('title', 'Education Dashboard')
@section('crumb', 'Admin / Education / Dashboard')

@section('content')
<div class="page-head">
    <div>
        <h2>Education & STEM Dashboard</h2>
        <p>Overview of projects, courses, and sensor knowledge base.</p>
    </div>
    <div class="page-actions">
        <a href="/admin/education/projects" class="btn btn-ghost">All Projects</a>
    </div>
</div>

<div class="grid kpis">
    <div class="kpi">
        <div class="t"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><rect x="3" y="3" width="18" height="14" rx="2"/><path d="M3 9h18M8 21h8M12 17v4" stroke-linejoin="round"/></svg> Total Projects</div>
        <div class="v">{{ $total_projects }}</div>
        <div class="s">{{ $published_projects }} published</div>
    </div>
    <div class="kpi">
        <div class="t"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/></svg> Total Views</div>
        <div class="v">{{ number_format($total_views) }}</div>
    </div>
    <div class="kpi">
        <div class="t"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path d="M12 2l2 7h7l-5.5 4 2 7L12 16l-5.5 4 2-7L3 9h7l2-7z"/></svg> Avg Rating</div>
        <div class="v">{{ $avg_rating ? number_format($avg_rating, 1) : 'N/A' }}</div>
    </div>
    <div class="kpi">
        <div class="t"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><circle cx="12" cy="12" r="3"/><path d="M12 2v4M12 18v4"/></svg> Sensors</div>
        <div class="v">{{ $total_sensors }}</div>
    </div>
    <div class="kpi">
        <div class="t"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path d="M4 19.5A2.5 2.5 0 016.5 17H20"/><path d="M4 4.5A2.5 2.5 0 016.5 2H20v20H6.5A2.5 2.5 0 014 19.5z"/></svg> Courses</div>
        <div class="v">{{ $total_courses }}</div>
    </div>
    <div class="kpi">
        <div class="t"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path d="M9 3h6v4H9zM5 7h14v14H5z" stroke-linejoin="round"/></svg> Drafts</div>
        <div class="v">{{ $draft_projects }}</div>
        <div class="s">Awaiting review</div>
    </div>
</div>

<div class="grid split" style="margin-top:18px">
    <div class="card">
        <div class="card-h"><h2>Projects by Category</h2></div>
        <div class="scroll-x">
            <table class="tbl">
                <thead><tr><th>Category</th><th class="num">Projects</th></tr></thead>
                <tbody>
                    @forelse($categories as $cat)
                    <tr>
                        <td>{{ ucfirst($cat['category'] ?? 'Uncategorized') }}</td>
                        <td class="num">{{ $cat['count'] }}</td>
                    </tr>
                    @empty
                    <tr><td colspan="2" class="empty">No categories yet</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
    <div class="card">
        <div class="card-h"><h2>Projects by Controller</h2></div>
        <div class="scroll-x">
            <table class="tbl">
                <thead><tr><th>Controller</th><th class="num">Projects</th></tr></thead>
                <tbody>
                    @forelse($controllers as $ctrl)
                    <tr>
                        <td>{{ $ctrl['main_controller'] ?? 'Various' }}</td>
                        <td class="num">{{ $ctrl['count'] }}</td>
                    </tr>
                    @empty
                    <tr><td colspan="2" class="empty">No controller data yet</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>

<div class="card" style="margin-top:18px">
    <div class="card-h"><h2>Recent Projects</h2></div>
    <div class="scroll-x">
        <table class="tbl">
            <thead><tr><th>Title</th><th>Category</th><th>Status</th><th>Views</th><th>Created</th></tr></thead>
            <tbody>
                @forelse($recent_projects as $proj)
                <tr>
                    <td><a href="/admin/education/projects/{{ $proj->id }}" style="color:var(--primary);font-weight:600">{{ $proj->title }}</a></td>
                    <td>{{ ucfirst($proj->category) }}</td>
                    <td><span class="badge {{ $proj->verification_status==='published' ? 'b-ok':'b-muted' }}">{{ $proj->verification_status }}</span></td>
                    <td class="num">{{ number_format($proj->view_count) }}</td>
                    <td>{{ $proj->created_at->diffForHumans() }}</td>
                </tr>
                @empty
                <tr><td colspan="5" class="empty">No projects yet. Create your first education project.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
@endsection
