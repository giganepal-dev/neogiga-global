@extends('frontend.account.layout')
@section('title',$ticket->ticket_number.' — NeoGiga Support')
@section('account-content')
<header class="account-topbar"><div><h1>{{ $ticket->subject }}</h1><p><span class="mono">{{ $ticket->ticket_number }}</span> · {{ str_replace('_',' ',$ticket->status) }}</p></div><a class="account-button secondary" href="/account/support">All tickets</a></header>
<section class="account-panel account-thread">
    @foreach($messages as $message)<article class="account-message {{ $message->sender_type === 'customer' ? 'customer' : 'team' }}"><div><strong>{{ $message->sender_type === 'customer' ? 'You' : 'NeoGiga support' }}</strong><time>{{ \Carbon\Carbon::parse($message->created_at)->format('d M Y, H:i') }}</time></div><p>{{ $message->message }}</p></article>@endforeach
</section>
<section class="account-panel"><form class="account-form" method="post" action="/account/support/{{ $ticket->id }}/reply">@csrf<div class="account-field"><label for="message">Reply</label><textarea id="message" name="message" required></textarea></div><div><button class="account-button" type="submit">Send reply</button></div></form></section>
@endsection
