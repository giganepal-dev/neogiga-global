@extends('admin.layout')
@section('title','Partner Approvals')
@section('crumb','Reseller & distributor onboarding')
@section('content')

<div class="grid kpis">
    <div class="kpi"><div class="t">Unified role apps pending</div><div class="v tnum">{{ number_format($stats['account_applications_pending']) }}</div></div>
    <div class="kpi"><div class="t">Reseller apps pending</div><div class="v tnum">{{ number_format($stats['reseller_apps_pending']) }}</div></div>
    <div class="kpi"><div class="t">Reseller territory pending</div><div class="v tnum">{{ number_format($stats['reseller_territory_pending']) }}</div></div>
    <div class="kpi"><div class="t">Distributor territory pending</div><div class="v tnum">{{ number_format($stats['distributor_territory_pending']) }}</div></div>
</div>

<div class="card" style="margin-bottom:16px">
    <div class="card-h"><h2>Unified account role applications</h2><div class="sub">Document review, compliance decision and atomic role provisioning</div></div>
    <div class="scroll-x"><table class="tbl">
        <thead><tr><th>Application</th><th>Applicant</th><th>Role / company</th><th>Documents</th><th>Status</th><th>Decision</th></tr></thead>
        <tbody>
        @forelse($accountApplications as $a)
            <tr>
                <td class="mono">{{ $a->application_number }}</td>
                <td><strong>{{ $a->applicant_name }}</strong><div class="sub">{{ $a->applicant_email }}</div></td>
                <td>{{ ucwords(str_replace('_',' ',$a->role_key)) }}<div class="sub">{{ $a->company_name }} · {{ $a->territory ?: 'No territory specified' }}</div></td>
                <td>@forelse(($accountDocuments[$a->id] ?? collect()) as $document)<a href="/admin/partner-approvals/account-documents/{{ $document->id }}">{{ $document->original_name }}</a><br>@empty<span class="sub">None</span>@endforelse</td>
                <td><span class="badge {{ in_array($a->status,['submitted','under_review','needs_information']) ? 'b-warn' : 'b-info' }}">{{ str_replace('_',' ',$a->status) }}</span>@if($a->review_notes)<div class="sub">{{ $a->review_notes }}</div>@endif</td>
                <td>
                    @if(in_array($a->status,['submitted','under_review','needs_information']))
                    <form method="post" action="/admin/partner-approvals/account-applications/{{ $a->id }}/approve" style="display:inline">@csrf<button class="btn" type="submit">Approve</button></form>
                    <form method="post" action="/admin/partner-approvals/account-applications/{{ $a->id }}/review" style="display:grid;gap:5px;margin-top:7px;min-width:220px">@csrf
                        <select name="status" class="control"><option value="under_review">Under review</option><option value="needs_information">Needs information</option><option value="rejected">Reject</option></select>
                        <input name="notes" class="control" placeholder="Required review notes" required>
                        <button class="btn btn-ghost" type="submit">Update review</button>
                    </form>
                    @else — @endif
                </td>
            </tr>
        @empty
            <tr><td colspan="6"><div class="empty"><h3>No unified role applications</h3></div></td></tr>
        @endforelse
        </tbody>
    </table></div>
</div>

