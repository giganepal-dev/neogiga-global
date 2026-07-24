@extends('admin.layout')
@section('title', 'Campaign Contacts')
@section('crumb', 'Marketing / Campaign Contacts')

@section('content')
<div class="page-head">
    <div>
        <h2>Campaign Contacts</h2>
        <p>Manage campaign contacts separate from customer accounts.</p>
    </div>
    <div class="page-actions">
        <a href="/admin/marketing/campaign-contacts/preview" class="btn btn-primary">Import Contacts</a>
    </div>
</div>

@if(session('status'))
    <div class="note" style="background:#dcfce7;border-color:#86efac;color:#166534">{{ session('status') }}</div>
@endif
@if(session('error'))
    <div class="note" style="background:#fee2e2;border-color:#fca5a5;color:#991b1b">{{ session('error') }}</div>
@endif

<div class="card">
    <div class="card-h"><h2>Import History</h2></div>
    <div class="scroll-x">
        <table class="tbl">
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Name</th>
                    <th>Source</th>
                    <th>Status</th>
                    <th>Created</th>
                    <th>Updated</th>
                    <th>Skipped</th>
                    <th>Errors</th>
                    <th>Date</th>
                </tr>
            </thead>
            <tbody>
                @forelse($imports as $import)
                <tr>
                    <td><a href="/admin/marketing/campaign-contacts/{{ $import->id }}">{{ $import->id }}</a></td>
                    <td>{{ $import->name ?? '-' }}</td>
                    <td><span class="badge b-info">{{ $import->source }}</span></td>
                    <td><span class="badge {{ $import->status === 'completed' ? 'b-ok' : ($import->status === 'failed' ? 'b-danger' : 'b-muted') }}">{{ $import->status }}</span></td>
                    <td class="num">{{ $import->created_rows }}</td>
                    <td class="num">{{ $import->updated_rows }}</td>
                    <td class="num">{{ $import->skipped_rows }}</td>
                    <td class="num">{{ $import->error_rows }}</td>
                    <td>{{ $import->created_at }}</td>
                </tr>
                @empty
                <tr>
                    <td colspan="9" class="empty">
                        <p>No imports yet. Click "Import Contacts" to get started.</p>
                    </td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>
    {{ $imports->links() }}
</div>

<div class="card" style="margin-top:16px">
    <div class="card-h"><h2>Campaign Contact vs Customer Import</h2></div>
    <div class="card-body">
        <p style="color:var(--muted);font-size:.88rem;margin-bottom:12px">
            <strong>Campaign Contacts</strong> are email subscribers for marketing campaigns. They do not have login accounts unless explicitly converted.
        </p>
        <p style="color:var(--muted);font-size:.88rem;margin-bottom:12px">
            <strong>Customer Accounts</strong> are registered users with login credentials, orders, and full profiles.
        </p>
        <p style="color:var(--muted);font-size:.88rem">
            A campaign contact becomes a customer only after: registration, invitation acceptance, checkout, or admin conversion.
        </p>
    </div>
</div>
@endsection
