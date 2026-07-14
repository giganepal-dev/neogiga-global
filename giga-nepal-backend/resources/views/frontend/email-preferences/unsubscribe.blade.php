@extends('frontend.layout')

@section('title', 'Email preferences | NeoGiga')
@section('description', 'Confirm your NeoGiga marketing email preferences.')

@section('content')
<section class="section">
    <div class="wrap" style="max-width:720px">
        <div class="panel" style="padding:clamp(24px,5vw,44px)">
            <span class="eyebrow">Email preferences</span>
            <h1 class="section-title" style="margin:10px 0 14px">Marketing email unsubscribe</h1>
            @if(session('status'))<p class="badge b-ok" role="status">{{ session('status') }}</p>@endif
            <p class="sub">Email address: <strong>{{ $maskedEmail }}</strong></p>
            @if($confirmed)
                <p>Your marketing unsubscribe is confirmed. NeoGiga may still send essential order, invoice, security, and account messages.</p>
                <a class="btn btn-ghost" href="{{ route('email.preferences', ['token' => $token]) }}">Review preferences</a>
            @else
                <p>This page does not unsubscribe you automatically. Confirm below to stop marketing email.</p>
                <form method="post" action="{{ route('email.unsubscribe.confirm', ['token' => $token]) }}">
                    @csrf
                    <div class="field">
                        <label for="reason">Reason (optional)</label>
                        <textarea class="control" id="reason" name="reason" rows="3" maxlength="500">{{ old('reason') }}</textarea>
                    </div>
                    <label style="display:flex;gap:10px;align-items:flex-start;margin:18px 0">
                        <input type="checkbox" name="confirmation" value="1" required>
                        <span>I confirm that I want to stop all NeoGiga marketing email.</span>
                    </label>
                    @error('confirmation')<p style="color:#fca5a5">{{ $message }}</p>@enderror
                    <button class="btn btn-primary" type="submit">Confirm unsubscribe</button>
                </form>
            @endif
        </div>
    </div>
</section>
@endsection
