@extends('pcb.layout')

@section('title', ($mode === 'register' ? 'Create PCB Workspace' : 'Sign in to PCB Workspace').' — NeoGiga PCB')
@section('robots', 'noindex,nofollow')

@push('styles')
<style>
    .auth-shell{min-height:calc(100vh - 180px);display:grid;place-items:center;padding:48px 0}.auth-panel{width:min(480px,100%)}.auth-panel .card-body{padding:24px}.auth-panel h1{font-size:1.8rem;margin:0 0 8px}.auth-panel .lead{margin-bottom:22px}.auth-switch{text-align:center;color:var(--muted);font-size:.88rem;margin-top:16px}.auth-switch a{color:#0e7490;font-weight:800}
</style>
@endpush

@section('content')
<section class="auth-shell">
    <div class="wrap auth-panel">
        @if($errors->any())<div class="errors"><ul>@foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul></div>@endif
        <div class="card">
            <div class="card-body">
                <div class="eyebrow">Secure PCB workspace</div>
                <h1>{{ $mode === 'register' ? 'Create your account' : 'Welcome back' }}</h1>
                <p class="lead">{{ $mode === 'register' ? 'Your projects and design files remain private to authorized project members.' : 'Access your projects, files, quotes and production status.' }}</p>
                <form method="post" action="{{ $mode === 'register' ? '/en/register' : '/en/login' }}">
                    @csrf
                    <div class="form-grid">
                        @if($mode === 'register')
                            <div class="field full"><label for="name">Full name</label><input class="control" id="name" name="name" value="{{ old('name') }}" required autocomplete="name"></div>
                        @endif
                        <div class="field full"><label for="email">Email</label><input class="control" id="email" type="email" name="email" value="{{ old('email') }}" required autocomplete="email"></div>
                        <div class="field full"><label for="password">Password</label><input class="control" id="password" type="password" name="password" required autocomplete="{{ $mode === 'register' ? 'new-password' : 'current-password' }}"></div>
                        @if($mode === 'register')
                            <div class="field full"><label for="password_confirmation">Confirm password</label><input class="control" id="password_confirmation" type="password" name="password_confirmation" required autocomplete="new-password"></div>
                            <label class="check field full"><input type="checkbox" name="terms" value="1" required> I agree to the NeoGiga terms and private-file handling policy.</label>
                        @else
                            <label class="check field full"><input type="checkbox" name="remember" value="1"> Keep me signed in on this device</label>
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
