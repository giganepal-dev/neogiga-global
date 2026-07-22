@extends('frontend.layout')
@push('head')
<link rel="stylesheet" href="{{ asset('css/account-hub.css') }}?v=20260722pcb">
@endpush
@section('content')
@php
    $accountNav = [
        ['/account', 'Overview'], ['/account/orders', 'Orders'], ['/account/rfqs', 'RFQs'],
        ['/account/quotations', 'Quotations'], ['/account/bom', 'BOM projects'], ['/account/pcb', 'PCB projects'], ['/account/saved', 'Saved parts'],
        ['/account/notifications', 'Notifications'], ['/account/support', 'Support'], ['/account/payments', 'Payments'],
        ['/account/applications', 'Partner roles'], ['/account/addresses', 'Addresses'], ['/account/profile', 'Profile'],
        ['/account/security', 'Security'],
    ];
    $currentMarketplace = $marketplaceContext['current'] ?? null;
@endphp
<div class="account-stage">
    <div class="account-shell">
        <aside class="account-sidebar" aria-label="Account navigation">
            <div class="account-identity">
                <strong>{{ auth()->user()->name }}</strong>
                <span>{{ auth()->user()->email }}</span>
            </div>
            <form class="account-role-form" method="post" action="/account/role">
                @csrf
                <label for="account-role">Working as</label>
                <select id="account-role" name="role_key" onchange="this.form.submit()">
                    @foreach($accountRoles as $role)
                        <option value="{{ $role['key'] }}" @selected($role['current'])>{{ $role['label'] }}</option>
                    @endforeach
                </select>
            </form>
            <nav class="account-nav">
                @foreach($accountNav as [$url, $label])
                    <a href="{{ $url }}" @if(request()->path() === ltrim($url, '/')) aria-current="page" @endif>{{ $label }}</a>
                @endforeach
            </nav>
        </aside>
        <main class="account-main" id="account-main">
            @if(session('success'))<div class="account-alert success" role="status">{{ session('success') }}</div>@endif
            @if($errors->any())<div class="account-alert error" role="alert"><ul>@foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul></div>@endif
            @yield('account-content')
        </main>
    </div>
</div>
@endsection
