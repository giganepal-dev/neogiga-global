@extends('frontend.layout')
@section('title', $robot->name . ' — NeoGiga AI & Robotics')
@section('meta_description', $robot->short_description ?? Str::limit(strip_tags($robot->description), 160))

@section('content')
<div style="max-width:1200px;margin:0 auto;padding:24px">
    <div style="font-size:.85rem;color:#64748b;margin-bottom:16px">
        <a href="{{ url($localePrefix ?? '/en') }}/ai" style="color:#3b82f6;text-decoration:none">AI & Robotics</a> /
        <a href="{{ url($localePrefix ?? '/en') }}/ai/robots" style="color:#3b82f6;text-decoration:none">Robots</a> /
        {{ $robot->name }}
    </div>

    <div style="display:grid;grid-template-columns:1fr 1fr;gap:32px;align-items:start">
        {{-- Image --}}
        <div>
            @if($robot->image)
                <img src="{{ $robot->image }}" alt="{{ $robot->name }}" style="width:100%;border-radius:12px;background:#f8fafc">
            @else
                <div style="width:100%;height:300px;background:#f1f5f9;border-radius:12px;display:flex;align-items:center;justify-content:center;font-size:5rem">🤖</div>
            @endif
            @if($robot->videos && count($robot->videos))
            <div style="margin-top:12px;display:flex;gap:8px;flex-wrap:wrap">
                @foreach($robot->videos as $video)
                    <a href="{{ $video }}" target="_blank" style="padding:8px 16px;background:#1e293b;color:#fff;border-radius:6px;text-decoration:none;font-size:.85rem">▶ Video</a>
                @endforeach
            </div>
            @endif
        </div>

        {{-- Info --}}
        <div>
            <div style="display:flex;gap:8px;margin-bottom:8px;flex-wrap:wrap">
                @if($robot->type)
                    <span style="padding:4px 10px;background:#eff6ff;color:#2563eb;border-radius:6px;font-size:.8rem">{{ $robot->type->name }}</span>
                @endif
                @if($robot->ros2_support)
                    <span style="padding:4px 10px;background:#f0fdf4;color:#16a34a;border-radius:6px;font-size:.8rem">ROS 2</span>
                @endif
                @if($robot->is_featured)
                    <span style="padding:4px 10px;background:#fef3c7;color:#92400e;border-radius:6px;font-size:.8rem">Featured</span>
                @endif
            </div>
            <h1 style="font-size:1.8rem;font-weight:800;margin-bottom:4px">{{ $robot->name }}</h1>
            <p style="color:#64748b;margin-bottom:16px">{{ $robot->manufacturer?->name ?? '' }} {{ $robot->model_number ? '· '.$robot->model_number : '' }}</p>

            @if($robot->global_price)
                <div style="font-size:1.5rem;font-weight:700;color:#059669;margin-bottom:16px">{{ $robot->currency }} {{ number_format($robot->global_price, 2) }}</div>
            @endif

            @if($robot->short_description || $robot->description)
                <p style="color:#475569;line-height:1.6;margin-bottom:20px">{{ $robot->short_description ?? Str::limit(strip_tags($robot->description), 300) }}</p>
            @endif

            <div style="display:flex;gap:8px;flex-wrap:wrap;margin-bottom:20px">
                <a href="{{ url($localePrefix ?? '/en') }}/rfq?robot={{ $robot->slug }}" style="padding:12px 24px;background:#3b82f6;color:#fff;border-radius:8px;text-decoration:none;font-weight:600">Request Quotation</a>
                <a href="{{ url($localePrefix ?? '/en') }}/ai/lab" style="padding:12px 24px;background:#10b981;color:#fff;border-radius:8px;text-decoration:none;font-weight:600">Request Demo</a>
                @if($robot->documentation_url)
                    <a href="{{ $robot->documentation_url }}" target="_blank" style="padding:12px 24px;background:#f1f5f9;color:#475569;border-radius:8px;text-decoration:none;font-weight:600">Documentation</a>
                @endif
            </div>

            {{-- Specs --}}
            <div style="background:#f8fafc;border:1px solid #e2e8f0;border-radius:12px;padding:20px">
                <h3 style="font-weight:700;margin-bottom:12px">Specifications</h3>
                <table style="width:100%;font-size:.9rem">
                    @php
                    $specs = [
                        'Robot Type' => $robot->type?->name,
                        'Payload' => $robot->payload_kg ? $robot->payload_kg.' kg' : null,
                        'Reach' => $robot->reach_mm ? $robot->reach_mm.' mm' : null,
                        'Degrees of Freedom' => $robot->degrees_of_freedom,
                        'Dimensions' => ($robot->length_mm && $robot->width_mm && $robot->height_mm) ? $robot->length_mm.'×'.$robot->width_mm.'×'.$robot->height_mm.' mm' : null,
                        'Weight' => $robot->weight_kg ? $robot->weight_kg.' kg' : null,
                        'Speed' => $robot->speed_mps ? $robot->speed_mps.' m/s' : null,
                        'Battery' => $robot->battery_type,
                        'Runtime' => $robot->battery_runtime_min ? $robot->battery_runtime_min.' min' : null,
                        'Compute' => $robot->compute_platform,
                        'AI Accelerator' => $robot->ai_accelerator,
                        'OS' => $robot->operating_system,
                        'ROS 2' => $robot->ros2_support ? 'Yes' : null,
                        'Indoor/Outdoor' => ucfirst($robot->indoor_outdoor ?? ''),
                        'IP Rating' => $robot->ip_rating,
                        'Camera' => $robot->camera_system,
                        'LiDAR' => $robot->lidar,
                        'Radar' => $robot->radar,
                    ];
                    @endphp
                    @foreach($specs as $label => $value)
                        @if($value)
                        <tr style="border-bottom:1px solid #e2e8f0">
                            <td style="padding:8px 0;color:#64748b;width:40%">{{ $label }}</td>
                            <td style="padding:8px 0;font-weight:500">{{ $value }}</td>
                        </tr>
                        @endif
                    @endforeach
                </table>
            </div>
        </div>
    </div>

    {{-- Applications --}}
    @if($robot->applications->count())
    <div style="margin-top:32px">
        <h2 style="font-size:1.2rem;font-weight:700;margin-bottom:12px">Applications</h2>
        <div style="display:flex;gap:8px;flex-wrap:wrap">
            @foreach($robot->applications as $app)
                <span style="padding:6px 14px;background:#eff6ff;color:#2563eb;border-radius:20px;font-size:.85rem">{{ $app->name }}</span>
            @endforeach
        </div>
    </div>
    @endif

    {{-- Sensors --}}
    @if($robot->sensors && count($robot->sensors))
    <div style="margin-top:32px">
        <h2 style="font-size:1.2rem;font-weight:700;margin-bottom:12px">Sensors</h2>
        <div style="display:flex;gap:8px;flex-wrap:wrap">
            @foreach($robot->sensors as $sensor)
                <span style="padding:6px 14px;background:#f0fdf4;color:#16a34a;border-radius:20px;font-size:.85rem">{{ $sensor }}</span>
            @endforeach
        </div>
    </div>
    @endif

    {{-- Programming Languages --}}
    @if($robot->programming_languages && count($robot->programming_languages))
    <div style="margin-top:32px">
        <h2 style="font-size:1.2rem;font-weight:700;margin-bottom:12px">Programming Languages</h2>
        <div style="display:flex;gap:8px;flex-wrap:wrap">
            @foreach($robot->programming_languages as $lang)
                <span style="padding:6px 14px;background:#fef3c7;color:#92400e;border-radius:20px;font-size:.85rem">{{ $lang }}</span>
            @endforeach
        </div>
    </div>
    @endif

    {{-- Related Robots --}}
    @if($relatedRobots->count())
    <div style="margin-top:48px">
        <h2 style="font-size:1.2rem;font-weight:700;margin-bottom:16px">Related Robots</h2>
        <div style="display:grid;grid-template-columns:repeat(auto-fill,minmax(250px,1fr));gap:16px">
            @foreach($relatedRobots as $r)
            <a href="{{ url($localePrefix ?? '/en') }}/ai/robots/{{ $r->slug }}" style="display:block;background:#f8fafc;border:1px solid #e2e8f0;border-radius:12px;padding:16px;text-decoration:none;color:inherit">
                <div style="font-weight:700">{{ $r->name }}</div>
                <div style="font-size:.85rem;color:#64748b">{{ $r->manufacturer?->name ?? '' }}</div>
                @if($r->global_price)
                    <div style="margin-top:8px;font-weight:600;color:#059669">{{ $r->currency }} {{ number_format($r->global_price, 2) }}</div>
                @endif
            </a>
            @endforeach
        </div>
    </div>
    @endif
</div>
@endsection
