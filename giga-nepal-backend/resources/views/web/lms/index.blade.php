<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="robots" content="index, follow">
    <title>NeoGiga Learning Projects and Courses</title>
    <meta name="description" content="Learn electronics, IoT, robotics, and marketplace product projects with NeoGiga courses and tutorials.">
    <style>
        body{margin:0;background:#f8fafc;color:#0f172a;font-family:ui-sans-serif,system-ui,-apple-system,"Segoe UI",Roboto,Arial,sans-serif;line-height:1.55}
        .wrap{max-width:1120px;margin:0 auto;padding:32px 20px}
        header{padding:28px 0 20px;border-bottom:1px solid #e2e8f0;margin-bottom:24px}
        a{color:#0369a1;text-decoration:none} a:hover{text-decoration:underline}
        h1{font-size:clamp(2rem,4vw,3.2rem);line-height:1.05;margin:0 0 10px;letter-spacing:0}
        h2{font-size:1.15rem;margin:0}
        .sub{color:#64748b;max-width:720px}
        .grid{display:grid;grid-template-columns:repeat(auto-fit,minmax(260px,1fr));gap:14px;margin:18px 0 32px}
        .card{background:#fff;border:1px solid #e2e8f0;border-radius:8px;padding:16px;box-shadow:0 1px 2px rgba(2,6,23,.04)}
        .badge{display:inline-flex;background:#e0f2fe;color:#075985;border-radius:999px;padding:2px 8px;font-size:.75rem;font-weight:700}
        .meta{color:#64748b;font-size:.88rem;margin-top:8px}
    </style>
</head>
<body>
<main class="wrap">
    <header>
        <a href="/">NeoGiga</a>
        <h1>Learning Projects</h1>
        <p class="sub">Courses and hands-on project tutorials linked to electronics, IoT, robotics, and marketplace components.</p>
    </header>

    <section>
        <h2>Courses</h2>
        <div class="grid">
            @forelse($courses as $course)
                <article class="card">
                    <span class="badge">{{ $course->level ?? 'beginner' }}</span>
                    <h3>{{ $course->title ?? 'Untitled course' }}</h3>
                    <p class="sub">{{ $course->subtitle ?? $course->description ?? 'NeoGiga learning course.' }}</p>
                    <div class="meta">{{ (int) ($course->estimated_minutes ?? 0) }} minutes</div>
                </article>
            @empty
                <p class="sub">Courses are being prepared.</p>
            @endforelse
        </div>
    </section>

    <section>
        <h2>Projects</h2>
        <div class="grid">
            @forelse($projects as $project)
                <article class="card">
                    <span class="badge">{{ $project->difficulty_level ?? 'beginner' }}</span>
                    <h3><a href="/learn/projects/{{ $project->slug }}">{{ $project->title ?? 'Untitled project' }}</a></h3>
                    <p class="sub">{{ $project->summary ?? 'NeoGiga project tutorial.' }}</p>
                    <div class="meta">{{ (int) ($project->estimated_minutes ?? 0) }} minutes</div>
                </article>
            @empty
                <p class="sub">Projects are being prepared.</p>
            @endforelse
        </div>
    </section>
</main>
</body>
</html>
