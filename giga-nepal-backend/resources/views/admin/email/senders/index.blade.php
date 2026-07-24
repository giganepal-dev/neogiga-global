@extends('admin.layout')
@section('title', 'Sender Identities')
@section('crumb', 'Email / Senders')

@section('content')
<div class="page-head">
    <div>
        <h2>Sender Identities</h2>
        <p>Manage email sender profiles for your campaigns.</p>
    </div>
    <div class="page-actions">
        <a href="/email/senders/create" class="btn btn-primary">New Sender</a>
    </div>
</div>

<div class="card">
    <div class="card-h">
        <h2>Senders ({{ $senders->total() }})</h2>
    </div>
    <div class="scroll-x">
        <table class="tbl">
            <thead>
                <tr>
                    <th>Name</th>
                    <th>Email</th>
                    <th>Reply-To</th>
                    <th>Verified</th>
                    <th>Default</th>
                    <th>Created</th>
                    <th></th>
                </tr>
            </thead>
            <tbody>
                @forelse($senders as $s)
                <tr>
                    <td>
                        <a href="/email/senders/{{ $s->id }}" style="font-weight:600;color:var(--fg)">{{ $s->sender_name }}</a>
                    </td>
                    <td class="mono">{{ $s->sender_email }}</td>
                    <td>{{ $s->reply_to ?? '—' }}</td>
                    <td>
                        @if($s->is_verified)
                            <span class="badge b-ok">verified</span>
                        @else
                            <span class="badge b-warn">unverified</span>
                        @endif
                    </td>
                    <td>
                        @if($s->is_default)
                            <span class="badge b-info">default</span>
                        @else
                            —
                        @endif
                    </td>
                    <td style="white-space:nowrap">{{ $s->created_at?->diffForHumans() ?? '—' }}</td>
                    <td style="white-space:nowrap">
                        <a href="/email/senders/{{ $s->id }}" class="btn btn-ghost btn-sm">View</a>
                        <a href="/email/senders/{{ $s->id }}/edit" class="btn btn-ghost btn-sm">Edit</a>
                    </td>
                </tr>
                @empty
                <tr><td colspan="7" class="empty"><p>No sender identities yet.</p></td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
    {{ $senders->links() }}
</div>
@endsection
