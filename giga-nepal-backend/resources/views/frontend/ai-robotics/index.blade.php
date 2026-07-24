@extends('frontend.layout')

@section('title', 'NeoGiga AI & Robotics — Marketplace · Academy · Lab · Robot Explorer · Innovation Services')
@section('meta_description', 'Learn, Build, Deploy — Explore AI products, robot models, courses, labs, and innovation services on NeoGiga AI & Robotics.')

@section('content')
{{-- Hero Section --}}
<section style="background:linear-gradient(135deg,#0a0a1a 0%,#1a1a3e 50%,#0f2027 100%);padding:60px 24px;text-align:center;color:#fff">
    <div style="max-width:800px;margin:0 auto">
        <h1 style="font-size:2.5rem;font-weight:800;margin-bottom:8px">NeoGiga AI & Robotics</h1>
        <p style="font-size:1.2rem;color:#94a3b8;margin-bottom:24px">Learn. Build. Deploy.</p>
        <p style="color:#cbd5e1;margin-bottom:32px">Marketplace · Academy · Lab · Robot Explorer · Innovation Services</p>

        {{-- Search --}}
        <form action="{{ url($localePrefix ?? '/en') }}/ai/store" method="GET" style="max-width:600px;margin:0 auto 32px">
            <div style="display:flex;gap:8px">
                <input type="text" name="q" placeholder="Search AI products, robot models, courses, projects..." style="flex:1;padding:14px 18px;border-radius:8px;border:1px solid #334155;background:#1e293b;color:#fff;font-size:15px">
                <button type="submit" style="padding:14px 28px;background:#3b82f6;color:#fff;border:none;border-radius:8px;font-weight:600;cursor:pointer">Search</button>
            </div>
        </form>

        {{-- Primary CTAs --}}
        <div style="display:flex;gap:12px;justify-content:center;flex-wrap:wrap">
            <a href="{{ url($localePrefix ?? '/en') }}/ai/robots" style="padding:12px 24px;background:#3b82f6;color:#fff;border-radius:8px;text-decoration:none;font-weight:600">Explore Robots</a>
            <a href="{{ url($localePrefix ?? '/en') }}/ai/store" style="padding:12px 24px;background:#10b981;color:#fff;border-radius:8px;text-decoration:none;font-weight:600">Shop AI Hardware</a>
            <a href="{{ url($localePrefix ?? '/en') }}/ai/learning" style="padding:12px 24px;background:#8b5cf6;color:#fff;border-radius:8px;text-decoration:none;font-weight:600">Start Learning</a>
            <a href="{{ url($localePrefix ?? '/en') }}/ai/lab" style="padding:12px 24px;background:#f59e0b;color:#fff;border-radius:8px;text-decoration:none;font-weight:600">Build a Robotics Lab</a>
            <a href="{{ url($localePrefix ?? '/en') }}/ai/lab" style="padding:12px 24px;background:#ef4444;color:#fff;border-radius:8px;text-decoration:none;font-weight:600">Request a Demo</a>
        </div>
    </div>
</section>

{{-- Featured Ecosystem Cards --}}
<section style="max-width:1200px;margin:0 auto;padding:48px 24px">
    <h2 style="text-align:center;font-size:1.5rem;font-weight:700;margin-bottom:32px">Explore the Ecosystem</h2>
    <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(180px,1fr));gap:16px">
        @php $prefix = url($localePrefix ?? '/en'); @endphp
        @foreach([
            ['url' => '/ai/store', 'icon' => '🤖', 'title' => 'AI Marketplace', 'desc' => 'AI hardware, accelerators, sensors'],
            ['url' => '/ai/robots', 'icon' => '🦾', 'title' => 'Robot Explorer', 'desc' => 'Compare and discover robots'],
            ['url' => '/ai/learning', 'icon' => '🎓', 'title' => 'AI & Robotics Academy', 'desc' => 'Courses, paths, certificates'],
            ['url' => '/ai/lab', 'icon' => '🔬', 'title' => 'Virtual Lab', 'desc' => 'Simulate, test, learn online'],
            ['url' => '/ai/institutional', 'icon' => '🏛️', 'title' => 'Institutional Lab Solutions', 'desc' => 'School & university packages'],
            ['url' => '/ai/ai-models', 'icon' => '🧠', 'title' => 'AI Model Library', 'desc' => 'Models, compatibility, guides'],
        ] as $card)
        <a href="{{ $prefix }}{{ $card['url'] }}" style="display:block;padding:24px;background:#f8fafc;border:1px solid #e2e8f0;border-radius:12px;text-decoration:none;color:inherit;text-align:center;transition:transform .15s,box-shadow .15s" onmouseover="this.style.transform='translateY(-2px)';this.style.boxShadow='0 4px 12px rgba(0,0,0,.1)'" onmouseout="this.style.transform='';this.style.boxShadow=''">
            <div style="font-size:2rem;margin-bottom:8px">{{ $card['icon'] }}</div>
            <div style="font-weight:700;margin-bottom:4px">{{ $card['title'] }}</div>
            <div style="font-size:.85rem;color:#64748b">{{ $card['desc'] }}</div>
        </a>
        @endforeach
    </div>
