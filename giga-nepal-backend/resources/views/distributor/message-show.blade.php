@extends('distributor.layout')
@section('title','Conversation')
@section('content')
<div class="page-intro"><h1>Conversation</h1><p>Customer details are privacy-masked.</p></div>
<div class="card"><div class="card-body" style="display:grid;gap:12px">
    @foreach($messages as $msg)
    <div style="padding:10px 12px;border-radius:10px;background:{{ $msg['sender_is_self'] ? '#fef9c3' : '#f8fafc' }}">
        <strong>{{ $msg['sender_name'] }}</strong>
        <div>{{ $msg['body'] }}</div>
        <div class="sub">{{ $msg['created_at'] }}</div>
    </div>
    @endforeach
</div>
<form method="post" action="/distributor/messages/{{ $conversation }}" class="card-body">@csrf
    <div class="field"><label>Reply</label><textarea class="control" name="body" rows="3" required></textarea></div>
    <button type="submit" class="btn btn-primary">Send</button>
</form></div>
@endsection
