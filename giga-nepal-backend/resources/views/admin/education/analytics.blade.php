@extends('admin.layout')
@section('title', 'Education Analytics')
@section('crumb', 'Admin / Education / Analytics')

@section('content')
<div class="page-head">
    <h2>Education Analytics</h2>
</div>

<div class="grid kpis">
    <div class="kpi">
        <div class="t">Total Views</div>
        <div class="v">{{ number_format($total_views) }}</div>
    </div>
    <div class="kpi">
        <div class="t">Enrollments</div>
        <div class="v">{{ number_format($total_enrollments) }}</div>
    </div>
    <div class="kpi">
        <div class="t">Avg Rating</div>
        <div class="v">{{ $avg_rating ? number_format($avg_rating, 1) : 'N/A' }}</div>
    </div>
</div>

<div class="grid split" style="margin-top:18px">
    <div class="card">
        <div class="card-h"><h2>Top Projects by Views</h2></div>
        <div class="scroll-x">
            <table class="tbl">
                <thead><tr><th>Project</th><th>Category</th><th class="num">Views</th><th class="num">Rating</th></tr></thead>
                <tbody>
                    @forelse($top_projects as $proj)
                    <tr>
                        <td><a href="/admin/education/projects/{{ $proj->id }}" style="color:var(--primary);font-weight:600">{{ $proj->title }}</a></td>
                        <td>{{ ucfirst($proj->category) }}</td>
                        <td class="num">{{ number_format($proj->view_count) }}</td>
                        <td class="num">{{ $proj->rating_avg ? number_format($proj->rating_avg, 1) : '-' }}</td>
                    </tr>
                    @empty
                    <tr><td colspan="4" class="empty">No data yet</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <div>
        <div class="card" style="margin-bottom:16px">
            <div class="card-h"><h2>Views by Category</h2></div>
            <div class="scroll-x">
                <table class="tbl">
                    <thead><tr><th>Category</th><th class="num">Projects</th><th class="num">Total Views</th></tr></thead>
                    <tbody>
                        @forelse($top_categories as $cat)
                        <tr>
                            <td>{{ ucfirst($cat['category'] ?? 'Uncategorized') }}</td>
                            <td class="num">{{ $cat['project_count'] }}</td>
                            <td class="num">{{ number_format($cat['total_views']) }}</td>
                        </tr>
                        @empty
                        <tr><td colspan="3" class="empty">No data</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        <div class="card">
            <div class="card-h"><h2>Projects by Controller</h2></div>
            <div class="scroll-x">
                <table class="tbl">
                    <thead><tr><th>Controller</th><th class="num">Count</th></tr></thead>
                    <tbody>
                        @forelse($top_controllers as $ctrl)
                        <tr>
                            <td>{{ $ctrl['main_controller'] ?? 'Various' }}</td>
                            <td class="num">{{ $ctrl['count'] }}</td>
                        </tr>
                        @empty
                        <tr><td colspan="2" class="empty">No data</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<div class="card" style="margin-top:18px">
    <div class="card-h"><h2>Projects by Verification Status</h2></div>
    <div class="scroll-x">
        <table class="tbl">
            <thead><tr><th>Status</th><th class="num">Count</th></tr></thead>
            <tbody>
                @forelse($verification_stats as $stat)
                <tr>
                    <td><span class="badge {{ match($stat['verification_status']) {
                        'published' => 'b-ok',
                        'draft','ai_generated' => 'b-muted',
                        'review_required','needs_update' => 'b-warn',
                        'archived' => 'b-danger',
                        default => 'b-muted'
                    } }}">{{ str_replace('_', ' ', $stat['verification_status']) }}</span></td>
                    <td class="num">{{ $stat['count'] }}</td>
                </tr>
                @empty
                <tr><td colspan="2" class="empty">No data</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
@endsection
