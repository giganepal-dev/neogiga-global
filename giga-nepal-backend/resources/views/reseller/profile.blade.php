@extends('reseller.layout')
@section('title','Edit Profile')
@section('content')
<h1 style="margin:0 0 24px">Edit Profile</h1>
@if(session('status'))<div class="card" style="margin-bottom:16px;border-color:rgba(16,185,129,.3);color:#34d399">{{ session('status') }}</div>@endif
<div class="card"><form method="post" action="/reseller/profile">@csrf
    <div style="display:grid;grid-template-columns:1fr 1fr;gap:14px">
        <div class="field"><label style="font-weight:600;font-size:.8rem;color:var(--muted)">Company Name</label><input class="control" style="width:100%;border:1px solid var(--line);border-radius:8px;min-height:44px;padding:8px 12px;background:var(--bg);color:var(--on)" name="company_name" value="{{ old('company_name',$r2->company_name) }}" required></div>
        <div class="field"><label style="font-weight:600;font-size:.8rem;color:var(--muted)">Trading Name</label><input class="control" style="width:100%;border:1px solid var(--line);border-radius:8px;min-height:44px;padding:8px 12px;background:var(--bg);color:var(--on)" name="trading_name" value="{{ old('trading_name',$r2->trading_name) }}"></div>
        <div class="field"><label style="font-weight:600;font-size:.8rem;color:var(--muted)">Phone</label><input class="control" style="width:100%;border:1px solid var(--line);border-radius:8px;min-height:44px;padding:8px 12px;background:var(--bg);color:var(--on)" name="phone" value="{{ old('phone',$r2->phone) }}"></div>
        <div class="field"><label style="font-weight:600;font-size:.8rem;color:var(--muted)">Website</label><input class="control" style="width:100%;border:1px solid var(--line);border-radius:8px;min-height:44px;padding:8px 12px;background:var(--bg);color:var(--on)" name="website" value="{{ old('website',$r2->website) }}" placeholder="https://"></div>
    </div>
    <div class="field" style="margin-top:14px"><label style="font-weight:600;font-size:.8rem;color:var(--muted)">Business Address</label><textarea class="control" style="width:100%;border:1px solid var(--line);border-radius:8px;min-height:80px;padding:8px 12px;background:var(--bg);color:var(--on)" name="business_address" rows="3">{{ old('business_address',$r2->business_address) }}</textarea></div>
    <button class="btn btn-ghost" type="submit" style="background:var(--cyan);color:#003640;border-color:transparent;margin-top:14px">Save Profile</button>
</form></div>
@endsection
