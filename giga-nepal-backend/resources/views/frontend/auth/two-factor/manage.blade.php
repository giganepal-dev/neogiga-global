@extends('frontend.layout')

@section('title', 'Security Settings — NeoGiga')

@section('content')
<div class="section" style="max-width:520px;margin:60px auto">
    <div class="panel" style="padding:32px">
        <h1 style="font-size:24px;margin-bottom:16px">Security Settings</h1>

        <div style="display:flex;justify-content:space-between;align-items:center;padding:16px 0;border-bottom:1px solid var(--border)">
            <div>
                <strong>Two-Factor Authentication</strong>
                <p class="sub" style="margin:0">
                    @if($enabled)
                        Enabled since {{ $confirmedAt?->format('M j, Y') }}
                    @else
                        Not enabled
                    @endif
                </p>
            </div>
            <div>
                @if($enabled)
                    <span class="badge b-ok">Active</span>
                @else
                    <a href="{{ route('2fa.setup') }}" class="btn btn-primary btn-sm">Enable</a>
                @endif
            </div>
        </div>

        @if($enabled)
        <div style="display:flex;justify-content:space-between;align-items:center;padding:16px 0;border-bottom:1px solid var(--border)">
            <div>
                <strong>Recovery Codes</strong>
                <p class="sub" style="margin:0">Use if you lose your authenticator app</p>
            </div>
            <a href="{{ route('2fa.new-codes') }}" class="btn btn-ghost btn-sm">New Codes</a>
        </div>

        <div style="padding:16px 0">
            <form method="POST" action="{{ route('2fa.disable') }}" onsubmit="return confirm('Disable two-factor authentication? This will make your account less secure.')">
                @csrf
                <label class="form-label">Enter 6-digit code to disable</label>
                <div style="display:flex;gap:8px">
                    <input type="text" name="code" class="control" inputmode="numeric" pattern="[0-9]{6}" maxlength="6" placeholder="000000" required style="flex:1;font-family:monospace">
                    <button type="submit" class="btn btn-danger">Disable 2FA</button>
                </div>
                @error('code')
                    <p class="form-error">{{ $message }}</p>
                @enderror
            </form>
        </div>
        @endif

        <p class="sub" style="text-align:center;margin-top:16px">
            <a href="{{ route('frontend.account') }}">Back to account</a>
        </p>
    </div>
</div>
@endsection