</section>

{{-- Featured Robot Models --}}
@if($featuredRobots->count())
<section style="background:#f1f5f9;padding:48px 24px">
    <div style="max-width:1200px;margin:0 auto">
        <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:24px">
            <h2 style="font-size:1.5rem;font-weight:700">Featured Robot Models</h2>
            <a href="{{ $prefix }}/ai/robots" style="color:#3b82f6;text-decoration:none;font-weight:600">View All →</a>
        </div>
        <div style="display:grid;grid-template-columns:repeat(auto-fill,minmax(300px,1fr));gap:16px">
            @foreach($featuredRobots as $robot)
            <a href="{{ $prefix }}/ai/robots/{{ $robot->slug }}" style="display:block;background:#fff;border:1px solid #e2e8f0;border-radius:12px;padding:20px;text-decoration:none;color:inherit">
                @if($robot->image)
                    <img src="{{ $robot->image }}" alt="{{ $robot->name }}" style="width:100%;height:160px;object-fit:contain;margin-bottom:12px;border-radius:8px;background:#f8fafc">
                @else
                    <div style="width:100%;height:160px;background:#f1f5f9;border-radius:8px;display:flex;align-items:center;justify-content:center;font-size:3rem;margin-bottom:12px">🤖</div>
                @endif
                <div style="font-weight:700;margin-bottom:4px">{{ $robot->name }}</div>
                <div style="font-size:.85rem;color:#64748b">{{ $robot->manufacturer?->name ?? 'Unknown' }}</div>
                @if($robot->type)
                    <span style="display:inline-block;margin-top:8px;padding:2px 8px;background:#eff6ff;color:#2563eb;border-radius:4px;font-size:.75rem">{{ $robot->type->name }}</span>
                @endif
                @if($robot->global_price)
                    <div style="margin-top:8px;font-weight:600;color:#059669">{{ $robot->currency }} {{ number_format($robot->global_price, 2) }}</div>
                @endif
            </a>
            @endforeach
        </div>
    </div>
</section>
@endif

{{-- Robot Types --}}
@if($robotTypes->count())
<section style="max-width:1200px;margin:0 auto;padding:48px 24px">
    <h2 style="font-size:1.5rem;font-weight:700;margin-bottom:24px">Browse by Robot Type</h2>
    <div style="display:grid;grid-template-columns:repeat(auto-fill,minmax(200px,1fr));gap:12px">
        @foreach($robotTypes as $type)
        <a href="{{ $prefix }}/ai/robots?type={{ $type->id }}" style="display:flex;align-items:center;gap:12px;padding:16px;background:#f8fafc;border:1px solid #e2e8f0;border-radius:8px;text-decoration:none;color:inherit">
            <span style="font-size:1.5rem">{{ $type->icon ?? '🤖' }}</span>
            <div>
                <div style="font-weight:600">{{ $type->name }}</div>
                <div style="font-size:.8rem;color:#64748b">{{ $type->robot_models_count }} models</div>
            </div>
        </a>
        @endforeach
    </div>
</section>
@endif

{{-- Featured Courses --}}
@if($featuredCourses->count())
<section style="background:#f1f5f9;padding:48px 24px">
    <div style="max-width:1200px;margin:0 auto">
        <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:24px">
            <h2 style="font-size:1.5rem;font-weight:700">Featured Courses</h2>
            <a href="{{ $prefix }}/ai/learning" style="color:#3b82f6;text-decoration:none;font-weight:600">View All →</a>
        </div>
        <div style="display:grid;grid-template-columns:repeat(auto-fill,minmax(260px,1fr));gap:16px">
            @foreach($featuredCourses as $course)
            <div style="background:#fff;border:1px solid #e2e8f0;border-radius:12px;padding:20px">
                <div style="font-weight:700;margin-bottom:4px">{{ $course->title ?? $course->name }}</div>
                <div style="font-size:.85rem;color:#64748b">{{ $course->description ?? '' }}</div>
                <div style="margin-top:12px;display:flex;gap:8px">
                    <span style="padding:2px 8px;background:#eff6ff;color:#2563eb;border-radius:4px;font-size:.75rem">{{ ucfirst($course->level ?? 'beginner') }}</span>
                    @if($course->estimated_hours)
                        <span style="padding:2px 8px;background:#f0fdf4;color:#16a34a;border-radius:4px;font-size:.75rem">{{ $course->estimated_hours }}h</span>
                    @endif
                </div>
            </div>
            @endforeach
        </div>
    </div>
