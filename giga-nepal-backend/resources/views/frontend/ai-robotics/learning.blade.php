@extends('frontend.layout')
@section('title', 'AI & Robotics Learning — NeoGiga Academy')
@section('meta_description', 'Learn AI and robotics with courses, learning paths, and hands-on projects.')
@section('content')
<div style="max-width:1200px;margin:0 auto;padding:24px">
    <div style="font-size:.85rem;color:#64748b;margin-bottom:16px"><a href="{{ url($localePrefix ?? '/en') }}/ai" style="color:#3b82f6;text-decoration:none">AI & Robotics</a> / Learning</div>
    <h1 style="font-size:1.8rem;font-weight:800;margin-bottom:8px">AI & Robotics Academy</h1>
    <p style="color:#64748b;margin-bottom:32px">From beginner to advanced — learn AI, robotics, and engineering skills</p>

    @if($paths->count())
    <h2 style="font-size:1.3rem;font-weight:700;margin-bottom:16px">Learning Paths</h2>
    <div style="display:grid;grid-template-columns:repeat(auto-fill,minmax(300px,1fr));gap:16px;margin-bottom:40px">
        @foreach($paths as $path)
        <a href="{{ url($localePrefix ?? '/en') }}/ai/learning/{{ $path->slug }}" style="display:block;background:#fff;border:1px solid #e2e8f0;border-radius:12px;padding:24px;text-decoration:none;color:inherit">
            <div style="display:flex;justify-content:space-between;align-items:start">
                <div style="font-weight:700;font-size:1.1rem">{{ $path->name }}</div>
                <span style="padding:4px 10px;background:{{ $path->level==='beginner'?'#dcfce7':($path->level==='intermediate'?'#fef3c7':'#fee2e2') }};color:{{ $path->level==='beginner'?'#166534':($path->level==='intermediate'?'#92400e':'#991b1b') }};border-radius:6px;font-size:.75rem;font-weight:600;text-transform:capitalize">{{ $path->level }}</span>
            </div>
            <div style="font-size:.9rem;color:#64748b;margin-top:8px">{{ $path->description }}</div>
            <div style="margin-top:12px;font-size:.85rem;color:#475569">{{ $path->courses_count ?? 0 }} courses · {{ $path->estimated_hours ?? '—' }}h estimated</div>
        </a>
        @endforeach
    </div>
    @endif

    @if($courses->count())
    <h2 style="font-size:1.3rem;font-weight:700;margin-bottom:16px">Featured Courses</h2>
    <div style="display:grid;grid-template-columns:repeat(auto-fill,minmax(260px,1fr));gap:16px">
        @foreach($courses as $course)
        <div style="background:#f8fafc;border:1px solid #e2e8f0;border-radius:12px;padding:20px">
            <div style="font-weight:700;margin-bottom:4px">{{ $course->title ?? $course->name }}</div>
            <div style="font-size:.85rem;color:#64748b">{{ Str::limit($course->description, 100) }}</div>
            @if($course->estimated_hours)<div style="margin-top:8px;font-size:.8rem;color:#475569">⏱ {{ $course->estimated_hours }}h</div>@endif
        </div>
        @endforeach
    </div>
    @endif
</div>
@endsection
