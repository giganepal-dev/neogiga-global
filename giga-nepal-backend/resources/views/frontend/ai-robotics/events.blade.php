@extends('frontend.layout')
@section('title', 'AI & Robotics Events — NeoGiga')
@section('meta_description', 'Webinars, workshops, competitions, and conferences in AI and robotics.')
@section('content')
<div style="max-width:1200px;margin:0 auto;padding:24px">
    <div style="font-size:.85rem;color:#64748b;margin-bottom:16px"><a href="{{ url($localePrefix ?? '/en') }}/ai" style="color:#3b82f6;text-decoration:none">AI & Robotics</a> / Events</div>
    <h1 style="font-size:1.8rem;font-weight:800;margin-bottom:24px">Events & Workshops</h1>

    @if($events->count())
    <h2 style="font-size:1.2rem;font-weight:700;margin-bottom:16px">Upcoming Events</h2>
    <div style="display:grid;grid-template-columns:repeat(auto-fill,minmax(300px,1fr));gap:16px;margin-bottom:40px">
        @foreach($events as $event)
        <div style="background:#fff;border:1px solid #e2e8f0;border-radius:12px;padding:24px">
            <div style="display:flex;justify-content:space-between;align-items:start;margin-bottom:8px">
                <div style="font-weight:700">{{ $event->name }}</div>
                <span style="padding:2px 8px;background:#eff6ff;color:#2563eb;border-radius:4px;font-size:.7rem;text-transform:uppercase">{{ str_replace('_',' ',$event->event_type) }}</span>
            </div>
            <div style="font-size:.85rem;color:#64748b">📅 {{ $event->starts_at->format('M d, Y') }}</div>
            @if($event->location)<div style="font-size:.85rem;color:#64748b;margin-top:4px">📍 {{ $event->location }} ({{ $event->location_type }})</div>@endif
            @if($event->ticket_price > 0)<div style="margin-top:8px;font-weight:600;color:#059669">{{ $event->currency }} {{ number_format($event->ticket_price, 2) }}</div>@else<div style="margin-top:8px;font-weight:600;color:#16a34a">Free</div>@endif
        </div>
        @endforeach
    </div>
    @endif

    @if($pastEvents->count())
    <h2 style="font-size:1.2rem;font-weight:700;margin-bottom:16px">Past Events</h2>
    <div style="display:grid;grid-template-columns:repeat(auto-fill,minmax(300px,1fr));gap:16px">
        @foreach($pastEvents as $event)
        <div style="background:#f8fafc;border:1px solid #e2e8f0;border-radius:12px;padding:20px;opacity:.7">
            <div style="font-weight:700">{{ $event->name }}</div>
            <div style="font-size:.85rem;color:#64748b">{{ $event->starts_at->format('M d, Y') }}</div>
        </div>
        @endforeach
    </div>
    @endif
</div>
@endsection
