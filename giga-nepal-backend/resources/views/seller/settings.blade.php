@extends('seller.layout')
@section('title', 'Settings')
@section('content')

<div class="page-intro">
    <h1>Settings</h1>
    <p>Manage your account preferences and notification settings.</p>
</div>

{{-- Account Info --}}
<div class="card">
    <div class="card-h"><h2>Account Information</h2><a href="/seller/profile" class="sub">Edit profile →</a></div>
    <div class="card-body">
        <div style="display:grid;grid-template-columns:1fr 1fr;gap:16px">
            <div>
                <div class="sub" style="font-size:.82rem;margin-bottom:4px">Business Name</div>
                <div style="font-weight:600">{{ $v->name }}</div>
            </div>
            <div>
                <div class="sub" style="font-size:.82rem;margin-bottom:4px">Email</div>
                <div>{{ $v->email ?? 'Not set' }}</div>
            </div>
            <div>
                <div class="sub" style="font-size:.82rem;margin-bottom:4px">Phone</div>
                <div>{{ $v->phone ?? 'Not set' }}</div>
            </div>
            <div>
                <div class="sub" style="font-size:.82rem;margin-bottom:4px">Operating Scope</div>
                <div>{{ ucfirst($v->operating_scope ?? 'country') }}</div>
            </div>
            <div>
                <div class="sub" style="font-size:.82rem;margin-bottom:4px">Account Status</div>
                <div>
                    <span class="badge {{ ($v->status ?? '') === 'active' ? 'b-ok' : 'b-warn' }}">
                        {{ ucfirst($v->status ?? 'pending') }}
                    </span>
                </div>
            </div>
            <div>
                <div class="sub" style="font-size:.82rem;margin-bottom:4px">Verification</div>
                <div>
                    <span class="badge {{ ($v->is_verified ?? false) ? 'b-ok' : 'b-warn' }}">
                        {{ ($v->is_verified ?? false) ? 'Verified' : 'Pending' }}
                    </span>
                </div>
            </div>
        </div>
    </div>
</div>

{{-- Notification Preferences --}}
<div class="card">
    <div class="card-h"><h2>Notification Preferences</h2></div>
    <div class="card-body">
        @if(count($notificationPreferences) > 0)
        <p class="sub" style="margin-bottom:12px">You receive notifications for these events:</p>
        <div style="display:grid;gap:8px">
            @foreach($notificationPreferences as $event)
            <div style="display:flex;align-items:center;gap:10px;padding:8px 12px;background:rgba(127,127,127,.04);border-radius:8px">
                <span style="width:8px;height:8px;border-radius:50%;background:var(--info)"></span>
                <span>{{ ucfirst(str_replace('_', ' ', $event)) }}</span>
            </div>
            @endforeach
        </div>
        @else
        <p class="sub">No notification events recorded yet. You will receive notifications as you use the platform.</p>
        @endif
    </div>
</div>

{{-- Quick Links --}}
<div class="card">
    <div class="card-h"><h2>Quick Links</h2></div>
    <div class="card-body" style="display:grid;gap:8px">
        <a href="/seller/profile" class="btn btn-ghost" style="justify-content:start">
            <x-icon name="profile" :size="16" /> Edit Business Profile
        </a>
        <a href="/seller/readiness" class="btn btn-ghost" style="justify-content:start">
            <x-icon name="checklist" :size="16" /> View Onboarding Checklist
        </a>
        <a href="/seller/documents" class="btn btn-ghost" style="justify-content:start">
            <x-icon name="document" :size="16" /> Manage Documents
        </a>
        <a href="/seller/support" class="btn btn-ghost" style="justify-content:start">
            <x-icon name="support" :size="16" /> Contact Support
        </a>
    </div>
</div>

@endsection
