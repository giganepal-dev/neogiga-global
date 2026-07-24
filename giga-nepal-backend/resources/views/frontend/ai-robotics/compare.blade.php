@extends('frontend.layout')
@section('title', 'Compare Robots — NeoGiga AI & Robotics')
@section('content')
<div style="max-width:1200px;margin:0 auto;padding:24px">
    <div style="font-size:.85rem;color:#64748b;margin-bottom:16px"><a href="{{ url($localePrefix ?? '/en') }}/ai" style="color:#3b82f6;text-decoration:none">AI & Robotics</a> / Compare</div>
    <h1 style="font-size:1.8rem;font-weight:800;margin-bottom:24px">Robot Comparison</h1>

    @if($robots->count() < 2)
    <div style="text-align:center;padding:48px;background:#f8fafc;border-radius:12px;color:#64748b">
        <p>Select at least 2 robots to compare.</p>
        <a href="{{ url($localePrefix ?? '/en') }}/ai/robots" style="color:#3b82f6;text-decoration:none;font-weight:600">Browse Robots →</a>
    </div>
    @else
    <div style="overflow-x:auto">
        <table style="width:100%;border-collapse:collapse;min-width:600px">
            <thead>
                <tr>
                    <th style="text-align:left;padding:12px;background:#f1f5f9;border:1px solid #e2e8f0;width:200px">Feature</th>
                    @foreach($robots as $robot)
                    <th style="text-align:center;padding:12px;background:#f1f5f9;border:1px solid #e2e8f0">
                        <a href="{{ url($localePrefix ?? '/en') }}/ai/robots/{{ $robot->slug }}" style="color:#3b82f6;text-decoration:none;font-weight:700">{{ $robot->name }}</a>
                        <div style="font-size:.8rem;color:#64748b;font-weight:400">{{ $robot->manufacturer?->name ?? '' }}</div>
                    </th>
                    @endforeach
                </tr>
            </thead>
            <tbody>
                @php
                $fields = [
                    'Model Number' => fn($r) => $r->model_number ?? '—',
                    'Type' => fn($r) => $r->type?->name ?? '—',
                    'Payload' => fn($r) => $r->payload_kg ? $r->payload_kg.' kg' : '—',
                    'Reach' => fn($r) => $r->reach_mm ? $r->reach_mm.' mm' : '—',
                    'Degrees of Freedom' => fn($r) => $r->degrees_of_freedom ?? '—',
                    'Weight' => fn($r) => $r->weight_kg ? $r->weight_kg.' kg' : '—',
                    'Speed' => fn($r) => $r->speed_mps ? $r->speed_mps.' m/s' : '—',
                    'Battery Runtime' => fn($r) => $r->battery_runtime_min ? $r->battery_runtime_min.' min' : '—',
                    'Compute Platform' => fn($r) => $r->compute_platform ?? '—',
                    'AI Accelerator' => fn($r) => $r->ai_accelerator ?? '—',
                    'ROS 2' => fn($r) => $r->ros2_support ? '✅' : '❌',
                    'Indoor/Outdoor' => fn($r) => ucfirst($r->indoor_outdoor ?? '—'),
                    'IP Rating' => fn($r) => $r->ip_rating ?? '—',
                    'Camera' => fn($r) => $r->camera_system ?? '—',
                    'LiDAR' => fn($r) => $r->lidar ?? '—',
                    'Price' => fn($r) => $r->global_price ? $r->currency.' '.number_format($r->global_price, 2) : '—',
                ];
                @endphp
                @foreach($fields as $label => $getter)
                <tr>
                    <td style="padding:10px 12px;border:1px solid #e2e8f0;font-weight:500;background:#f8fafc">{{ $label }}</td>
                    @foreach($robots as $robot)
                    <td style="padding:10px 12px;border:1px solid #e2e8f0;text-align:center">{{ $getter($robot) }}</td>
                    @endforeach
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>
    @endif
</div>
@endsection
