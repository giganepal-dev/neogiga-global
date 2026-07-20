@extends('manufacturer.layout')
@section('title','Edit Profile')
@section('content')
<h1 style="margin:0 0 24px">Edit Profile</h1>
@if(session('status'))<div class="card" style="margin-bottom:16px;border-color:rgba(16,185,129,.3);color:#34d399">{{ session('status') }}</div>@endif
<div class="card">
    <form method="post" action="/manufacturer/profile" enctype="multipart/form-data">
        @csrf
        @if($manufacturer->logo_path)<div style="margin-bottom:12px"><img src="{{ asset('storage/'.$manufacturer->logo_path) }}" style="max-width:160px;max-height:80px;object-fit:contain;background:#081527;border-radius:8px;border:1px solid var(--line);padding:8px"></div>@endif
        <div style="display:grid;grid-template-columns:1fr 1fr;gap:14px">
            <div class="field"><label style="font-weight:600;font-size:.8rem;color:var(--muted)">Legal Name</label><input class="control" style="width:100%;border:1px solid var(--line);border-radius:8px;min-height:44px;padding:8px 12px;background:var(--bg);color:var(--on)" name="legal_name" value="{{ old('legal_name', $manufacturer->legal_name) }}"></div>
            <div class="field"><label style="font-weight:600;font-size:.8rem;color:var(--muted)">Website</label><input class="control" style="width:100%;border:1px solid var(--line);border-radius:8px;min-height:44px;padding:8px 12px;background:var(--bg);color:var(--on)" name="official_website" value="{{ old('official_website', $manufacturer->official_website) }}" placeholder="https://"></div>
            <div class="field"><label style="font-weight:600;font-size:.8rem;color:var(--muted)">Country</label><input class="control" style="width:100%;border:1px solid var(--line);border-radius:8px;min-height:44px;padding:8px 12px;background:var(--bg);color:var(--on)" name="country_of_origin" value="{{ old('country_of_origin', $manufacturer->country_of_origin) }}"></div>
            <div class="field"><label style="font-weight:600;font-size:.8rem;color:var(--muted)">Company Logo</label><input class="control" style="width:100%;border:1px solid var(--line);border-radius:8px;min-height:44px;padding:8px 12px;background:var(--bg);color:var(--on)" type="file" name="logo" accept="image/*"></div>
        </div>
        <div class="field" style="margin-top:14px"><label style="font-weight:600;font-size:.8rem;color:var(--muted)">Company Overview</label><textarea class="control" style="width:100%;border:1px solid var(--line);border-radius:8px;min-height:100px;padding:8px 12px;background:var(--bg);color:var(--on)" name="overview" rows="4">{{ old('overview', $manufacturer->overview) }}</textarea></div>
        <button class="btn btn-ghost" type="submit" style="background:var(--cyan);color:#003640;border-color:transparent;margin-top:14px">Save Profile</button>
    </form>
</div>
@endsection
