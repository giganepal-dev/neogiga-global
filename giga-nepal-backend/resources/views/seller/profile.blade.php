@extends('seller.layout')
@section('title','Edit Profile')
@section('content')
<h1 style="margin:0 0 24px">Edit Profile</h1>
@if(session('status'))<div class="card" style="margin-bottom:16px;border-color:rgba(16,185,129,.3);color:#34d399">{{ session('status') }}</div>@endif
<div class="card"><form method="post" action="/seller/profile">@csrf
    <div style="display:grid;grid-template-columns:1fr 1fr;gap:14px">
        <div class="field"><label style="font-weight:600;font-size:.8rem;color:var(--muted)">Business Name</label><input class="control" style="width:100%;border:1px solid var(--line);border-radius:8px;min-height:44px;padding:8px 12px;background:var(--bg);color:var(--on)" name="name" value="{{ old('name',$v->name) }}" required></div>
        <div class="field"><label style="font-weight:600;font-size:.8rem;color:var(--muted)">Email</label><input class="control" style="width:100%;border:1px solid var(--line);border-radius:8px;min-height:44px;padding:8px 12px;background:var(--bg);color:var(--on)" name="email" value="{{ old('email',$v->email) }}" type="email"></div>
        <div class="field"><label style="font-weight:600;font-size:.8rem;color:var(--muted)">Phone</label><input class="control" style="width:100%;border:1px solid var(--line);border-radius:8px;min-height:44px;padding:8px 12px;background:var(--bg);color:var(--on)" name="phone" value="{{ old('phone',$v->phone) }}"></div>
        <div class="field"><label style="font-weight:600;font-size:.8rem;color:var(--muted)">Website</label><input class="control" style="width:100%;border:1px solid var(--line);border-radius:8px;min-height:44px;padding:8px 12px;background:var(--bg);color:var(--on)" name="website" value="{{ old('website',$v->website) }}" placeholder="https://"></div>
        <div class="field"><label style="font-weight:600;font-size:.8rem;color:var(--muted)">Operating Scope</label><select class="control" style="width:100%;min-height:44px" name="operating_scope" required @disabled($v->status !== 'pending')><option value="country" @selected(old('operating_scope',$v->operating_scope ?? 'country')==='country')>Single country</option><option value="global" @selected(old('operating_scope',$v->operating_scope ?? 'country')==='global')>Global seller</option></select>@if($v->status !== 'pending')<input type="hidden" name="operating_scope" value="{{ $v->operating_scope ?? 'country' }}">@endif</div>
        <div class="field"><label style="font-weight:600;font-size:.8rem;color:var(--muted)">Registration Country</label><select class="control" style="width:100%;min-height:44px" name="country_id" required @disabled($v->status !== 'pending')>@foreach($countries as $country)<option value="{{ $country->id }}" @selected((string)old('country_id',$v->country_id)===(string)$country->id)>{{ $country->name }} ({{ $country->iso_code_2 }})</option>@endforeach</select>@if($v->status !== 'pending')<input type="hidden" name="country_id" value="{{ $v->country_id }}">@endif</div>
    </div>
    <div class="field" style="margin-top:14px"><label style="font-weight:600;font-size:.8rem;color:var(--muted)">Description</label><textarea class="control" style="width:100%;border:1px solid var(--line);border-radius:8px;min-height:80px;padding:8px 12px;background:var(--bg);color:var(--on)" name="description" rows="3">{{ old('description',$v->description) }}</textarea></div>
    <p class="sub">{{ $v->status === 'pending' ? 'Scope can be adjusted while onboarding is pending.' : 'Open a support ticket to request a reviewed country or global-scope change.' }}</p>
    <button class="btn btn-ghost" type="submit" style="background:var(--cyan);color:#003640;border-color:transparent;margin-top:14px">Save Profile</button>
</form></div>
@endsection
