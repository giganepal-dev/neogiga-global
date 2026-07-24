@extends('seller.layout')
@section('title', 'Warehouses')
@section('content')

<div class="page-intro">
    <h1>Warehouses</h1>
    <p>Manage your warehouse locations and fulfillment centers.</p>
</div>

<div class="kpi-grid" style="grid-template-columns:repeat(auto-fit,minmax(160px,1fr));margin-bottom:16px">
    <div class="kpi">
        <div class="t">Total Warehouses</div>
        <div class="v">{{ number_format($stats['total']) }}</div>
    </div>
    <div class="kpi">
        <div class="t">Active</div>
        <div class="v" style="color:var(--ok)">{{ number_format($stats['active']) }}</div>
    </div>
</div>

<div class="card">
    <div class="card-h">
        <h2>Warehouse Locations</h2>
        <span class="badge b-muted">{{ number_format($warehouses->total()) }} warehouses</span>
    </div>
    <div class="table-wrap">
        <table class="table">
            <thead>
                <tr>
                    <th>Warehouse</th>
                    <th>Code</th>
                    <th>Location</th>
                    <th>Contact</th>
                    <th>Status</th>
                    <th>Default</th>
                </tr>
            </thead>
            <tbody>
                @forelse($warehouses as $wh)
                <tr>
                    <td>
                        <strong>{{ $wh->name }}</strong>
                        @if($wh->description)<div class="sub" style="font-size:.8rem;max-width:250px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap">{{ $wh->description }}</div>@endif
                    </td>
                    <td class="mono">{{ $wh->code }}</td>
                    <td>
                        <div>{{ $wh->address_line1 }}</div>
                        @if($wh->address_line2)<div class="sub" style="font-size:.8rem">{{ $wh->address_line2 }}</div>@endif
                        <div class="sub" style="font-size:.8rem">
                            @if($wh->postal_code){{ $wh->postal_code }}, @endif{{ $wh->country_name ?? '' }}
                        </div>
                    </td>
                    <td>
                        @if($wh->contact_name)<div>{{ $wh->contact_name }}</div>@endif
                        @if($wh->contact_email)<div class="sub" style="font-size:.8rem">{{ $wh->contact_email }}</div>@endif
                        @if($wh->contact_phone)<div class="sub" style="font-size:.8rem">{{ $wh->contact_phone }}</div>@endif
                    </td>
                    <td>
                        <span class="badge {{ $wh->is_active ? 'b-ok' : 'b-muted' }}">
                            {{ $wh->is_active ? 'Active' : 'Inactive' }}
                        </span>
                    </td>
                    <td>
                        @if($wh->is_default)
                            <span class="badge b-info">Default</span>
                        @else
                            <span class="sub">—</span>
                        @endif
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="6">
                        <div class="empty">
                            <h3>No warehouses configured</h3>
                            <p>Set up warehouse locations to manage inventory and fulfillment.</p>
                        </div>
                    </td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>
    @if($warehouses->hasPages())
    <div style="padding:12px 16px">{{ $warehouses->links() }}</div>
    @endif
</div>

@endsection
