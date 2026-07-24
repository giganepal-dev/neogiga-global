@extends('admin.layout')
@section('title', 'AI Session Details')
@section('crumb', 'AI Commerce / Sessions / ' . substr($session->session_id, 0, 12))

@section('content')
<div class="page-head">
    <div>
        <h2>Session Details</h2>
        <p style="color:var(--muted)">
            <code style="font-size:.82rem">{{ $session->session_id }}</code>
            &middot; {{ $session->context }} context
            &middot; {{ $session->user_name ?? 'Anonymous' }}
        </p>
    </div>
    <div class="page-actions" style="display:flex;gap:8px">
        <a href="/admin/ai-commerce/sessions" class="btn btn-ghost">Back to Sessions</a>
        @php $isActive = ! $session->expires_at || \Carbon\Carbon::parse($session->expires_at)->isFuture(); @endphp
        @if($isActive)
            <form method="POST" action="/admin/ai-commerce/sessions/{{ $session->session_id }}/terminate" style="display:inline" onsubmit="return confirm('Terminate this session?')">
                @csrf
                <button class="btn btn-ghost" style="color:var(--danger)">Terminate Session</button>
            </form>
        @endif
    </div>
</div>

@if(session('status'))
    <div class="note" style="background:#dcfce7;border-color:#86efac;color:#166534">{{ session('status') }}</div>
@endif

<div style="display:grid;grid-template-columns:1fr 320px;gap:16px;align-items:start">
    {{-- Conversation --}}
    <div class="card">
        <div class="card-h"><h2>Conversation ({{ count($messages) }} messages)</h2></div>
        <div class="card-body">
            @if(count($messages) > 0)
                @foreach($messages as $msg)
                @php
                    $role = $msg['role'] ?? 'unknown';
                    $content = $msg['content'] ?? '';
                    $isUser = $role === 'user';
                @endphp
                <div style="margin-bottom:16px;padding:12px;border-radius:8px;background:{{ $isUser ? 'var(--bg)' : '#f0f7ff' }};border:1px solid {{ $isUser ? 'var(--border)' : '#bfdbfe' }}">
                    <div style="font-size:.78rem;font-weight:600;color:{{ $isUser ? 'var(--muted)' : '#1e40af' }};margin-bottom:6px;text-transform:uppercase">
                        {{ $role === 'user' ? 'User' : ($role === 'assistant' ? 'AI' : $role) }}
                    </div>
                    <div style="font-size:.88rem;white-space:pre-wrap;line-height:1.5">{{ $content }}</div>
                </div>
                @endforeach
            @else
                <p style="color:var(--muted);text-align:center;padding:20px">No messages in this session.</p>
            @endif
        </div>
    </div>

    {{-- Session Info --}}
    <div>
        <div class="card">
            <div class="card-h"><h2>Session Info</h2></div>
            <div class="card-body">
                <div style="display:grid;gap:12px;font-size:.88rem">
                    <div>
                        <div class="sub">Session ID</div>
                        <div class="mono" style="font-size:.78rem;word-break:break-all">{{ $session->session_id }}</div>
                    </div>
                    <div>
                        <div class="sub">User</div>
                        <div>{{ $session->user_name ?? 'Anonymous' }}</div>
                        @if($session->user_email)
                            <div style="color:var(--muted);font-size:.82rem">{{ $session->user_email }}</div>
                        @endif
                    </div>
                    <div>
                        <div class="sub">Context</div>
                        <div><span class="badge b-muted">{{ $session->context }}</span></div>
                    </div>
                    <div>
                        <div class="sub">Current Goal</div>
                        <div>{{ $session->current_goal ?? '—' }}</div>
                    </div>
                    <div>
                        <div class="sub">Status</div>
                        <div>
                            @if($isActive)
                                <span class="badge b-ok">active</span>
                            @else
                                <span class="badge b-muted">ended</span>
                            @endif
                        </div>
                    </div>
                    <div>
                        <div class="sub">Created</div>
                        <div>{{ $session->created_at }}</div>
                    </div>
                    <div>
                        <div class="sub">Expires</div>
                        <div>{{ $session->expires_at ? \Carbon\Carbon::parse($session->expires_at)->diffForHumans() : 'Never' }}</div>
                    </div>
                </div>
            </div>
        </div>

        @if($session->metadata)
        <div class="card" style="margin-top:16px">
            <div class="card-h"><h2>Metadata</h2></div>
            <div class="card-body">
                <pre style="background:var(--bg);padding:12px;border-radius:6px;font-size:11px;overflow-x:auto;max-height:300px;overflow-y:auto">{{ json_encode(json_decode($session->metadata, true), JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) }}</pre>
            </div>
        </div>
        @endif
    </div>
</div>
@endsection
