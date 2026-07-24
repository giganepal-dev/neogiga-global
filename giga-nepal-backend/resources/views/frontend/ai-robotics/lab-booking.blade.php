@extends('frontend.layout')
@section('title', 'Book a Lab Session — NeoGiga AI & Robotics')
@section('meta_description', 'Book a physical lab session for robotics demonstrations, workshops, or training.')
@section('content')
<div style="max-width:800px;margin:0 auto;padding:24px">
    <div style="font-size:.85rem;color:#64748b;margin-bottom:16px"><a href="{{ url($localePrefix ?? '/en') }}/ai" style="color:#3b82f6;text-decoration:none">AI & Robotics</a> / <a href="{{ url($localePrefix ?? '/en') }}/ai/lab" style="color:#3b82f6;text-decoration:none">Lab</a> / Book a Session</div>
    <h1 style="font-size:1.8rem;font-weight:800;margin-bottom:8px">Book a Lab Session</h1>
    <p style="color:#64748b;margin-bottom:32px">Schedule a demonstration, workshop, or training session at our robotics lab.</p>

    @if(session('status'))
        <div style="background:#dcfce7;border:1px solid #86efac;color:#166534;padding:16px;border-radius:8px;margin-bottom:24px">{{ session('status') }}</div>
    @endif
    @if($errors->any())
        <div style="background:#fee2e2;border:1px solid #fca5a5;color:#991b1b;padding:16px;border-radius:8px;margin-bottom:24px">{{ $errors->first() }}</div>
    @endif

    <form method="POST" action="{{ url($localePrefix ?? '/en') }}/ai/lab/book">
        @csrf
        <div style="display:grid;grid-template-columns:1fr 1fr;gap:16px">
            <div>
                <label style="display:block;font-weight:600;margin-bottom:4px">Contact Name *</label>
                <input type="text" name="contact_name" value="{{ old('contact_name') }}" required style="width:100%;padding:10px 12px;border:1px solid #d1d5db;border-radius:6px">
            </div>
            <div>
                <label style="display:block;font-weight:600;margin-bottom:4px">Email *</label>
                <input type="email" name="contact_email" value="{{ old('contact_email') }}" required style="width:100%;padding:10px 12px;border:1px solid #d1d5db;border-radius:6px">
            </div>
            <div>
                <label style="display:block;font-weight:600;margin-bottom:4px">Phone</label>
                <input type="text" name="contact_phone" value="{{ old('contact_phone') }}" style="width:100%;padding:10px 12px;border:1px solid #d1d5db;border-radius:6px">
            </div>
            <div>
                <label style="display:block;font-weight:600;margin-bottom:4px">Institution / Company</label>
                <input type="text" name="institution_name" value="{{ old('institution_name') }}" style="width:100%;padding:10px 12px;border:1px solid #d1d5db;border-radius:6px">
            </div>
            <div>
                <label style="display:block;font-weight:600;margin-bottom:4px">Booking Type *</label>
                <select name="booking_type" required style="width:100%;padding:10px 12px;border:1px solid #d1d5db;border-radius:6px">
                    <option value="">Select type...</option>
                    <option value="demonstration" {{ old('booking_type') === 'demonstration' ? 'selected' : '' }}>Lab Demonstration</option>
                    <option value="workshop" {{ old('booking_type') === 'workshop' ? 'selected' : '' }}>Workshop</option>
                    <option value="training" {{ old('booking_type') === 'training' ? 'selected' : '' }}>Training Session</option>
                    <option value="testing" {{ old('booking_type') === 'testing' ? 'selected' : '' }}>Product Testing</option>
                    <option value="prototyping" {{ old('booking_type') === 'prototyping' ? 'selected' : '' }}>Prototype Development</option>
                </select>
            </div>
            <div>
                <label style="display:block;font-weight:600;margin-bottom:4px">Preferred Date *</label>
                <input type="date" name="preferred_date" value="{{ old('preferred_date') }}" required style="width:100%;padding:10px 12px;border:1px solid #d1d5db;border-radius:6px">
            </div>
            <div>
                <label style="display:block;font-weight:600;margin-bottom:4px">Preferred Time</label>
                <input type="time" name="preferred_time" value="{{ old('preferred_time') }}" style="width:100%;padding:10px 12px;border:1px solid #d1d5db;border-radius:6px">
            </div>
        </div>
        <div style="margin-top:16px">
            <label style="display:block;font-weight:600;margin-bottom:4px">Requirements / Notes</label>
            <textarea name="requirements" rows="4" style="width:100%;padding:10px 12px;border:1px solid #d1d5db;border-radius:6px" placeholder="Tell us about your group size, topics of interest, and any specific equipment you'd like to see...">{{ old('requirements') }}</textarea>
        </div>
        <div style="margin-top:24px">
            <button type="submit" style="padding:14px 32px;background:#3b82f6;color:#fff;border:none;border-radius:8px;font-weight:600;cursor:pointer">Submit Booking Request</button>
        </div>
    </form>
</div>
@endsection
