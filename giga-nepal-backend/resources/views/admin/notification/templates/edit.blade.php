@extends('admin.layout')
@section('title', 'Edit: ' . ucwords(str_replace('-', ' ', $template)))
@section('crumb', 'Notifications / Templates / ' . ucwords(str_replace('-', ' ', $template)) . ' / Edit')

@section('content')
<div class="page-head">
    <div>
        <h2>Edit {{ ucwords(str_replace('-', ' ', $template)) }} Template</h2>
        <p style="color:var(--muted)">Modify the Blade template source. Changes affect all future emails using this template.</p>
    </div>
    <div class="page-actions">
        <a href="/admin/notification/templates/{{ $template }}" class="btn btn-ghost">Back to Template</a>
    </div>
</div>

@if(session('error'))
    <div class="note" style="background:#fee2e2;border-color:#fca5a5;color:#991b1b">{{ session('error') }}</div>
@endif

<form method="POST" action="/admin/notification/templates/{{ $template }}">
    @csrf
    @method('PUT')

    <div class="card">
        <div class="card-h">
            <h2>Template Source</h2>
            <div style="display:flex;gap:8px;align-items:center">
                <span style="color:var(--muted);font-size:.82rem">{{ $template }}.blade.php</span>
            </div>
        </div>
        <div class="card-body">
            <textarea class="control" name="content" rows="30" required style="font-family:monospace;font-size:12px;line-height:1.6;tab-size:2">{{ $content }}</textarea>
        </div>
    </div>

    <div style="margin-top:16px;display:flex;gap:8px">
        <button type="submit" class="btn btn-primary">Save Template</button>
        <a href="/admin/notification/templates/{{ $template }}" class="btn btn-ghost">Cancel</a>
    </div>
</form>

<div class="card" style="margin-top:16px">
    <div class="card-h"><h2>Available Variables</h2></div>
    <div class="card-body">
        <p style="color:var(--muted);font-size:.82rem;margin-bottom:8px">Use <code>{{ '{{ $variableName }}' }}</code> syntax. These are passed from <code>TransactionalCommunicationService::templateData()</code>:</p>
        <div style="display:flex;gap:6px;flex-wrap:wrap">
            @foreach(['userName','userEmail','brand','regionName','subject','greeting','orderNumber','orderDate','orderStatus','orderTotal','currency','paymentStatus','statusLabel','statusBadge','statusDate','statusMessage','nextStep','trackingNumber','carrier','customerAction','shippingAddress','products','verificationUrl','activationUrl','passwordResetUrl','loginUrl','securityNote'] as $var)
                <code style="padding:3px 6px;background:var(--bg);border:1px solid var(--border);border-radius:3px;font-size:.78rem">${{ $var }}</code>
            @endforeach
        </div>
    </div>
</div>
@endsection
