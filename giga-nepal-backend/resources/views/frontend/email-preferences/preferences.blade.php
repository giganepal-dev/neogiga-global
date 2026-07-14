@extends('frontend.layout')

@section('title', 'Manage email preferences | NeoGiga')
@section('description', 'Manage your NeoGiga marketing email preferences.')

@section('content')
<section class="section">
    <div class="wrap" style="max-width:760px">
        <div class="panel" style="padding:clamp(24px,5vw,44px)">
            <span class="eyebrow">Email preferences</span>
            <h1 class="section-title" style="margin:10px 0 14px">Choose what you receive</h1>
            @if(session('status'))<p class="badge b-ok" role="status">{{ session('status') }}</p>@endif
            <p class="sub">Email address: <strong>{{ $maskedEmail }}</strong></p>
            <form method="post" action="{{ route('email.preferences.update', ['token' => $token]) }}">
                @csrf
                @method('PATCH')
                @if($categories->isNotEmpty())
                    <fieldset class="field" style="border:0;padding:0;margin:24px 0">
                        <legend style="font-weight:700;margin-bottom:10px">Topics</legend>
                        @foreach($categories as $category)
                            <label style="display:flex;gap:10px;margin:8px 0">
                                <input type="checkbox" name="categories[]" value="{{ $category->id }}" @checked(in_array((int) $category->id, $selectedCategories, true))>
                                <span>{{ $category->name }}</span>
                            </label>
                        @endforeach
                    </fieldset>
                @endif
                <div class="grid" style="grid-template-columns:repeat(auto-fit,minmax(180px,1fr))">
                    <div class="field"><label for="preferred_language">Language</label><input class="control" id="preferred_language" name="preferred_language" maxlength="12" value="{{ old('preferred_language', $preferences->preferred_language ?? 'en') }}" required></div>
                    <div class="field"><label for="preferred_format">Format</label><select class="control" id="preferred_format" name="preferred_format"><option value="html" @selected(($preferences->preferred_format ?? 'html') === 'html')>HTML</option><option value="text" @selected(($preferences->preferred_format ?? '') === 'text')>Plain text</option></select></div>
                    <div class="field"><label for="frequency">Frequency</label><select class="control" id="frequency" name="frequency"><option value="standard" @selected(($preferences->frequency ?? 'standard') === 'standard')>Standard</option><option value="weekly" @selected(($preferences->frequency ?? '') === 'weekly')>Weekly</option><option value="monthly" @selected(($preferences->frequency ?? '') === 'monthly')>Monthly</option></select></div>
                </div>
                <label style="display:flex;gap:10px;align-items:flex-start;margin:18px 0">
                    <input type="hidden" name="all_marketing_opt_out" value="0">
                    <input type="checkbox" name="all_marketing_opt_out" value="1" @checked((bool) ($preferences->all_marketing_opt_out ?? false))>
                    <span>Stop all marketing email. Essential transactional messages remain enabled.</span>
                </label>
                @if($errors->any())<p style="color:#fca5a5">Please review the highlighted preferences.</p>@endif
                <button class="btn btn-primary" type="submit">Save preferences</button>
                <a class="btn btn-ghost" href="{{ route('email.unsubscribe', ['token' => $token]) }}">Unsubscribe</a>
            </form>
        </div>
    </div>
</section>
@endsection
