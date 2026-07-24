@extends('mail.transactional.layout')
@section('content')
<h2>Password Reset Request</h2>

<p>Hello {{ $userName ?? 'there' }},</p>

<p>We received a request to reset your password for your NeoGiga account.</p>

<a class="btn" href="{{ $passwordResetUrl ?? '#' }}">Reset your password</a>

<p class="muted" style="margin-top:16px">This link expires in {{ $expiryMinutes ?? 60 }} minutes. If you didn't request a password reset, please ignore this email or contact support if you're concerned about your account security.</p>
@endsection
