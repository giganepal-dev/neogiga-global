@extends('admin.layout')
@section('title', 'SMD Identification — NeoGiga Admin')

@section('content')
<div class="container-fluid px-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1 class="h3 mb-0">SMD Marking Code Identification</h1>
        <a href="/admin/smd/queue" class="btn btn-primary">Verification Queue</a>
    </div>

    @if(session('success'))
        <div class="alert alert-success">{{ session('success') }}</div>
    @endif

    <div class="row g-3 mb-4">
        <div class="col-md-3"><div class="card border-0 shadow-sm"><div class="card-body"><div class="text-muted small">Marking Codes</div><div class="h4">{{ number_format($stats['markings']) }}</div></div></div></div>
        <div class="col-md-3"><div class="card border-0 shadow-sm"><div class="card-body"><div class="text-muted small">Total Matches</div><div class="h4">{{ number_format($stats['matches']) }}</div></div></div></div>
        <div class="col-md-3"><div class="card border-0 shadow-sm"><div class="card-body"><div class="text-muted small">Linked to Catalog</div><div class="h4">{{ number_format($stats['linked']) }}</div></div></div></div>
        <div class="col-md-3"><div class="card border-0 shadow-sm"><div class="card-body"><div class="text-muted small">Awaiting Review</div><div class="h4 text-warning">{{ number_format($stats['unverified']) }}</div></div></div></div>
    </div>

    <div class="card border-0 shadow-sm">
        <div class="card-header bg-white d-flex justify-content-between align-items-center">
            <h5 class="mb-0">Recent Matches</h5>
            <span class="text-muted small">{{ number_format($stats['unique_mpns']) }} unique MPNs · {{ $stats['packages'] }} packages</span>
        </div>
        <div class="table-responsive">
            <table class="table table-hover mb-0">
                <thead class="table-light">
                    <tr>
                        <th>Marking</th>
                        <th>Candidate MPN</th>
                        <th>Manufacturer</th>
                        <th>Package</th>
                        <th>Function</th>
                        <th>Confidence</th>
                        <th>Linked Product</th>
                        <th>Status</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($recentMatches as $m)
                    <tr>
                        <td><code>{{ $m->display_marking }}</code></td>
                        <td><strong>{{ $m->candidate_mpn }}</strong></td>
                        <td>{{ $m->manufacturer_name ?? '—' }}</td>
                        <td>{{ $m->package_text ?? '—' }}</td>
                        <td style="max-width:200px"><small>{{ Str::limit($m->component_function, 60) }}</small></td>
                        <td>
                            <span class="badge bg-{{ $m->match_confidence >= 75 ? 'success' : ($m->match_confidence >= 50 ? 'warning' : 'secondary') }}">
                                {{ $m->match_confidence }}%
                            </span>
                        </td>
                        <td>
                            @if($m->product_name)
                                <a href="/admin/products/{{ $m->product_id }}">{{ Str::limit($m->product_name, 30) }}</a>
                            @else
                                <span class="text-muted">—</span>
                            @endif
                        </td>
                        <td>
                            @if($m->verification_status === 'verified') <span class="badge bg-success">Verified</span>
                            @elseif($m->verification_status === 'matched') <span class="badge bg-info">Matched</span>
                            @elseif($m->verification_status === 'rejected') <span class="badge bg-danger">Rejected</span>
                            @else <span class="badge bg-warning">Unverified</span>
                            @endif
                        </td>
                    </tr>
                    @empty
                    <tr><td colspan="8" class="text-center text-muted py-4">No SMD matches yet. Run the importer first.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection
