@extends('admin.layout')
@section('title', 'POS Daily Report')
@section('crumb', 'POS / Daily Report')

@section('content')
<div class="page-head">
    <div>
        <h2>POS Daily Report</h2>
        <p style="color:var(--muted)">{{ \Carbon\Carbon::parse($today)->format('l, F j, Y') }}</p>
    </div>
    <div class="page-actions">
        <a href="/admin/pos/manage" class="btn btn-ghost">Back to POS</a>
    </div>
</div>

@if(session('success'))
    <div class="note" style="background:#dcfce7;border-color:#86efac;color:#166534">{{ session('success') }}</div>
@endif

<div class="kpis">
    <div class="kpi">
        <div class="t">Total Sales</div>
        <div class="v" style="color:var(--ok)">${{ number_format($sales->total ?? 0, 2) }}</div>
        <div class="s">{{ $sales->count ?? 0 }} transactions</div>
    </div>
    <div class="kpi">
        <div class="t">Total Refunds</div>
        <div class="v" style="color:var(--danger)">${{ number_format($refunds->total ?? 0, 2) }}</div>
        <div class="s">{{ $refunds->count ?? 0 }} refunds</div>
    </div>
    <div class="kpi">
        <div class="t">Net Sales</div>
        <div class="v">${{ number_format(($sales->total ?? 0) - ($refunds->total ?? 0), 2) }}</div>
    </div>
    <div class="kpi">
        <div class="t">Shifts Today</div>
        <div class="v">{{ $shifts->total_shifts ?? 0 }}</div>
        <div class="s">{{ $shifts->open_shifts ?? 0 }} currently open</div>
    </div>
</div>

<div style="display:grid;grid-template-columns:1fr 1fr;gap:16px;margin-top:20px">
    <div class="card">
        <div class="card-h"><h2>Sales Summary</h2></div>
        <div class="card-body">
            <table style="width:100%">
                <tr>
                    <td style="padding:10px 0;border-bottom:1px solid var(--border)">Gross Sales</td>
                    <td style="padding:10px 0;border-bottom:1px solid var(--border);text-align:right;font-weight:600">${{ number_format($sales->total ?? 0, 2) }}</td>
                </tr>
                <tr>
                    <td style="padding:10px 0;border-bottom:1px solid var(--border)">Refunds</td>
                    <td style="padding:10px 0;border-bottom:1px solid var(--border);text-align:right;font-weight:600;color:var(--danger)">-${{ number_format($refunds->total ?? 0, 2) }}</td>
                </tr>
                <tr>
                    <td style="padding:10px 0;font-weight:700">Net Sales</td>
                    <td style="padding:10px 0;text-align:right;font-weight:700;font-size:18px">${{ number_format(($sales->total ?? 0) - ($refunds->total ?? 0), 2) }}</td>
                </tr>
            </table>
        </div>
    </div>

    <div class="card">
        <div class="card-h"><h2>Shift Activity</h2></div>
        <div class="card-body">
            <table style="width:100%">
                <tr>
                    <td style="padding:10px 0;border-bottom:1px solid var(--border)">Total Shifts</td>
                    <td style="padding:10px 0;border-bottom:1px solid var(--border);text-align:right;font-weight:600">{{ $shifts->total_shifts ?? 0 }}</td>
                </tr>
                <tr>
                    <td style="padding:10px 0;border-bottom:1px solid var(--border)">Currently Open</td>
                    <td style="padding:10px 0;border-bottom:1px solid var(--border);text-align:right;font-weight:600;color:var(--ok)">{{ $shifts->open_shifts ?? 0 }}</td>
                </tr>
                <tr>
                    <td style="padding:10px 0;font-weight:700">Closed</td>
                    <td style="padding:10px 0;text-align:right;font-weight:700">{{ ($shifts->total_shifts ?? 0) - ($shifts->open_shifts ?? 0) }}</td>
                </tr>
            </table>
        </div>
    </div>
</div>
@endsection
