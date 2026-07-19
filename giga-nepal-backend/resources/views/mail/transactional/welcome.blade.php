@extends('mail.transactional.layout')

@section('content')
<h2>{{ $greeting ?? 'Welcome to NeoGiga!' }}</h2>

<p>Your account has been created successfully. You can now sign in and explore the NeoGiga engineering marketplace.</p>

<p><strong>Account details:</strong></p>
<p>
  Name: {{ $userName ?? 'Customer' }}<br>
  Email: {{ $userEmail ?? '' }}<br>
  Marketplace: {{ $regionName ?? 'Global' }}
</p>

<a class="btn" href="{{ $loginUrl ?? 'https://neogiga.com/en/login' }}">Sign in to your account</a>

<p class="muted" style="margin-top:16px">If you did not create this account, please contact our support team immediately.</p>
@endsection
