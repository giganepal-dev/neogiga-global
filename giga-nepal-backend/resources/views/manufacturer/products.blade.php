@extends('manufacturer.layout')
@section('title','Products')
@section('content')
<h1 style="margin:0 0 24px">Products</h1>
@if($products->isEmpty())
    <div class="card" style="text-align:center;padding:40px"><p style="color:var(--muted)">No products assigned yet.</p></div>
@else
    <div class="table-wrap">
        <table class="table">
            <thead><tr><th>ID</th><th>Name</th><th>SKU</th><th>Status</th><th>Created</th></tr></thead>
            <tbody>
                @foreach($products as $p)
                <tr>
                    <td class="mono" style="font-size:.78rem;color:var(--faint)">#{{ $p->id }}</td>
                    <td><strong>{{ $p->name }}</strong></td>
                    <td class="mono" style="font-size:.78rem">{{ $p->sku }}</td>
                    <td><span class="badge {{ ($p->status ?? '') === 'active' ? 'b-ok' : 'b-muted' }}">{{ $p->status ?? 'draft' }}</span></td>
                    <td style="font-size:.78rem;color:var(--faint)">{{ $p->created_at ?? '—' }}</td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>
    {{ $products->links() }}
@endif
@endsection
