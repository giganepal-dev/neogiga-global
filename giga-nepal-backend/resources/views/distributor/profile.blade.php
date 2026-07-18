@extends('distributor.layout')
@section('title','Edit Profile')
@section('content')
<h1 style="margin:0 0 24px">Edit Profile</h1>
@if(session('status'))<div class="card" style="margin-bottom:16px;border-color:rgba(16,185,129,.3);color:#34d399">{{ session('status') }}</div>@endif
<div class="card">
    <form method="post" action="/distributor/profile">@csrf
        <div style="display:grid;grid-template-columns:1fr 1fr;gap:14px">
            <div class="field"><label style="font-weight:600;font-size:.8rem;color:var(--muted)">Company Name</label><input class="control" style="width:100%;border:1px solid var(--line);border-radius:8px;min-height:44px;padding:8px 12px;background:var(--bg);color:var(--on)" name="name" value="{{ old('name', $d->name) }}" required></div>
            <div class="field"><label style="font-weight:600;font-size:.8rem;color:var(--muted)">Phone</label><input class="control" style="width:100%;border:1px solid var(--line);border-radius:8px;min-height:44px;padding:8px 12px;background:var(--bg);color:var(--on)" name="phone" value="{{ old('phone', $d->phone) }}"></div>
            <div class="field"><label style="font-weight:600;font-size:.8rem;color:var(--muted)">Country ID</label><input class="control" style="width:100%;border:1px solid var(--line);border-radius:8px;min-height:44px;padding:8px 12px;background:var(--bg);color:var(--on)" name="country_id" value="{{ old('country_id', $d->country_id) }}"></div>
            <div class="field"><label style="font-weight:600;font-size:.8rem;color:var(--muted)">Website</label><input class="control" style="width:100%;border:1px solid var(--line);border-radius:8px;min-height:44px;padding:8px 12px;background:var(--bg);color:var(--on)" name="website" value="{{ old('website', json_decode($d->metadata??'{}',true)['website'] ?? '') }}" placeholder="https://"></div>
        </div>
        <div class="field" style="margin-top:14px"><label style="font-weight:600;font-size:.8rem;color:var(--muted)">Description</label><textarea class="control" style="width:100%;border:1px solid var(--line);border-radius:8px;min-height:100px;padding:8px 12px;background:var(--bg);color:var(--on)" name="description" rows="4">{{ old('description', json_decode($d->metadata??'{}',true)['description'] ?? '') }}</textarea></div>
        <button class="btn btn-ghost" type="submit" style="background:var(--cyan);color:#003640;border-color:transparent;margin-top:14px">Save Profile</button>
    </form>
</div>
@endsection