</section>
@endif

{{-- Learning Paths --}}
@if($featuredLearningPaths->count())
<section style="max-width:1200px;margin:0 auto;padding:48px 24px">
    <h2 style="font-size:1.5rem;font-weight:700;margin-bottom:24px">Learning Paths</h2>
    <div style="display:grid;grid-template-columns:repeat(auto-fill,minmax(300px,1fr));gap:16px">
        @foreach($featuredLearningPaths as $path)
        <a href="{{ $prefix }}/ai/learning/{{ $path->slug }}" style="display:block;background:#fff;border:1px solid #e2e8f0;border-radius:12px;padding:24px;text-decoration:none;color:inherit">
            <div style="display:flex;justify-content:space-between;align-items:start">
                <div>
                    <div style="font-weight:700;margin-bottom:4px">{{ $path->name }}</div>
                    <div style="font-size:.85rem;color:#64748b">{{ $path->description }}</div>
                </div>
                <span style="padding:4px 10px;background:{{ $path->level==='beginner'?'#dcfce7':($path->level==='intermediate'?'#fef3c7':'#fee2e2') }};color:{{ $path->level==='beginner'?'#166534':($path->level==='intermediate'?'#92400e':'#991b1b') }};border-radius:6px;font-size:.75rem;font-weight:600;text-transform:capitalize">{{ $path->level }}</span>
            </div>
            <div style="margin-top:12px;font-size:.85rem;color:#64748b">{{ $path->courses_count ?? 0 }} courses · {{ $path->estimated_hours ?? '—' }}h estimated</div>
        </a>
        @endforeach
    </div>
</section>
@endif

{{-- Institutional Packages --}}
@if($featuredPackages->count())
<section style="background:#f1f5f9;padding:48px 24px">
    <div style="max-width:1200px;margin:0 auto">
        <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:24px">
            <h2 style="font-size:1.5rem;font-weight:700">Institutional Lab Packages</h2>
            <a href="{{ $prefix }}/ai/institutional" style="color:#3b82f6;text-decoration:none;font-weight:600">View All →</a>
        </div>
        <div style="display:grid;grid-template-columns:repeat(auto-fill,minmax(300px,1fr));gap:16px">
            @foreach($featuredPackages as $pkg)
            <div style="background:#fff;border:1px solid #e2e8f0;border-radius:12px;padding:24px">
                <div style="font-weight:700;margin-bottom:4px">{{ $pkg->name }}</div>
                <div style="font-size:.85rem;color:#64748b;margin-bottom:8px">{{ $pkg->short_description ?? $pkg->description }}</div>
                @if($pkg->target_institution)
                    <span style="padding:2px 8px;background:#f0fdf4;color:#16a34a;border-radius:4px;font-size:.75rem">{{ ucfirst($pkg->target_institution) }}</span>
                @endif
                @if($pkg->base_price)
                    <div style="margin-top:12px;font-weight:600;color:#059669">From {{ $pkg->currency }} {{ number_format($pkg->base_price, 2) }}</div>
                @endif
            </div>
            @endforeach
        </div>
    </div>
</section>
@endif

{{-- Manufacturers --}}
@if($featuredManufacturers->count())
<section style="max-width:1200px;margin:0 auto;padding:48px 24px">
    <h2 style="font-size:1.5rem;font-weight:700;margin-bottom:24px">AI & Robotics Manufacturers</h2>
    <div style="display:grid;grid-template-columns:repeat(auto-fill,minmax(200px,1fr));gap:12px">
        @foreach($featuredManufacturers as $mfr)
        <a href="{{ $prefix }}/ai/manufacturers/{{ $mfr->slug }}" style="display:flex;align-items:center;gap:12px;padding:16px;background:#f8fafc;border:1px solid #e2e8f0;border-radius:8px;text-decoration:none;color:inherit">
            @if($mfr->logo)
                <img src="{{ $mfr->logo }}" alt="{{ $mfr->name }}" style="width:40px;height:40px;object-fit:contain;border-radius:4px">
            @else
                <div style="width:40px;height:40px;background:#e2e8f0;border-radius:4px;display:flex;align-items:center;justify-content:center;font-size:1.2rem">🏭</div>
            @endif
            <div>
                <div style="font-weight:600;font-size:.9rem">{{ $mfr->name }}</div>
                <div style="font-size:.75rem;color:#64748b">{{ $mfr->country ?? 'Global' }}</div>
            </div>
        </a>
        @endforeach
    </div>
