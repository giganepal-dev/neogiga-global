@extends('seller.layout')
@section('title', 'Notifications')
@section('content')

<div class="page-intro">
    <div style="display:flex;justify-content:space-between;align-items:center;gap:16px;flex-wrap:wrap">
        <div>
            <h1>Notifications</h1>
            <p>Stay updated on orders, products, and account activity.</p>
        </div>
        @if($unreadCount > 0)
        <form method="post" action="/seller/notifications/mark-all-read" style="margin:0">
            @csrf
            <button class="btn btn-ghost" type="submit">Mark all as read ({{ $unreadCount }} unread)</button>
        </form>
        @endif
    </div>
</div>

<div class="card">
    <div class="card-h">
        <h2>Recent Notifications</h2>
        <span class="badge b-muted">{{ number_format($notifications->total()) }} notifications</span>
    </div>
    <div style="padding:0">
        @forelse($notifications as $n)
        <div style="display:flex;justify-content:space-between;align-items:flex-start;gap:12px;padding:14px 20px;border-bottom:1px solid var(--line);{{ $n->is_read ? '' : 'background:rgba(15,98,230,.04)' }}">
            <div style="display:flex;gap:12px;min-width:0;flex:1">
                <span style="width:8px;height:8px;border-radius:50%;flex-shrink:0;margin-top:7px;background:{{ $n->is_read ? 'transparent' : 'var(--info)' }}"></span>
                <div style="min-width:0">
                    <div style="font-weight:{{ $n->is_read ? '500' : '700' }};font-size:.95rem">{{ $n->title }}</div>
                    <div class="sub" style="font-size:.85rem;margin-top:2px">{{ $n->message }}</div>
                    <div style="display:flex;gap:12px;margin-top:6px;align-items:center">
                        <span class="sub" style="font-size:.78rem">{{ \Illuminate\Support\Carbon::parse($n->created_at)->diffForHumans() }}</span>
                        @if($n->event)
                            <span class="badge b-muted" style="font-size:.7rem">{{ str_replace('_', ' ', $n->event) }}</span>
                        @endif
                    </div>
                </div>
            </div>
            <div style="display:flex;gap:6px;flex-shrink:0">
                @if(! $n->is_read)
                <form method="post" action="/seller/notifications/{{ $n->id }}/read" style="margin:0">
                    @csrf
                    <button class="btn btn-ghost" type="submit" style="padding:4px 8px;font-size:.78rem">Mark read</button>
                </form>
                @endif
                @if($n->action_url)
                <a href="{{ $n->action_url }}" class="btn btn-ghost" style="padding:4px 8px;font-size:.78rem">View</a>
                @endif
            </div>
        </div>
        @empty
        <div class="empty" style="padding:40px 20px">
            <h3>No notifications</h3>
            <p>You're all caught up. Notifications about orders, products, and account changes will appear here.</p>
        </div>
        @endforelse
    </div>
    @if($notifications->hasPages())
    <div style="padding:12px 20px">{{ $notifications->links() }}</div>
    @endif
</div>

@endsection
