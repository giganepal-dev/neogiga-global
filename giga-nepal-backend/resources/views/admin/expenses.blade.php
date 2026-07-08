@extends('admin.layout')
@section('title','Expenses & Reports')
@section('crumb','Back-office spend')
@section('content')

<div class="grid kpis">
    <div class="kpi"><div class="t">Total expenses</div><div class="v tnum">{{ number_format($stats['total'], 2) }}</div><div class="s">all recorded</div></div>
    <div class="kpi"><div class="t">Entries</div><div class="v tnum">{{ number_format($stats['count']) }}</div><div class="s">line items</div></div>
</div>

<div class="grid dashboard-split">
    <div class="card">
        <div class="card-h"><h2>Record Expense</h2></div>
        <form method="post" action="/admin/expenses" class="form-stack" style="padding:16px">@csrf
            <input class="control" name="category" required maxlength="64" placeholder="Category e.g. logistics">
            <input class="control" name="amount" type="number" step="0.01" min="0" required placeholder="Amount">
            <input class="control" name="currency" maxlength="3" placeholder="Currency (USD)">
            <input class="control" name="expense_date" type="date" required value="{{ date('Y-m-d') }}">
            <input class="control" name="description" maxlength="255" placeholder="Description (optional)">
            <button class="btn btn-primary" type="submit">Record Expense</button>
        </form>
    </div>

    <div class="card">
        <div class="card-h"><h2>By Category</h2><span class="sub">Spend breakdown</span></div>
        <div class="scroll-x"><table class="tbl">
            <thead><tr><th>Category</th><th class="num">Amount</th></tr></thead>
            <tbody>
            @forelse ($byCategory as $b)
                <tr><td>{{ $b->category }}</td><td class="num tnum">{{ number_format($b->amount, 2) }}</td></tr>
            @empty
                <tr><td colspan="2"><div class="empty"><h3>No expenses recorded</h3></div></td></tr>
            @endforelse
            </tbody>
        </table></div>
    </div>
</div>

<div class="card" style="margin-top:16px">
    <div class="card-h"><h2>Recent Expenses</h2><span class="sub">Latest 25</span></div>
    <div class="scroll-x"><table class="tbl">
        <thead><tr><th>Expense #</th><th>Category</th><th class="num">Amount</th><th>Status</th><th>Date</th><th>Description</th></tr></thead>
        <tbody>
        @forelse ($expenses as $e)
            <tr>
                <td class="mono">{{ $e->expense_number }}</td>
                <td>{{ $e->category }}</td>
                <td class="num tnum">{{ number_format($e->amount, 2) }} {{ $e->currency }}</td>
                <td><span class="badge {{ $e->status === 'paid' ? 'b-ok' : 'b-muted' }}">{{ $e->status }}</span></td>
                <td class="sub">{{ $e->expense_date }}</td>
                <td>{{ $e->description ?? '—' }}</td>
            </tr>
        @empty
            <tr><td colspan="6"><div class="empty"><h3>No expenses yet</h3></div></td></tr>
        @endforelse
        </tbody>
    </table></div>
</div>

@endsection
