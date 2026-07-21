@extends('admin.layout')
@section('title', 'SMD Marking Codes — NeoGiga Admin')

@section('content')
<div class="container-fluid px-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1 class="h3 mb-0">SMD Marking Codes</h1>
        <a href="/admin/smd" class="btn btn-ghost">← Back to Dashboard</a>
    </div>

    <div class="card border-0 shadow-sm">
        <div class="table-responsive">
            <table class="table table-hover mb-0">
                <thead class="table-light">
                    <tr>
                        <th>Marking</th>
                        <th>Length</th>
                        <th>Matches</th>
                        <th>First Seen</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($markings as $m)
                    <tr>
                        <td><code>{{ $m->display_marking }}</code></td>
                        <td>{{ $m->marking_length }}</td>
                        <td><span class="badge bg-info">{{ $m->match_count }}</span></td>
                        <td><small class="text-muted">{{ $m->created_at }}</small></td>
                    </tr>
                    @empty
                    <tr><td colspan="4" class="text-center text-muted py-4">No marking codes yet. Run the importer first.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
    <div class="mt-3">{{ $markings->links() }}</div>
</div>
@endsection
