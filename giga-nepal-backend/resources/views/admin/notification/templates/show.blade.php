@extends('admin.layout')
@section('title', 'Template: ' . ucwords(str_replace('-', ' ', $template)))
@section('crumb', 'Notifications / Templates / ' . ucwords(str_replace('-', ' ', $template)))

@section('content')
<div class="page-head">
    <div>
        <h2>{{ ucwords(str_replace('-', ' ', $template)) }} Template</h2>
        <p style="color:var(--muted)">{{ $template }}.blade.php &middot; {{ number_format(strlen($content) / 1024, 1) }}KB</p>
    </div>
    <div class="page-actions" style="display:flex;gap:8px">
        <a href="/admin/notification/templates/{{ $template }}/edit" class="btn btn-primary">Edit Template</a>
        <a href="/admin/notification/templates/{{ $template }}/preview" class="btn btn-ghost">Preview</a>
    </div>
</div>

@if(session('status'))
    <div class="note" style="background:#dcfce7;border-color:#86efac;color:#166534">{{ session('status') }}</div>
@endif

<div style="display:grid;grid-template-columns:1fr 320px;gap:16px;align-items:start">
    <div class="card">
        <div class="card-h"><h2>Template Source</h2></div>
        <div class="card-body">
            <pre style="background:var(--bg);padding:16px;border-radius:6px;overflow-x:auto;font-size:12px;line-height:1.6;max-height:600px;overflow-y:auto;white-space:pre-wrap">{{ $content }}</pre>
        </div>
    </div>

    <div>
        <div class="card">
            <div class="card-h"><h2>Events Using This Template</h2></div>
            <div class="card-body">
                @if(count($events) > 0)
                    <div style="display:flex;gap:4px;flex-wrap:wrap">
                        @foreach($events as $ev)
                            <span class="badge b-muted">{{ $ev }}</span>
                        @endforeach
                    </div>
                @else
                    <p style="color:var(--muted);font-size:.88rem">No events mapped to this template.</p>
                @endif
            </div>
        </div>

        <div class="card" style="margin-top:16px">
            <div class="card-h"><h2>Template Variables</h2></div>
            <div class="card-body">
                @php
                    preg_match_all('/\{\{\s*\$([a-zA-Z_]+)\s*\}\}/', $content, $matches);
                    $vars = array_unique($matches[1] ?? []);
                @endphp
                @if(count($vars) > 0)
                    <div style="display:flex;gap:4px;flex-wrap:wrap">
                        @foreach($vars as $v)
                            <code style="padding:3px 6px;background:var(--bg);border:1px solid var(--border);border-radius:3px;font-size:.78rem">${{ $v }}</code>
                        @endforeach
                    </div>
                @else
                    <p style="color:var(--muted);font-size:.88rem">No variables detected.</p>
                @endif
            </div>
        </div>

        <div class="card" style="margin-top:16px">
            <div class="card-h"><h2>File Info</h2></div>
            <div class="card-body">
                <div style="display:grid;gap:8px;font-size:.88rem">
                    <div>
                        <span style="color:var(--muted)">Path:</span>
                        <span class="mono" style="font-size:.78rem">{{ $filePath }}</span>
                    </div>
                    <div>
                        <span style="color:var(--muted)">Size:</span>
                        {{ number_format(strlen($content)) }} bytes
                    </div>
                    <div>
                        <span style="color:var(--muted)">Lines:</span>
                        {{ count(explode("\n", $content)) }}
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
