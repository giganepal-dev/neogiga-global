@extends('admin.layout')
@section('title', 'SMD Verification Queue — NeoGiga Admin')

@section('content')
<div class="container-fluid px-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1 class="h3 mb-0">SMD Verification Queue</h1>
        <a href="/admin/smd" class="btn btn-ghost">← Back to Dashboard</a>
    </div>

    @if(session('success'))
        <div class="alert alert-success">{{ session('success') }}</div>
    @endif

    <div class="card border-0 shadow-sm">
        <div class="table-responsive">
            <table class="table table-hover mb-0">
                <thead class="table-light">
                    <tr><th>Marking</th><th>MPN</th><th>Manufacturer</th><th>Function</th><th>Conf</th><th>Linked</th><th>Actions</th></tr>
                </thead>
                <tbody>
                    @forelse($queue as $m)
                    <tr>
                        <td><code>{{ $m->display_marking }}</code></td>
                        <td><strong>{{ $m->candidate_mpn }}</strong></td>
                        <td>{{ $m->manufacturer_name ?? '—' }}</td>
                        <td style="max-width:200px"><small>{{ Str::limit($m->component_function, 50) }}</small></td>
                        <td><span class="badge bg-{{ $m->match_confidence >= 75 ? 'success' : ($m->match_confidence >= 50 ? 'warning' : 'secondary') }}">{{ $m->match_confidence }}%</span></td>
                        <td>{{ $m->product_name ? Str::limit($m->product_name, 25) : '—' }}</td>
                        <td>
                            <div class="btn-group btn-group-sm">
                                <form action="/admin/smd/verify/{{ $m->id }}" method="POST">@csrf<button class="btn btn-success btn-sm">✓ Verify</button></form>
                                <form action="/admin/smd/reject/{{ $m->id }}" method="POST" class="ms-1">@csrf<button class="btn btn-danger btn-sm">✕ Reject</button></form>
                            </div>
                        </td>
                    </tr>
                    @empty
                    <tr><td colspan="7" class="text-center text-muted py-4">No unverified matches. All clear!</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
    <div class="mt-3">{{ $queue->links() }}</div>
</div>
@endsection
