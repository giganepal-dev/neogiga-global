@extends('admin.layout')
@section('title', 'AI Commerce Settings')
@section('crumb', 'AI Commerce / Settings')

@section('content')
<div class="page-head">
    <div>
        <h2>AI Commerce Settings</h2>
        <p>Configure AI model, API credentials, and generation parameters.</p>
    </div>
    <div class="page-actions">
        <a href="/admin/ai-commerce" class="btn btn-ghost">Back to Dashboard</a>
    </div>
</div>

<div class="card">
    <div class="card-h"><h2>AI Configuration</h2></div>
    <div class="card-body">
        <div style="display:grid;gap:20px;max-width:600px">
            <div>
                <div class="sub">API Key</div>
                <div style="display:flex;align-items:center;gap:8px">
                    @if($config['ai_api_key_set'])
                        <span class="badge b-ok">Configured</span>
                        <span style="color:var(--muted);font-size:.82rem">Key is set in .env (NEOAI_API_KEY)</span>
                    @else
                        <span class="badge b-danger">Not Set</span>
                        <span style="color:var(--muted);font-size:.82rem">Set NEOAI_API_KEY in .env to enable AI features</span>
                    @endif
                </div>
            </div>

            <div>
                <div class="sub">AI Model</div>
                <div style="font-weight:600;font-family:monospace">{{ $config['ai_model'] }}</div>
            </div>

            <div>
                <div class="sub">API URL</div>
                <div style="font-family:monospace;font-size:.88rem;word-break:break-all">{{ $config['ai_api_url'] }}</div>
            </div>

            <div>
                <div class="sub">Max Tokens</div>
                <div style="font-weight:600">{{ number_format($config['max_tokens']) }}</div>
            </div>

            <div>
                <div class="sub">Temperature</div>
                <div style="font-weight:600">{{ $config['temperature'] }}</div>
            </div>
        </div>
    </div>
</div>

<div class="card" style="margin-top:16px">
    <div class="card-h"><h2>System Architecture</h2></div>
    <div class="card-body">
        <div style="display:grid;gap:16px;max-width:600px">
            <div style="padding:12px;background:var(--bg);border-radius:8px">
                <div style="font-weight:600;margin-bottom:4px">Database AI Tools</div>
                <div style="color:var(--muted);font-size:.88rem">
                    All price, stock, and product facts come from the database via <code>DatabaseAiTools</code>.
                    No AI model generates product data — it only interprets and routes queries.
                </div>
            </div>
            <div style="padding:12px;background:var(--bg);border-radius:8px">
                <div style="font-weight:600;margin-bottom:4px">Conversational Orchestrator</div>
                <div style="color:var(--muted);font-size:.88rem">
                    LLM routing, guardrails, audit logging, and human handoff.
                    Requires <code>NEOAI_API_KEY</code> to be configured.
                </div>
            </div>
            <div style="padding:12px;background:var(--bg);border-radius:8px">
                <div style="font-weight:600;margin-bottom:4px">Contexts</div>
                <div style="color:var(--muted);font-size:.88rem">
                    <code>general</code> — General AI assistant
                    <br><code>bom</code> — Bill of Materials builder
                    <br><code>pos</code> — POS invoice generation
                    <br><code>lms</code> — Learning recommendations
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
