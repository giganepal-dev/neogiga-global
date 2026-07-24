@extends('frontend.layout')
@section('title', 'Request a Demo — NeoGiga AI & Robotics')
@section('meta_description', 'Request a demonstration of AI and robotics products from NeoGiga.')
@section('content')
<div style="max-width:800px;margin:0 auto;padding:24px">
    <div style="font-size:.85rem;color:#64748b;margin-bottom:16px"><a href="{{ url($localePrefix ?? '/en') }}/ai" style="color:#3b82f6;text-decoration:none">AI & Robotics</a> / Request a Demo</div>
    <h1 style="font-size:1.8rem;font-weight:800;margin-bottom:8px">Request a Demo</h1>
    <p style="color:#64748b;margin-bottom:32px">Tell us about your needs and we'll arrange a demonstration.</p>

    @if(session('status'))
        <div style="background:#dcfce7;border:1px solid #86efac;color:#166534;padding:16px;border-radius:8px;margin-bottom:24px">{{ session('status') }}</div>
    @endif
    @if($errors->any())
        <div style="background:#fee2e2;border:1px solid #fca5a5;color:#991b1b;padding:16px;border-radius:8px;margin-bottom:24px">{{ $errors->first() }}</div>
    @endif

    <form method="POST" action="{{ url($localePrefix ?? '/en') }}/ai/demo-request">
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
                <label style="display:block;font-weight:600;margin-bottom:4px">Robot Model</label>
                <select name="robot_model_id" style="width:100%;padding:10px 12px;border:1px solid #d1d5db;border-radius:6px">
                    <option value="">Any / General</option>
                    @foreach($robots as $robot)
                        <option value="{{ $robot->id }}" {{ old('robot_model_id') == $robot->id ? 'selected' : '' }}>{{ $robot->name }} — {{ $robot->manufacturer?->name ?? '' }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label style="display:block;font-weight:600;margin-bottom:4px">Preferred Manufacturer</label>
                <select name="manufacturer_id" style="width:100%;padding:10px 12px;border:1px solid #d1d5db;border-radius:6px">
                    <option value="">Any</option>
                    @foreach($manufacturers as $mfr)
                        <option value="{{ $mfr->id }}" {{ old('manufacturer_id') == $mfr->id ? 'selected' : '' }}>{{ $mfr->name }}</option>
                    @endforeach
                </select>
            </div>
        </div>
        <div style="margin-top:16px">
            <label style="display:block;font-weight:600;margin-bottom:4px">Requirements / Notes</label>
            <textarea name="requirements" rows="4" style="width:100%;padding:10px 12px;border:1px solid #d1d5db;border-radius:6px" placeholder="Tell us about your use case, timeline, and any specific requirements...">{{ old('requirements') }}</textarea>
        </div>
        <div style="margin-top:24px">
            <button type="submit" style="padding:14px 32px;background:#3b82f6;color:#fff;border:none;border-radius:8px;font-weight:600;cursor:pointer">Submit Demo Request</button>
        </div>
    </form>
</div>
@endsection
