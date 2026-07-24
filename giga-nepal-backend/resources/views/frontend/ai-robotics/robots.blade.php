@extends('frontend.layout')

@section('title', 'Robot Explorer — NeoGiga AI & Robotics')
@section('meta_description', 'Explore and compare robot models from leading manufacturers. Find the perfect robot for your application.')

@section('content')
<div style="max-width:1200px;margin:0 auto;padding:24px">
    {{-- Breadcrumb --}}
    <div style="font-size:.85rem;color:#64748b;margin-bottom:16px">
        <a href="{{ url($localePrefix ?? '/en') }}/ai" style="color:#3b82f6;text-decoration:none">AI & Robotics</a> / Robot Explorer
    </div>

    <h1 style="font-size:1.8rem;font-weight:800;margin-bottom:24px">Robot Explorer</h1>

    {{-- Filters --}}
    <div style="background:#f8fafc;border:1px solid #e2e8f0;border-radius:12px;padding:20px;margin-bottom:24px">
        <form method="GET" style="display:flex;gap:12px;flex-wrap:wrap;align-items:end">
            <div style="flex:1;min-width:200px">
                <label style="display:block;font-size:.8rem;font-weight:600;margin-bottom:4px;color:#475569">Search</label>
                <input type="text" name="q" value="{{ request('q') }}" placeholder="Robot name, model number..." style="width:100%;padding:10px 12px;border:1px solid #d1d5db;border-radius:6px;font-size:.9rem">
            </div>
            <div style="min-width:150px">
                <label style="display:block;font-size:.8rem;font-weight:600;margin-bottom:4px;color:#475569">Type</label>
                <select name="type" style="width:100%;padding:10px 12px;border:1px solid #d1d5db;border-radius:6px;font-size:.9rem">
                    <option value="">All Types</option>
                    @foreach($types as $type)
                        <option value="{{ $type->id }}" {{ request('type') == $type->id ? 'selected' : '' }}>{{ $type->name }} ({{ $type->robot_models_count }})</option>
                    @endforeach
                </select>
            </div>
            <div style="min-width:150px">
                <label style="display:block;font-size:.8rem;font-weight:600;margin-bottom:4px;color:#475569">Manufacturer</label>
                <select name="manufacturer" style="width:100%;padding:10px 12px;border:1px solid #d1d5db;border-radius:6px;font-size:.9rem">
                    <option value="">All Manufacturers</option>
                    @foreach($manufacturers as $mfr)
                        <option value="{{ $mfr->id }}" {{ request('manufacturer') == $mfr->id ? 'selected' : '' }}>{{ $mfr->name }}</option>
                    @endforeach
                </select>
            </div>
            <div style="min-width:120px">
                <label style="display:block;font-size:.8rem;font-weight:600;margin-bottom:4px;color:#475569">ROS 2</label>
                <select name="ros" style="width:100%;padding:10px 12px;border:1px solid #d1d5db;border-radius:6px;font-size:.9rem">
                    <option value="">Any</option>
                    <option value="1" {{ request('ros') ? 'selected' : '' }}>Yes</option>
                </select>
            </div>
            <div style="min-width:120px">
                <label style="display:block;font-size:.8rem;font-weight:600;margin-bottom:4px;color:#475569">Use</label>
                <select name="use" style="width:100%;padding:10px 12px;border:1px solid #d1d5db;border-radius:6px;font-size:.9rem">
                    <option value="">Any</option>
                    <option value="indoor" {{ request('use')=='indoor' ? 'selected' : '' }}>Indoor</option>
                    <option value="outdoor" {{ request('use')=='outdoor' ? 'selected' : '' }}>Outdoor</option>
                    <option value="both" {{ request('use')=='both' ? 'selected' : '' }}>Both</option>
                </select>
            </div>
            <button type="submit" style="padding:10px 24px;background:#3b82f6;color:#fff;border:none;border-radius:6px;font-weight:600;cursor:pointer">Filter</button>
        </form>
    </div>

    {{-- Results --}}
    <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:16px">
        <span style="color:#64748b;font-size:.9rem">{{ $robots->total() }} robots found</span>
        @if(request()->hasAny(['q','type','manufacturer','ros','use']))
            <a href="{{ url($localePrefix ?? '/en') }}/ai/robots" style="color:#3b82f6;text-decoration:none;font-size:.85rem">Clear filters</a>
        @endif
    </div>

    <div style="display:grid;grid-template-columns:repeat(auto-fill,minmax(280px,1fr));gap:16px">
        @forelse($robots as $robot)
        <a href="{{ url($localePrefix ?? '/en') }}/ai/robots/{{ $robot->slug }}" style="display:block;background:#fff;border:1px solid #e2e8f0;border-radius:12px;padding:20px;text-decoration:none;color:inherit;transition:box-shadow .15s" onmouseover="this.style.boxShadow='0 4px 12px rgba(0,0,0,.08)'" onmouseout="this.style.boxShadow=''">
            @if($robot->image)
                <img src="{{ $robot->image }}" alt="{{ $robot->name }}" style="width:100%;height:160px;object-fit:contain;margin-bottom:12px;border-radius:8px;background:#f8fafc">
            @else
                <div style="width:100%;height:160px;background:#f1f5f9;border-radius:8px;display:flex;align-items:center;justify-content:center;font-size:3rem;margin-bottom:12px">🤖</div>
            @endif
            <div style="font-weight:700;margin-bottom:2px">{{ $robot->name }}</div>
            <div style="font-size:.85rem;color:#64748b">{{ $robot->manufacturer?->name ?? '' }} {{ $robot->model_number ? '· '.$robot->model_number : '' }}</div>
            <div style="margin-top:8px;display:flex;gap:6px;flex-wrap:wrap">
                @if($robot->type)
                    <span style="padding:2px 8px;background:#eff6ff;color:#2563eb;border-radius:4px;font-size:.7rem">{{ $robot->type->name }}</span>
                @endif
                @if($robot->ros2_support)
                    <span style="padding:2px 8px;background:#f0fdf4;color:#16a34a;border-radius:4px;font-size:.7rem">ROS 2</span>
                @endif
                @if($robot->indoor_outdoor)
                    <span style="padding:2px 8px;background:#fef3c7;color:#92400e;border-radius:4px;font-size:.7rem">{{ ucfirst($robot->indoor_outdoor) }}</span>
                @endif
            </div>
            @if($robot->global_price)
                <div style="margin-top:12px;font-weight:600;color:#059669">{{ $robot->currency }} {{ number_format($robot->global_price, 2) }}</div>
            @endif
        </a>
        @empty
        <div style="grid-column:1/-1;text-align:center;padding:48px;color:#64748b">
            <p style="font-size:1.1rem">No robots found matching your criteria.</p>
            <a href="{{ url($localePrefix ?? '/en') }}/ai/robots" style="color:#3b82f6;text-decoration:none">Clear filters</a>
        </div>
        @endforelse
    </div>

    <div style="margin-top:32px;text-align:center">
        {{ $robots->withQueryString()->links() }}
    </div>
</div>
@endsection
