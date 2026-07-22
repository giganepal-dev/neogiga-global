@extends('frontend.account.layout')
@section('title','Profile — NeoGiga')
@section('account-content')
<header class="account-topbar"><div><h1>Profile</h1><p>Contact and company details shared across your approved account roles.</p></div></header>
<section class="account-panel">
    <form class="account-form" method="post" action="/account/profile">@csrf @method('patch')
        <div class="account-form-grid">
            <div class="account-field full"><label for="name">Display name</label><input id="name" name="name" value="{{ old('name',$user->name) }}" required></div>
            <div class="account-field"><label for="first_name">First name</label><input id="first_name" name="first_name" value="{{ old('first_name',$profile->first_name ?? '') }}"></div>
            <div class="account-field"><label for="last_name">Last name</label><input id="last_name" name="last_name" value="{{ old('last_name',$profile->last_name ?? '') }}"></div>
            <div class="account-field"><label>Email</label><input value="{{ $user->email }}" disabled></div>
            <div class="account-field"><label for="phone">Phone</label><input id="phone" name="phone" value="{{ old('phone',$profile->phone ?? '') }}"></div>
            <div class="account-field"><label for="company_name">Company / institution</label><input id="company_name" name="company_name" value="{{ old('company_name',$profile->company_name ?? '') }}"></div>
            <div class="account-field"><label for="preferred_language">Preferred language</label><select id="preferred_language" name="preferred_language"><option value="en" @selected(($profile->preferred_language ?? 'en')==='en')>English</option><option value="ne" @selected(($profile->preferred_language ?? '')==='ne')>Nepali</option><option value="hi" @selected(($profile->preferred_language ?? '')==='hi')>Hindi</option></select></div>
        </div>
        <div><button class="account-button" type="submit">Save profile</button></div>
    </form>
</section>
@endsection
