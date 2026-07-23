@extends('seller.layout')
@section('title', $pageTitle ?? 'Coming Soon')
@section('content')
<div class="card">
    <div class="card-h"><h2>{{ $pageTitle ?? 'Coming Soon' }}</h2></div>
    <div class="card-body">
        <div class="empty-card">
            <p>{{ $pageMessage ?? 'This section is under development and will be available soon.' }}</p>
            <a href="/seller" class="btn btn-primary">Back to Dashboard</a>
        </div>
    </div>
</div>
@endsection
