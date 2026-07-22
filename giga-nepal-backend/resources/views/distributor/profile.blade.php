@extends('distributor.layout')
@section('title','Edit Profile')
@section('content')
<div class="page-intro"><h1>Profile</h1><p>Company details visible to NeoGiga operations.</p></div>
<div class="card"><div class="card-body">
    <form method="post" action="/distributor/profile">@csrf
        <div class="form-grid">
            <div class="field"><label>Company name</label><input class="control" name="name" value="{{ old('name', $distributor->name) }}" required></div>
            <div class="field"><label>Phone</label><input class="control" name="phone" value="{{ old('phone', $distributor->phone) }}"></div>
            <div class="field"><label>Operating scope</label><select class="control" name="operating_scope" required @disabled($distributor->status !== 'pending')><option value="country" @selected(old('operating_scope',$distributor->operating_scope ?? 'country')==='country')>Single country / regional</option><option value="global" @selected(old('operating_scope',$distributor->operating_scope ?? 'country')==='global')>Global distributor</option></select>@if($distributor->status !== 'pending')<input type="hidden" name="operating_scope" value="{{ $distributor->operating_scope ?? 'country' }}">@endif</div>
            <div class="field"><label>Registration country</label><select class="control" name="country_id" required @disabled($distributor->status !== 'pending')>@foreach($countries as $country)<option value="{{ $country->id }}" @selected((string)old('country_id',$distributor->country_id)===(string)$country->id)>{{ $country->name }} ({{ $country->iso_code_2 }})</option>@endforeach</select>@if($distributor->status !== 'pending')<input type="hidden" name="country_id" value="{{ $distributor->country_id }}">@endif</div>
            <div class="field"><label>Website</label><input class="control" name="website" value="{{ old('website', ($distributor->metadata['website'] ?? null)) }}" placeholder="https://"></div>
        </div>
        <div class="field"><label>Description</label><textarea class="control" name="description" rows="4">{{ old('description', ($distributor->metadata['description'] ?? '')) }}</textarea></div>
        <p class="sub">{{ $distributor->status === 'pending' ? 'Scope can be adjusted while onboarding is pending.' : 'Open a support ticket to request a reviewed country or global-scope change.' }}</p>
        <button class="btn btn-primary" type="submit">Save profile</button>
    </form>
</div></div>
@endsection
