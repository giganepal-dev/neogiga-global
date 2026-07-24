@extends('frontend.layout')
@section('title', $path->name . ' — Learning Path')
@section('content')
<div style="max-width:1000px;margin:0 auto;padding:24px">
    <div style="font-size:.85rem;color:#64748b;margin-bottom:16px"><a href="{{ url($localePrefix ?? '/en') }}/ai" style="color:#3b82f6;text-decoration:none">AI & Robotics</a> / <a href="{{ url($localePrefix ?? '/en') }}/ai/learning" style="color:#3b82f6;text-decoration:none">Learning</a> / {{ $path->name }}</div>
    <div style="display:flex;justify-content:space-between;align-items:start;margin-bottom:24px">
        <div>
            <h1 style="font-size:1.8rem;font-weight:800;margin-bottom:8px">{{ $path->name }}</h1>
            <p style="color:#64748b">{{ $path->description }} · {{ $path->courses_count ?? 0 }} courses · {{ $path->estimated_hours ?? '—' }}h</p>
        </div>
        <span style="padding:6px 14px;background:{{ $path->level==='beginner'?'#dcfce7':($path->level==='intermediate'?'#fef3c7':'#fee2e2') }};color:{{ $path->level==='beginner'?'#166534':($path->level==='intermediate'?'#92400e':'#991b1b') }};border-radius:8px;font-size:.85rem;font-weight:600;text-transform:capitalize">{{ $path->level }}</span>
    </div>
    @if($path->courses->count())
    <div style="display:grid;gap:12px">
        @foreach($path->courses as $i => $course)
        <div style="display:flex;gap:16px;padding:20px;background:#f8fafc;border:1px solid #e2e8f0;border-radius:12px;align-items:center">
            <div style="width:40px;height:40px;background:#3b82f6;color:#fff;border-radius:50%;display:flex;align-items:center;justify-content:center;font-weight:700;flex-shrink:0">{{ $i+1 }}</div>
            <div style="flex:1">
                <div style="font-weight:700">{{ $course->title ?? $course->name }}</div>
                <div style="font-size:.85rem;color:#64748b">{{ Str::limit($course->description, 120) }}</div>
            </div>
            @if($course->pivot->is_required)<span style="padding:2px 8px;background:#fee2e2;color:#991b1b;border-radius:4px;font-size:.7rem">Required</span>@endif
        </div>
        @endforeach
    </div>
    @else
    <p style="color:#64748b;text-align:center;padding:32px">Courses are being added to this learning path.</p>
    @endif
</div>
@endsection
