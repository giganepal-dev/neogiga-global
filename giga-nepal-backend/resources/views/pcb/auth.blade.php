@extends('pcb.layout')

@section('title', ($mode === 'register' ? 'Create PCB Workspace' : 'Sign in to PCB Workspace').' — NeoGiga PCB')
@section('robots', 'noindex,nofollow')

@push('styles')
<style nonce="{{ $csp_nonce ?? '' }}">
    .auth-shell{min-height:calc(100vh - 180px);display:grid;place-items:center;padding:56px 0}
    .auth-panel{width:min(480px,100%)}.auth-panel h1{font-size:1.7rem;margin:0 0 8px}.auth-switch{text-align:center;color:var(--muted);font-size:.86rem;margin-top:18px}.auth-switch a{color:var(--cyan);font-weight:700}
</style>
@endpush

@section('content')
<section class="auth-shell">
    <div class="wrap auth-panel">
        @if($errors->any())<div class="errors"><ul>@foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul></div>@endif
        <div class="card">
            <div class="card-body" style="padding:28px">
                <div class="eyebrow">Secure PCB workspace</div>
                <h1>{{ $mode === 'register' ? 'Create your account' : 'Welcome back' }}</h1>
                <p class="muted" style="margin-bottom:22px">{{ $mode === 'register' ? 'Your projects and design files remain private to authorized project members.' : 'Access your projects, files, quotes and production status.' }}</p>
                <form method="post" action="{{ $mode === 'register' ? '/en/register' : '/en/login' }}">
                    @csrf
                    <div class="form-grid">
                        @if($mode === 'register')
                            <div class="field full"><label for="name">Full name</label><input class="control" id="name" name="name" value="{{ old('name') }}" required autocomplete="name" placeholder="Your name"></div>
                        @endif
                        <div class="field full"><label for="email">Email</label><input class="control" id="email" type="email" name="email" value="{{ old('email') }}" required autocomplete="email" placeholder="you@example.com"></div>
                        <div class="field full"><label for="password">Password</label><input class="control" id="password" type="password" name="password" required autocomplete="{{ $mode === 'register' ? 'new-password' : 'current-password' }}" placeholder="{{ $mode === 'register' ? 'Min 10 characters' : 'Your password' }}"></div>
                        @if($mode === 'register')
                            <div class="field full"><label for="password_confirmation">Confirm password</label><input class="control" id="password_confirmation" type="password" name="password_confirmation" required autocomplete="new-password" placeholder="Type it again"></div>
                            <label class="check" style="grid-column:1/-1"><input type="checkbox" name="terms" value="1" required> I agree to the NeoGiga terms and private-file handling policy.</label>
                        @else
                            <label class="check" style="grid-column:1/-1"><input type="checkbox" name="remember" value="1"> Keep me signed in on this device</label>
                        @endif
                    </div>
                    <div class="form-actions"><button class="btn btn-primary" type="submit">{{ $mode === 'register' ? 'Create workspace' : 'Sign in' }}</button></div>
                </form>
            </div>
        </div>
        <p class="auth-switch">
            @if($mode === 'register')Already registered? <a href="/en/login">Sign in</a>@else New to NeoGiga PCB? <a href="/en/register">Create an account</a>@endif
        </p>
    </div>
</section>
@endsection
