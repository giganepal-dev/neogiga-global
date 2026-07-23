@extends('frontend.layout')

@section('title', 'Sensor Knowledge Base - NeoGiga STEM')
@section('description', 'Complete reference for electronic sensors: specifications, wiring, code examples, and compatible products.')

@push('head')
<style nonce="{{ $csp_nonce ?? '' }}">
.sensor-hero{padding:44px 0 24px;background:linear-gradient(135deg,#e9f1fd,#f8fafd);border-bottom:1px solid var(--line)}
.sensor-hero h1{font-size:clamp(1.6rem,4vw,2.4rem);font-weight:800;margin:8px 0 12px}
.sensor-grid{display:grid;grid-template-columns:repeat(auto-fill,minmax(280px,1fr));gap:16px;margin:24px 0}
.sensor-card{background:#fff;border:1px solid var(--line);border-radius:12px;padding:20px;transition:box-shadow .15s}
.sensor-card:hover{box-shadow:0 8px 24px rgba(23,43,77,.08)}
.sensor-card h3{font-size:1rem;font-weight:700;margin:0 0 8px}
.sensor-card .type{font-size:.76rem;color:var(--faint);text-transform:uppercase;letter-spacing:.06em;margin-bottom:10px}
.sensor-specs{display:grid;grid-template-columns:1fr 1fr;gap:6px;font-size:.82rem}
.sensor-specs dt{color:var(--muted)}
.sensor-specs dd{font-weight:600;margin:0}
.sensor-card .apps{margin-top:10px;font-size:.78rem;color:var(--muted)}
.sensor-card .apps strong{color:var(--on)}
</style>
@endpush

@section('content')
<section class="sensor-hero">
    <div class="wrap">
        <p class="eyebrow">Sensor Intelligence</p>
        <h1>Sensor Knowledge Base</h1>
        <p class="lead">Complete reference for electronic sensors used in IoT, robotics, and STEM projects.</p>
    </div>
</section>

<section class="wrap">
    <div class="sensor-grid">
        @forelse($sensors as $sensor)
            <div class="sensor-card">
                <h3>{{ $sensor->display_name }}</h3>
                <div class="type">{{ $sensor->sensor_type }}</div>
                <dl class="sensor-specs">
                    @if($sensor->interface)
                        <dt>Interface</dt><dd>{{ $sensor->interface }}</dd>
                    @endif
                    @if($sensor->voltage_range)
                        <dt>Voltage</dt><dd>{{ $sensor->voltage_range }}</dd>
                    @endif
                    @if($sensor->range)
                        <dt>Range</dt><dd>{{ $sensor->range }}</dd>
                    @endif
                    @if($sensor->accuracy)
                        <dt>Accuracy</dt><dd>{{ $sensor->accuracy }}</dd>
                    @endif
                    @if($sensor->resolution)
                        <dt>Resolution</dt><dd>{{ $sensor->resolution }}</dd>
                    @endif
                    @if($sensor->input_output_type)
                        <dt>I/O Type</dt><dd>{{ $sensor->input_output_type }}</dd>
                    @endif
                </dl>
                @if($sensor->applications && count($sensor->applications))
                    <div class="apps"><strong>Applications:</strong> {{ implode(', ', array_slice($sensor->applications, 0, 4)) }}</div>
                @endif
            </div>
        @empty
            <div style="grid-column:1/-1;text-align:center;padding:60px;color:var(--faint)">
                <p style="font-size:2rem;margin:0 0 12px">📡</p>
                <p>Sensor knowledge base is being populated. Check back soon.</p>
            </div>
        @endforelse
    </div>
</section>
@endsection
