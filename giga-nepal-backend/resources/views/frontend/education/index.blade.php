@extends('frontend.layout')

@section('title', $title ?? 'STEM Education Projects - NeoGiga')
@section('description', $description ?? 'Build IoT, robotics, and electronics projects with complete BOMs, code, and courses. From beginner to advanced.')

@push('head')
<style nonce="{{ $csp_nonce ?? '' }}">
.edu-hero{padding:56px 0 32px;background:linear-gradient(135deg,#e9f1fd,#f8fafd 55%,#fff);border-bottom:1px solid var(--line)}
.edu-hero h1{font-size:clamp(2rem,5vw,3.2rem);font-weight:800;line-height:1.08;letter-spacing:-.02em;margin:12px 0 14px}
.edu-hero p{color:var(--muted);font-size:1.05rem;max-width:68ch}
.edu-search{display:flex;gap:10px;margin-top:20px;max-width:600px}
.edu-search input{flex:1;border:1px solid #c7d4e6;border-radius:10px;padding:12px 16px;font-size:.95rem;min-height:46px}
.edu-search button{background:var(--cyan);color:#fff;border:0;border-radius:10px;padding:0 22px;font-weight:700;min-height:46px;cursor:pointer}
.edu-filters{display:flex;gap:10px;flex-wrap:wrap;margin:24px 0 16px}
.edu-filters select,.edu-filters button{border:1px solid #c7d4e6;border-radius:8px;padding:8px 14px;background:#fff;font-size:.88rem;color:var(--on);cursor:pointer}
.edu-filters select:focus,.edu-filters button:focus{border-color:var(--cyan);outline:none}
.edu-filters button.active{background:var(--cyan);color:#fff;border-color:var(--cyan)}
.edu-grid{display:grid;grid-template-columns:repeat(auto-fill,minmax(300px,1fr));gap:20px;margin:20px 0}
.edu-card{background:#fff;border:1px solid var(--line);border-radius:14px;overflow:hidden;transition:box-shadow .2s,transform .2s;cursor:pointer}
.edu-card:hover{box-shadow:0 12px 40px rgba(23,43,77,.12);transform:translateY(-2px)}
.edu-card-img{height:180px;background:linear-gradient(135deg,#e9f1fd,#f0f4f8);display:grid;place-items:center;position:relative;overflow:hidden}
.edu-card-img .placeholder{font-size:3rem;opacity:.6}
.edu-card-badge{position:absolute;top:10px;left:10px;display:flex;gap:6px}
.edu-card-badge span{font-size:.68rem;font-weight:700;padding:3px 8px;border-radius:999px;text-transform:uppercase;letter-spacing:.04em}
.badge-level{background:#dbeafe;color:#1d4ed8}
.badge-controller{background:#dcfce7;color:#166534}
.badge-featured{background:#fef3c7;color:#92400e}
.edu-card-body{padding:16px}
.edu-card-body h3{font-size:1.05rem;font-weight:700;margin:0 0 6px;line-height:1.3}
.edu-card-body .desc{color:var(--muted);font-size:.85rem;line-height:1.45;margin:0 0 12px;display:-webkit-box;-webkit-line-clamp:2;-webkit-box-orient:vertical;overflow:hidden}
.edu-card-meta{display:flex;gap:14px;flex-wrap:wrap;font-size:.78rem;color:var(--faint)}
.edu-card-meta span{display:inline-flex;align-items:center;gap:4px}
.edu-card-footer{display:flex;justify-content:space-between;align-items:center;padding:12px 16px;border-top:1px solid var(--line);background:var(--s2)}
.edu-card-footer .cost{font-weight:700;color:var(--cyan);font-size:.95rem}
.edu-card-footer .duration{color:var(--muted);font-size:.82rem}
.edu-section{margin:40px 0}
.edu-section h2{font-size:1.5rem;font-weight:800;margin:0 0 20px}
.cat-grid{display:grid;grid-template-columns:repeat(auto-fill,minmax(180px,1fr));gap:12px}
.cat-card{background:#fff;border:1px solid var(--line);border-radius:10px;padding:16px;text-align:center;cursor:pointer;transition:all .15s}
.cat-card:hover{border-color:var(--cyan);box-shadow:0 4px 16px rgba(15,98,230,.1)}
.cat-card .icon{font-size:1.8rem;margin-bottom:6px}
.cat-card .name{font-weight:700;font-size:.9rem}
.cat-card .count{color:var(--faint);font-size:.78rem}
.pagination{display:flex;justify-content:center;gap:6px;margin:30px 0}
.pagination a,.pagination span{min-width:36px;height:36px;display:grid;place-items:center;border-radius:8px;font-size:.88rem;border:1px solid var(--line);background:#fff;color:var(--on);transition:.15s}
.pagination a:hover{border-color:var(--cyan);color:var(--cyan)}
.pagination .active{background:var(--cyan);color:#fff;border-color:var(--cyan)}
@media(max-width:640px){.edu-grid{grid-template-columns:1fr}.edu-search{flex-direction:column}}
</style>
@endpush

@section('content')
<section class="edu-hero">
    <div class="wrap">
        <p class="eyebrow">STEM Education</p>
        <h1>Build Real Projects, Learn by Doing</h1>
        <p>Complete IoT, robotics, and electronics projects with BOMs, code, wiring guides, and connected courses. From beginner to expert level.</p>
        <form class="edu-search" action="{{ route('education.index') }}" method="GET">
            <input type="text" name="q" value="{{ request('q') }}" placeholder="Search projects: 4WD robot, weather station, IoT...">
            <button type="submit">Search</button>
        </form>
    </div>
</section>

<section class="wrap">
    <div class="edu-filters">
        <select name="category" onchange="this.form.submit()">
            <option value="">All Categories</option>
            @foreach($categories as $cat)
                <option value="{{ $cat['category'] }}" {{ request('category') === $cat['category'] ? 'selected' : '' }}>
                    {{ ucfirst($cat['category']) }} ({{ $cat['project_count'] }})
                </option>
            @endforeach
        </select>
        <select name="skill_level" onchange="this.form.submit()">
            <option value="">All Levels</option>
            <option value="beginner" {{ request('skill_level') === 'beginner' ? 'selected' : '' }}>Beginner</option>
            <option value="intermediate" {{ request('skill_level') === 'intermediate' ? 'selected' : '' }}>Intermediate</option>
            <option value="advanced" {{ request('skill_level') === 'advanced' ? 'selected' : '' }}>Advanced</option>
        </select>
        <select name="controller" onchange="this.form.submit()">
            <option value="">All Controllers</option>
            @foreach(['Arduino', 'ESP32', 'Raspberry Pi', 'STM32', 'Micro:bit', 'RP2040', 'ESP8266'] as $ctrl)
                <option value="{{ $ctrl }}" {{ request('controller') === $ctrl ? 'selected' : '' }}>{{ $ctrl }}</option>
            @endforeach
        </select>
    </div>

    @if(request('q') || request('category') || request('skill_level') || request('controller'))
        <p class="muted" style="margin-bottom:16px">
            {{ $projects->total() }} project{{ $projects->total() !== 1 ? 's' : '' }} found
            @if(request('q')) for "{{ request('q') }}" @endif
        </p>
    @endif

    <div class="edu-grid">
        @forelse($projects as $project)
            <a href="{{ route('education.show', $project->slug) }}" class="edu-card">
                <div class="edu-card-img">
                    @if($project->project_images && count($project->project_images))
                        <img src="{{ $project->project_images[0] }}" alt="{{ $project->title }}" loading="lazy">
                    @else
                        <span class="placeholder">🔧</span>
                    @endif
                    <div class="edu-card-badge">
                        <span class="badge-level">{{ $project->skill_level }}</span>
                        @if($project->main_controller)
                            <span class="badge-controller">{{ $project->main_controller }}</span>
                        @endif
                        @if($project->is_featured)
                            <span class="badge-featured">Featured</span>
                        @endif
                    </div>
                </div>
                <div class="edu-card-body">
                    <h3>{{ $project->title }}</h3>
                    <p class="desc">{{ $project->summary }}</p>
                    <div class="edu-card-meta">
                        <span>📂 {{ ucfirst($project->category) }}</span>
                        <span>⏱ {{ $project->duration_label }}</span>
                        <span>👁 {{ number_format($project->view_count) }} views</span>
                        @if($project->rating_avg)
                            <span>⭐ {{ number_format($project->rating_avg, 1) }}</span>
                        @endif
                    </div>
                </div>
                <div class="edu-card-footer">
                    <span class="cost">{{ $project->currency }} {{ number_format($project->estimated_cost ?? 0, 2) }}</span>
                    <span class="duration">{{ $project->duration_label }}</span>
                </div>
            </a>
        @empty
            <div style="grid-column:1/-1;text-align:center;padding:60px 20px;color:var(--faint)">
                <p style="font-size:2rem;margin:0 0 12px">🔍</p>
                <p>No projects found matching your criteria.</p>
                <a href="{{ route('education.index') }}" class="btn btn-ghost" style="margin-top:12px">Browse All Projects</a>
            </div>
        @endforelse
    </div>

    {{ $projects->links() }}
</section>
@endsection
