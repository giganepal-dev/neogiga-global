@extends('admin.layout')
@section('title','Vendors')
@section('crumb','Seller network')
@section('content')

<div class="card">
    <div class="card-h"><div><h2>Vendors</h2><div class="sub">{{ number_format($vendors->total()) }} registered</div></div></div>
    <div class="scroll-x">
        <table class="tbl">
            <thead><tr><th>Vendor</th><th>Slug</th><th>Status</th><th>Joined</th></tr></thead>
            <tbody>
            @forelse ($vendors as $v)
                <tr>
                    <td><strong>{{ $v->name ?? $v->business_name ?? ('Vendor #'.$v->id) }}</strong></td>
                    <td class="mono">{{ $v->slug ?? '—' }}</td>
                    <td>
                        @php $s = $v->status ?? 'pending'; @endphp
                        <span class="badge {{ $s==='approved'||$s==='active'?'b-ok':($s==='pending'?'b-warn':'b-muted') }}">{{ ucfirst($s) }}</span>
                    </td>
                    <td>{{ optional($v->created_at)->format('Y-m-d') ?? '—' }}</td>
                </tr>
            @empty
                <tr><td colspan="4">
                    <div class="empty">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><path d="M3 21v-2a4 4 0 014-4h4a4 4 0 014 4v2" stroke-linecap="round"/><circle cx="9" cy="7" r="4"/></svg>
                        <h3>No vendors yet</h3>
                        <p>Vendors register via <span class="mono">POST /api/v1/vendors/register</span> and appear here for marketplace approval.</p>
                    </div>
                </td></tr>
            @endforelse
            </tbody>
        </table>
    </div>
    @if ($vendors->hasPages())
        <div style="padding:12px 16px;border-top:1px solid var(--line)">{{ $vendors->links() }}</div>
    @endif
</div>

@endsection
