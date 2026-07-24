@extends('frontend.layout')
@section('title', $event->name . ' — NeoGiga AI & Robotics Events')
@section('meta_description', Str::limit($event->description, 160))
@section('content')
<div style="max-width:800px;margin:0 auto;padding:24px">
    <div style="font-size:.85rem;color:#64748b;margin-bottom:16px"><a href="{{ url($localePrefix ?? '/en') }}/ai" style="color:#3b82f6;text-decoration:none">AI & Robotics</a> / <a href="{{ url($localePrefix ?? '/en') }}/ai/events" style="color:#3b82f6;text-decoration:none">Events</a> / {{ $event->name }}</div>

    <span style="padding:3px 10px;background:#eff6ff;color:#2563eb;border-radius:6px;font-size:.75rem;text-transform:uppercase">{{ str_replace('_',' ',$event->event_type) }}</span>
    <h1 style="font-size:1.8rem;font-weight:800;margin:12px 0 8px">{{ $event->name }}</h1>

    <div style="display:flex;gap:24px;margin:20px 0;font-size:.95rem;color:#475569">
        <div>📅 {{ $event->starts_at->format('l, F d, Y') }} @if($event->ends_at) — {{ $event->ends_at->format('F d, Y') }}@endif</div>
        @if($event->location)<div>📍 {{ $event->location }} ({{ $event->location_type }})</div>@endif
    </div>

    @if($event->ticket_price > 0)
        <div style="padding:12px 16px;background:#fef3c7;border:1px solid #fde68a;border-radius:8px;margin-bottom:20px">
            <strong>Ticket: {{ $event->currency }} {{ number_format($event->ticket_price, 2) }}</strong>
            @if($event->max_attendees)<span style="margin-left:12px;color:#64748b">{{ $event->current_attendees }}/{{ $event->max_attendees }} registered</span>@endif
        </div>
    @else
        <div style="padding:12px 16px;background:#dcfce7;border:1px solid #86efac;border-radius:8px;margin-bottom:20px">
            <strong style="color:#166534">Free Event</strong>
            @if($event->max_attendees)<span style="margin-left:12px;color:#64748b">{{ $event->current_attendees }}/{{ $event->max_attendees }} registered</span>@endif
        </div>
    @endif

    @if($event->description)
    <div style="color:#475569;line-height:1.7;margin-bottom:32px">{!! nl2br(e($event->description)) !!}</div>
    @endif

    {{-- Registration Form --}}
    @if($event->starts_at->isFuture())
    <div style="background:#f8fafc;border:1px solid #e2e8f0;border-radius:12px;padding:24px">
        <h3 style="font-weight:700;margin-bottom:16px">Register for this Event</h3>

        @if(session('status'))
            <div style="background:#dcfce7;border:1px solid #86efac;color:#166534;padding:12px;border-radius:6px;margin-bottom:16px">{{ session('status') }}</div>
        @endif
        @if($errors->any())
            <div style="background:#fee2e2;border:1px solid #fca5a5;color:#991b1b;padding:12px;border-radius:6px;margin-bottom:16px">{{ $errors->first() }}</div>
        @endif

        <form method="POST" action="{{ url($localePrefix ?? '/en') }}/ai/events/{{ $event->slug }}/register">
            @csrf
            <div style="display:grid;grid-template-columns:1fr 1fr;gap:12px">
                <div>
                    <label style="display:block;font-weight:600;margin-bottom:4px">Name *</label>
                    <input type="text" name="name" value="{{ old('name') }}" required style="width:100%;padding:10px 12px;border:1px solid #d1d5db;border-radius:6px">
                </div>
                <div>
                    <label style="display:block;font-weight:600;margin-bottom:4px">Email *</label>
                    <input type="email" name="email" value="{{ old('email') }}" required style="width:100%;padding:10px 12px;border:1px solid #d1d5db;border-radius:6px">
                </div>
                <div>
                    <label style="display:block;font-weight:600;margin-bottom:4px">Phone</label>
                    <input type="text" name="phone" value="{{ old('phone') }}" style="width:100%;padding:10px 12px;border:1px solid #d1d5db;border-radius:6px">
                </div>
                <div>
                    <label style="display:block;font-weight:600;margin-bottom:4px">Institution</label>
                    <input type="text" name="institution" value="{{ old('institution') }}" style="width:100%;padding:10px 12px;border:1px solid #d1d5db;border-radius:6px">
                </div>
            </div>
            <button type="submit" style="margin-top:16px;padding:12px 24px;background:#3b82f6;color:#fff;border:none;border-radius:8px;font-weight:600;cursor:pointer">Register Now</button>
        </form>
    </div>
    @endif
</div>
@endsection