<div class="card" style="margin-bottom:16px">
    <div class="card-h"><h2>Reseller applications</h2><div class="sub">Approve to provision reseller account, territory, and role</div></div>
    <div class="scroll-x"><table class="tbl">
        <thead><tr><th>Company</th><th>Contact</th><th>Marketplace</th><th>Status</th><th>Actions</th></tr></thead>
        <tbody>
        @forelse($resellerApplications as $a)
            <tr>
                <td><strong>{{ $a->company_name }}</strong></td>
                <td>{{ $a->contact_person }}<div class="sub">{{ $a->email }}</div></td>
                <td class="mono">#{{ $a->marketplace_id ?? '—' }}</td>
                <td><span class="badge {{ $a->status === 'pending' ? 'b-warn' : 'b-info' }}">{{ $a->status }}</span></td>
                <td>
                    @if($a->status === 'pending')
                    <form method="post" action="/admin/partner-approvals/reseller-applications/{{ $a->id }}/approve" style="display:inline">@csrf<button class="btn" type="submit">Approve</button></form>
                    <form method="post" action="/admin/partner-approvals/reseller-applications/{{ $a->id }}/reject" style="display:inline;margin-left:6px">@csrf<input type="hidden" name="reason" value="Rejected by admin"><button class="btn btn-ghost" type="submit">Reject</button></form>
                    @else — @endif
                </td>
            </tr>
        @empty
            <tr><td colspan="5"><div class="empty"><h3>No reseller applications</h3></div></td></tr>
        @endforelse
        </tbody>
    </table></div>
</div>

<div class="card" style="margin-bottom:16px">
    <div class="card-h"><h2>Reseller territory expansion</h2><div class="sub">Review uploaded compliance documents</div></div>
    <div class="scroll-x"><table class="tbl">
        <thead><tr><th>Reseller</th><th>Marketplace</th><th>Status</th><th>Documents</th><th>Actions</th></tr></thead>
        <tbody>
        @forelse($resellerTerritoryRequests as $r)
            <tr>
                <td>{{ $r->partner_name ?? ('#'.$r->reseller_id) }}</td>
                <td class="mono">#{{ $r->marketplace_id ?? '—' }}</td>
                <td><span class="badge b-warn">{{ $r->status }}</span></td>
                <td class="sub">@if($r->document_company_reg)<a href="{{ asset('storage/'.$r->document_company_reg) }}" target="_blank">Company reg</a>@endif</td>
                <td>
                    @if($r->status === 'pending')
                    <form method="post" action="/admin/partner-approvals/reseller-territories/{{ $r->id }}/approve" style="display:inline">@csrf<button class="btn" type="submit">Approve</button></form>
                    <form method="post" action="/admin/partner-approvals/reseller-territories/{{ $r->id }}/reject" style="display:inline">@csrf<button class="btn btn-ghost" type="submit">Reject</button></form>
                    @else — @endif
                </td>
            </tr>
        @empty
            <tr><td colspan="5"><div class="empty"><h3>No territory requests</h3></div></td></tr>
        @endforelse
        </tbody>
    </table></div>
</div>

<div class="card">
    <div class="card-h"><h2>Distributor territory expansion</h2></div>
    <div class="scroll-x"><table class="tbl">
        <thead><tr><th>Distributor</th><th>Territory</th><th>Status</th><th>Documents</th><th>Actions</th></tr></thead>
        <tbody>
        @forelse($distributorTerritoryRequests as $r)
            <tr>
                <td>{{ $r->partner_name ?? ('#'.$r->distributor_id) }}</td>
                <td>{{ $r->territory_name }}</td>
                <td><span class="badge b-warn">{{ $r->status }}</span></td>
                <td class="sub">@if($r->document_company_reg)<a href="{{ asset('storage/'.$r->document_company_reg) }}" target="_blank">Docs</a>@endif</td>
                <td>
                    @if($r->status === 'pending')
                    <form method="post" action="/admin/partner-approvals/distributor-territories/{{ $r->id }}/approve" style="display:inline">@csrf<button class="btn" type="submit">Approve</button></form>
                    <form method="post" action="/admin/partner-approvals/distributor-territories/{{ $r->id }}/reject" style="display:inline">@csrf<input type="hidden" name="reason" value="Rejected by admin"><button class="btn btn-ghost" type="submit">Reject</button></form>
                    @else — @endif
                </td>
            </tr>
        @empty
            <tr><td colspan="5"><div class="empty"><h3>No distributor territory requests</h3></div></td></tr>
        @endforelse
        </tbody>
    </table></div>
</div>

@endsection
