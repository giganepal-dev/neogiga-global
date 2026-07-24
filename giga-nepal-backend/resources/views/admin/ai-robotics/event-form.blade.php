@extends('admin.layout')
@section('title', $event ? 'Edit Event' : 'Add Event')
@section('crumb', 'AI & Robotics / Events / ' . ($event ? 'Edit' : 'New'))
@section('content')
<div class="page-head"><div><h2>{{ $event ? 'Edit' : 'Add' }} Event</h2></div><div class="page-actions"><a href="/admin/ai-robotics/events" class="btn btn-ghost">Back</a></div></div>
@if($errors->any())<div class="note" style="background:#fee2e2;border-color:#fca5a5;color:#991b1b">{{ $errors->first() }}</div>@endif
<form method="POST" action="{{ $event ? '/admin/ai-robotics/events/'.$event->id : '/admin/ai-robotics/events' }}">
    @csrf @if($event)@method('PUT')@endif
    <div class="card"><div class="card-body">
        <div class="form-grid">
            <div class="field"><label>Name *</label><input class="control" name="name" value="{{ old('name', $event->name ?? '') }}" required></div>
            <div class="field"><label>Slug *</label><input class="control" name="slug" value="{{ old('slug', $event->slug ?? '') }}" required></div>
            <div class="field"><label>Type *</label><select class="control" name="event_type" required><option value="">—</option>@foreach(['webinar','workshop','competition','conference','demo','hackathon'] as $t)<option value="{{ $t }}" {{ old('event_type', $event->event_type ?? '') === $t ? 'selected' : '' }}>{{ ucfirst($t) }}</option>@endforeach</select></div>
            <div class="field"><label>Location Type</label><select class="control" name="location_type"><option value="online" {{ old('location_type', $event->location_type ?? 'online') === 'online' ? 'selected' : '' }}>Online</option><option value="offline" {{ old('location_type', $event->location_type ?? '') === 'offline' ? 'selected' : '' }}>Offline</option><option value="hybrid" {{ old('location_type', $event->location_type ?? '') === 'hybrid' ? 'selected' : '' }}>Hybrid</option></select></div>
            <div class="field"><label>Location</label><input class="control" name="location" value="{{ old('location', $event->location ?? '') }}"></div>
            <div class="field"><label>Starts At *</label><input class="control" type="datetime-local" name="starts_at" value="{{ old('starts_at', $event->starts_at?->format('Y-m-d\TH:i') ?? '') }}" required></div>
            <div class="field"><label>Ends At</label><input class="control" type="datetime-local" name="ends_at" value="{{ old('ends_at', $event->ends_at?->format('Y-m-d\TH:i') ?? '') }}"></div>
            <div class="field"><label>Ticket Price</label><input class="control" type="number" step="0.01" name="ticket_price" value="{{ old('ticket_price', $event->ticket_price ?? '') }}"></div>
            <div class="field"><label>Max Attendees</label><input class="control" type="number" name="max_attendees" value="{{ old('max_attendees', $event->max_attendees ?? '') }}"></div>
        </div>
        <div class="field"><label>Description</label><textarea class="control" name="description" rows="3">{{ old('description', $event->description ?? '') }}</textarea></div>
        <div class="form-grid" style="margin-top:12px">
            <div class="field"><label style="display:flex;align-items:center;gap:8px"><input type="checkbox" name="is_active" value="1" {{ old('is_active', $event->is_active ?? 1) ? 'checked' : '' }}> Active</label></div>
            <div class="field"><label style="display:flex;align-items:center;gap:8px"><input type="checkbox" name="is_featured" value="1" {{ old('is_featured', $event->is_featured ?? 0) ? 'checked' : '' }}> Featured</label></div>
        </div>
    </div></div>
    <div style="margin-top:16px;display:flex;gap:8px"><button type="submit" class="btn btn-primary">{{ $event ? 'Update' : 'Create' }}</button><a href="/admin/ai-robotics/events" class="btn btn-ghost">Cancel</a></div>
</form>
@endsection