</section>
@endif

{{-- Events --}}
@if($upcomingEvents->count())
<section style="background:#f1f5f9;padding:48px 24px">
    <div style="max-width:1200px;margin:0 auto">
        <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:24px">
            <h2 style="font-size:1.5rem;font-weight:700">Upcoming Events</h2>
            <a href="{{ $prefix }}/ai/events" style="color:#3b82f6;text-decoration:none;font-weight:600">View All →</a>
        </div>
        <div style="display:grid;grid-template-columns:repeat(auto-fill,minmax(280px,1fr));gap:16px">
            @foreach($upcomingEvents as $event)
            <div style="background:#fff;border:1px solid #e2e8f0;border-radius:12px;padding:20px">
                <div style="display:flex;justify-content:space-between;align-items:start;margin-bottom:8px">
                    <div style="font-weight:700">{{ $event->name }}</div>
                    <span style="padding:2px 8px;background:#eff6ff;color:#2563eb;border-radius:4px;font-size:.7rem;text-transform:uppercase">{{ $event->event_type }}</span>
                </div>
                <div style="font-size:.85rem;color:#64748b">{{ $event->starts_at->format('M d, Y') }}</div>
                @if($event->location)
                    <div style="font-size:.85rem;color:#64748b;margin-top:4px">📍 {{ $event->location }}</div>
                @endif
            </div>
            @endforeach
        </div>
    </div>
</section>
@endif

{{-- Latest News --}}
@if($recentArticles->count())
<section style="max-width:1200px;margin:0 auto;padding:48px 24px">
    <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:24px">
        <h2 style="font-size:1.5rem;font-weight:700">Latest News & Releases</h2>
        <a href="{{ $prefix }}/ai/news" style="color:#3b82f6;text-decoration:none;font-weight:600">View All →</a>
    </div>
    <div style="display:grid;grid-template-columns:repeat(auto-fill,minmax(280px,1fr));gap:16px">
        @foreach($recentArticles as $article)
        <a href="{{ $prefix }}/ai/news/{{ $article->slug }}" style="display:block;background:#f8fafc;border:1px solid #e2e8f0;border-radius:12px;padding:20px;text-decoration:none;color:inherit">
            <span style="padding:2px 8px;background:#eff6ff;color:#2563eb;border-radius:4px;font-size:.7rem;text-transform:uppercase">{{ str_replace('_',' ',$article->article_type) }}</span>
            <div style="font-weight:700;margin-top:8px">{{ $article->title }}</div>
            <div style="font-size:.85rem;color:#64748b;margin-top:4px">{{ Str::limit($article->excerpt, 100) }}</div>
            <div style="font-size:.75rem;color:#94a3b8;margin-top:8px">{{ $article->published_at?->format('M d, Y') }}</div>
        </a>
        @endforeach
    </div>
</section>
@endif

{{-- Partner Onboarding CTA --}}
<section style="background:linear-gradient(135deg,#1e3a5f,#0f172a);padding:48px 24px;text-align:center;color:#fff">
    <div style="max-width:800px;margin:0 auto">
        <h2 style="font-size:1.5rem;font-weight:700;margin-bottom:12px">Partner With NeoGiga AI & Robotics</h2>
        <p style="color:#94a3b8;margin-bottom:24px">Join as a manufacturer, seller, integrator, instructor, or research partner</p>
        <div style="display:flex;gap:12px;justify-content:center;flex-wrap:wrap">
            <a href="{{ url($localePrefix ?? '/en') }}/sell-on-neogiga" style="padding:12px 24px;background:#3b82f6;color:#fff;border-radius:8px;text-decoration:none;font-weight:600">Become a Seller</a>
            <a href="{{ url($localePrefix ?? '/en') }}/distributors" style="padding:12px 24px;background:#10b981;color:#fff;border-radius:8px;text-decoration:none;font-weight:600">Become a Distributor</a>
            <a href="{{ url($localePrefix ?? '/en') }}/rfq" style="padding:12px 24px;background:#8b5cf6;color:#fff;border-radius:8px;text-decoration:none;font-weight:600">Request a Quotation</a>
        </div>
    </div>
</section>
@endsection
