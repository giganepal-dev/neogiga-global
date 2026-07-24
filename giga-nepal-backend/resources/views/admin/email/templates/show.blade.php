@extends('admin.layout')
@section('title', $template->name)
@section('crumb', 'Email / Templates / ' . $template->name)

@section('content')
<div class="page-head">
    <div>
        <h2>{{ $template->name }}</h2>
        <p style="color:var(--muted)">
            Event: <code>{{ $template->event_key }}</code>
            &nbsp;&middot;&nbsp; Type: {{ $template->type ?? '—' }}
            &nbsp;&middot;&nbsp; Version: v{{ $template->version }}
            &nbsp;&middot;&nbsp;
            @if($template->is_active)
                <span class="badge b-ok">active</span>
            @else
                <span class="badge b-muted">draft</span>
            @endif
        </p>
    </div>
    <div class="page-actions" style="display:flex;gap:8px">
        <a href="/email/templates/{{ $template->id }}/edit" class="btn btn-primary">Edit</a>
        <form method="POST" action="/email/templates/{{ $template->id }}/duplicate" style="display:inline">
            @csrf
            <button class="btn btn-ghost">Duplicate</button>
        </form>
        <form method="POST" action="/email/templates/{{ $template->id }}" style="display:inline" onsubmit="return confirm('Delete this template?')">
            @csrf
            @method('DELETE')
            <button class="btn btn-ghost" style="color:var(--danger)">Delete</button>
        </form>
    </div>
</div>

@if(session('status'))
    <div class="note" style="background:#dcfce7;border-color:#86efac;color:#166534">{{ session('status') }}</div>
@endif

<div style="display:grid;grid-template-columns:1fr 1fr;gap:16px;align-items:start">
    {{-- Rendered preview --}}
    <div>
        <div class="card">
            <div class="card-h"><h2>Preview (Rendered)</h2></div>
            <div style="background:#ffffff;padding:0;border-radius:0 0 8px 8px">
                <div style="max-width:600px;margin:0 auto;border:1px solid #e0e0e0;overflow:hidden">
                    {!! $rendered !!}
                </div>
            </div>
        </div>
    </div>

    {{-- Details --}}
    <div>
        <div class="card">
            <div class="card-h"><h2>Template Info</h2></div>
            <div class="card-body">
                <div style="display:grid;gap:12px">
                    <div>
                        <div class="sub">Subject</div>
                        <div style="font-weight:600">{{ $template->subject }}</div>
                    </div>
                    <div>
                        <div class="sub">Description</div>
                        <div>{{ $template->description ?? '—' }}</div>
                    </div>
                    <div>
                        <div class="sub">Variables</div>
                        <div style="display:flex;gap:4px;flex-wrap:wrap">
                            @php $vars = json_decode($template->variables, true) ?? []; @endphp
                            @forelse($vars as $v)
                                <code style="padding:2px 6px;background:var(--bg);border:1px solid var(--border);border-radius:3px;font-size:.78rem">{{{{ $v }}}}</code>
                            @empty
                                <span style="color:var(--muted)">None detected</span>
                            @endforelse
                        </div>
                    </div>
                    <div>
                        <div class="sub">Created</div>
                        <div>{{ $template->created_at }}</div>
                    </div>
                    <div>
                        <div class="sub">Last Updated</div>
                        <div>{{ $template->updated_at }}</div>
                    </div>
                </div>
            </div>
        </div>

        @if($versions->count() > 1)
        <div class="card" style="margin-top:16px">
            <div class="card-h"><h2>Version History</h2></div>
            <div class="scroll-x">
                <table class="tbl">
                    <thead>
                        <tr>
                            <th>Version</th>
                            <th>Subject</th>
                            <th>Created</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($versions as $v)
                        <tr>
                            <td class="num">v{{ $v->version }}</td>
                            <td>{{ Str::limit($v->subject, 50) }}</td>
                            <td>{{ $v->created_at }}</td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
        @endif

        <div class="card" style="margin-top:16px">
            <div class="card-h"><h2>HTML Source</h2></div>
            <div class="card-body">
                <pre style="background:var(--bg);padding:12px;border-radius:6px;overflow-x:auto;font-size:12px;max-height:400px;overflow-y:auto">{{ $template->html_body }}</pre>
            </div>
        </div>
    </div>
</div>
@endsection
