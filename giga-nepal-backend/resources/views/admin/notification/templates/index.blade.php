@extends('admin.layout')
@section('title', 'Notification Templates')
@section('crumb', 'Notifications / Templates')

@section('content')
<div class="page-head">
    <div>
        <h2>Notification Templates</h2>
        <p>Manage transactional email templates for order updates, account events, RFQs, and more.</p>
    </div>
</div>

@if(session('status'))
    <div class="note" style="background:#dcfce7;border-color:#86efac;color:#166534">{{ session('status') }}</div>
@endif

<div class="card">
    <div class="card-h"><h2>Templates ({{ count($templates) }})</h2></div>
    <div class="scroll-x">
        <table class="tbl">
            <thead>
                <tr>
                    <th>Template</th>
                    <th>Events</th>
                    <th>Size</th>
                    <th>Last Modified</th>
                    <th></th>
                </tr>
            </thead>
            <tbody>
                @foreach($templates as $key => $t)
                <tr>
                    <td>
                        <a href="/admin/notification/templates/{{ $key }}" style="font-weight:600;color:var(--fg)">{{ $t['title'] }}</a>
                        <br><span style="color:var(--muted);font-size:.78rem">{{ $key }}.blade.php</span>
                    </td>
                    <td>
                        <div style="display:flex;gap:4px;flex-wrap:wrap">
                            @php
                                $eventsForTemplate = match($key) {
                                    'welcome' => ['registration', 'verification', 'activation', 'welcome'],
                                    'order-confirmation' => ['order_placed', 'order_confirmed'],
                                    'order-status' => ['shipped', 'delivered', 'cancelled', 'payment'],
                                    'password-reset' => ['password_reset'],
                                    'rfq-received' => ['rfq_received'],
                                    'quotation-ready' => ['quotation_ready', 'quotation_expiring'],
                                    'support-updated' => ['support_updated'],
                                    'invoice-generated' => ['invoice_generated'],
                                    default => [],
                                };
                            @endphp
                            @foreach($eventsForTemplate as $ev)
                                <span class="badge b-muted" style="font-size:.72rem">{{ $ev }}</span>
                            @endforeach
                        </div>
                    </td>
                    <td class="num" style="font-size:.82rem">{{ number_format($t['size'] / 1024, 1) }}KB</td>
                    <td style="font-size:.82rem">{{ date('M d, Y H:i', $t['modified']) }}</td>
                    <td style="white-space:nowrap">
                        <a href="/admin/notification/templates/{{ $key }}" class="btn btn-ghost btn-sm">View</a>
                        <a href="/admin/notification/templates/{{ $key }}/edit" class="btn btn-ghost btn-sm">Edit</a>
                    </td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>
</div>

<div class="card" style="margin-top:16px">
    <div class="card-h"><h2>Supported Events</h2></div>
    <div class="card-body">
        <div style="display:flex;gap:6px;flex-wrap:wrap">
            @foreach($events as $event)
                <span class="badge b-muted">{{ $event }}</span>
            @endforeach
        </div>
        <p style="color:var(--muted);font-size:.82rem;margin-top:12px">
            These events are defined in <code>TransactionalCommunicationService::EVENTS</code>.
            Each event maps to a Blade template via <code>templateForEvent()</code>.
        </p>
    </div>
</div>
@endsection
