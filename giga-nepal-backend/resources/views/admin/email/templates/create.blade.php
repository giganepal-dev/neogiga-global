@extends('admin.layout')
@section('title', 'Create Email Template')
@section('crumb', 'Email / Templates / New')

@section('content')
<div class="page-head">
    <div>
        <h2>Create Email Template</h2>
        <p>Build a reusable HTML email template with merge tags and block library.</p>
    </div>
    <div class="page-actions">
        <a href="/email/templates" class="btn btn-ghost">Back to List</a>
    </div>
</div>

@if(session('error'))
    <div class="note" style="background:#fee2e2;border-color:#fca5a5;color:#991b1b">{{ session('error') }}</div>
@endif

<form method="POST" action="/email/templates">
    @csrf
    <div style="display:grid;grid-template-columns:1fr 320px;gap:16px;align-items:start">
        {{-- Main form --}}
        <div>
            <div class="card">
                <div class="card-h"><h2>Template Details</h2></div>
                <div class="card-body">
                    <div class="form-grid">
                        <div class="field">
                            <label>Template Name *</label>
                            <input class="control" name="name" value="{{ old('name') }}" required placeholder="e.g. Welcome Email">
                        </div>
                        <div class="field">
                            <label>Event Key *</label>
                            <input class="control" name="event_key" value="{{ old('event_key') }}" required placeholder="e.g. user.registered">
                            <small style="color:var(--muted)">Internal identifier for triggering this template</small>
                        </div>
                        <div class="field">
                            <label>Type</label>
                            <select class="control" name="type">
                                <option value="marketing">Marketing</option>
                                <option value="transactional">Transactional</option>
                                <option value="notification">Notification</option>
                                <option value="newsletter">Newsletter</option>
                            </select>
                        </div>
                        <div class="field">
                            <label>Subject Line *</label>
                            <input class="control" name="subject" value="{{ old('subject') }}" required placeholder="e.g. Welcome to NeoGiga, {{first_name}}!">
                        </div>
                    </div>
                    <div class="field">
                        <label>Description</label>
                        <input class="control" name="description" value="{{ old('description') }}" placeholder="Brief description of when this template is used">
                    </div>
                </div>
            </div>

            <div class="card" style="margin-top:16px">
                <div class="card-h"><h2>HTML Body *</h2></div>
                <div class="card-body">
                    <textarea class="control" name="body_html" rows="18" required style="font-family:monospace;font-size:13px;line-height:1.5" placeholder="Paste your HTML email template here...">{{ old('body_html') }}</textarea>
                    <small style="color:var(--muted)">Use table-based HTML for email client compatibility. Inline CSS recommended.</small>
                </div>
            </div>

            <div class="card" style="margin-top:16px">
                <div class="card-h"><h2>Plain Text Body</h2></div>
                <div class="card-body">
                    <textarea class="control" name="body_text" rows="8" style="font-family:monospace;font-size:13px" placeholder="Optional plain text version for email clients that don't support HTML...">{{ old('body_text') }}</textarea>
                </div>
            </div>

            <div style="margin-top:16px;display:flex;gap:8px">
                <button type="submit" class="btn btn-primary">Create Template</button>
                <a href="/email/templates" class="btn btn-ghost">Cancel</a>
            </div>
        </div>

        {{-- Block library sidebar --}}
        <div>
            <div class="card">
                <div class="card-h"><h2>Block Library</h2></div>
                <div class="card-body" style="padding:8px">
                    <p style="color:var(--muted);font-size:.82rem;margin:0 0 12px">Click a block to copy its HTML to clipboard, then paste into the HTML body.</p>
                    @foreach($blocks as $key => $block)
                    <div style="padding:10px;border:1px solid var(--border);border-radius:6px;margin-bottom:8px;cursor:pointer;transition:border-color .15s" onclick="copyBlock('{{ $key }}')">
                        <div style="display:flex;justify-content:space-between;align-items:center">
                            <strong style="font-size:.88rem">{{ $block['name'] }}</strong>
                            <span class="badge b-muted" style="font-size:.72rem">click to copy</span>
                        </div>
                        <p style="color:var(--muted);font-size:.78rem;margin:4px 0 0">{{ $block['description'] }}</p>
                    </div>
                    @endforeach
                </div>
            </div>

            <div class="card" style="margin-top:16px">
                <div class="card-h"><h2>Quick Assembly</h2></div>
                <div class="card-body">
                    <p style="color:var(--muted);font-size:.82rem;margin:0 0 12px">Build a standard email by combining blocks in order:</p>
                    <button type="button" class="btn btn-ghost btn-sm" onclick="assembleStandard()" style="width:100%">Standard: Header + Hero + Text + CTA + Footer</button>
                </div>
            </div>
        </div>
    </div>
</form>

<script>
const blocks = @json($blocks);

function copyBlock(key) {
    const html = blocks[key].html;
    navigator.clipboard.writeText(html).then(() => {
        const el = event.currentTarget;
        const badge = el.querySelector('.badge');
        badge.textContent = 'copied!';
        badge.classList.add('b-ok');
        setTimeout(() => { badge.textContent = 'click to copy'; badge.classList.remove('b-ok'); }, 1500);
    });
}

function assembleStandard() {
    const order = ['header', 'hero', 'text_block', 'cta_button', 'footer'];
    const html = order.map(k => blocks[k].html).join('\n\n');
    document.querySelector('[name="body_html"]').value = html;
}
</script>
@endsection
