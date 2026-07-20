@extends('distributor.layout')
@section('title','Edit Profile')
@section('content')
<div class="page-intro"><h1>Profile</h1><p>Company details visible to NeoGiga operations.</p></div>
<div class="card"><div class="card-body">
    <form method="post" action="/distributor/profile">@csrf
        <div class="form-grid">
            <div class="field"><label>Company name</label><input class="control" name="name" value="{{ old('name', $distributor->name) }}" required></div>
            <div class="field"><label>Phone</label><input class="control" name="phone" value="{{ old('phone', $distributor->phone) }}"></div>
            <div class="field"><label>Country ID</label><input class="control" name="country_id" value="{{ old('country_id', $distributor->country_id) }}"></div>
            <div class="field"><label>Website</label><input class="control" name="website" value="{{ old('website', ($distributor->metadata['website'] ?? null)) }}" placeholder="https://"></div>
        </div>
        <div class="field"><label>Description</label><textarea class="control" name="description" rows="4">{{ old('description', ($distributor->metadata['description'] ?? '')) }}</textarea></div>
        <button class="btn btn-primary" type="submit">Save profile</button>
    </form>
</div></div>
@endsection
