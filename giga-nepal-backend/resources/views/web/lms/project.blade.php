<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="robots" content="index, follow">
    <title>{{ $project->title }} · NeoGiga Learning</title>
    <meta name="description" content="{{ \Illuminate\Support\Str::limit($project->summary ?? $project->description ?? 'NeoGiga learning project tutorial.', 155) }}">
    <style>
        body{margin:0;background:#f8fafc;color:#0f172a;font-family:ui-sans-serif,system-ui,-apple-system,"Segoe UI",Roboto,Arial,sans-serif;line-height:1.55}
        .wrap{max-width:980px;margin:0 auto;padding:32px 20px}
        a{color:#0369a1;text-decoration:none} a:hover{text-decoration:underline}
        h1{font-size:clamp(2rem,4vw,3.2rem);line-height:1.05;margin:10px 0;letter-spacing:0}
        h2{font-size:1.15rem;margin-top:28px}
        .sub{color:#64748b;max-width:760px}
        .panel{background:#fff;border:1px solid #e2e8f0;border-radius:8px;padding:16px;margin-top:14px}
        table{width:100%;border-collapse:collapse} th,td{text-align:left;border-bottom:1px solid #e2e8f0;padding:9px 8px} th{color:#64748b;font-size:.75rem;text-transform:uppercase}
        pre{white-space:pre-wrap;background:#0f172a;color:#e2e8f0;border-radius:8px;padding:14px;overflow:auto}
    </style>
</head>
<body>
<main class="wrap">
    <a href="/learn">Learning</a>
    <h1>{{ $project->title }}</h1>
    <p class="sub">{{ $project->summary ?? $project->description }}</p>
    <p class="sub">{{ ucfirst($project->difficulty_level ?? 'beginner') }} · {{ (int) ($project->estimated_minutes ?? 0) }} minutes</p>

    <section>
        <h2>Lessons</h2>
        <div class="panel">
            @forelse($project->lessons as $lesson)
                <p><strong>{{ $lesson->title }}</strong><br><span class="sub">{{ $lesson->summary }}</span></p>
            @empty
                <p class="sub">Lessons are being prepared.</p>
            @endforelse
        </div>
    </section>

    <section>
        <h2>Components</h2>
        <div class="panel">
            <table><thead><tr><th>Component</th><th>Qty</th><th>Required</th></tr></thead><tbody>
            @forelse($components as $component)
                <tr><td>{{ $component->product_name ?? $component->name }}</td><td>{{ $component->quantity }} {{ $component->unit }}</td><td>{{ $component->is_required ? 'Yes' : 'No' }}</td></tr>
            @empty
                <tr><td colspan="3" class="sub">No components listed.</td></tr>
            @endforelse
            </tbody></table>
        </div>
    </section>

    <section>
        <h2>Code Samples</h2>
        @forelse($codeSamples as $sample)
            <div class="panel">
                <strong>{{ $sample->title }}</strong>
                <pre>{{ $sample->code }}</pre>
                <p class="sub">{{ $sample->explanation }}</p>
            </div>
        @empty
            <p class="sub">No code samples listed.</p>
        @endforelse
    </section>
</main>
</body>
</html>
