@extends('reseller.layout')
@section('title','Reseller Sign In')
@section('content')
<div style="max-width:420px;margin:60px auto">
    <h1 style="margin:0 0 8px;font-size:1.5rem">Reseller Sign In</h1>
    <p style="color:var(--muted);margin:0 0 24px">Access your reseller portal.</p>
    <div class="card">
        <form method="post" action="/reseller/login">
            @csrf
            @if($errors->any())
                <div style="background:rgba(239,68,68,.1);border:1px solid rgba(239,68,68,.3);border-radius:8px;padding:10px 14px;margin-bottom:16px;color:#ef4444;font-size:.86rem">
                    {{ $errors->first() }}
                </div>
            @endif
            <div class="field" style="display:grid;gap:6px;margin-bottom:14px">
                <label style="font-weight:600;font-size:.8rem;color:var(--muted)">Email</label>
                <input class="control" style="width:100%;border:1px solid var(--line);border-radius:8px;min-height:44px;padding:8px 12px;background:var(--bg);color:var(--on)" name="email" type="email" value="{{ old('email') }}" required autofocus>
            </div>
            <div class="field" style="display:grid;gap:6px;margin-bottom:16px">
                <label style="font-weight:600;font-size:.8rem;color:var(--muted)">Password</label>
                <input class="control" style="width:100%;border:1px solid var(--line);border-radius:8px;min-height:44px;padding:8px 12px;background:var(--bg);color:var(--on)" name="password" type="password" required>
            </div>
            <button class="btn btn-ghost" type="submit" style="width:100%;justify-content:center;background:var(--cyan);color:#003640;border-color:transparent">Sign In</button>
        </form>
    </div>
</div>
@endsection
