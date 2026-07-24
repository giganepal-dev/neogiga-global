@extends('admin.layout')
@section('title', 'Edit: ' . $template->name)
@section('crumb', 'Email / Templates / ' . $template->name . ' / Edit')

@section('content')
<div class="page-head">
    <div>
        <h2>Edit Template</h2>
        <p style="color:var(--muted)">Editing v{{ $template->version }} — saving creates a new version.</p>
    </div>
    <div class="page-actions">
        <a href="/email/templates/{{ $template->id }}" class="btn btn-ghost">Back to Template</a>
    </div>
</div>

@if(session('error'))
    <div class="note" style="background:#fee2e2;border-color:#fca5a5;color:#991b1b">{{ session('error') }}</div>
@endif

<form method="POST" action="/email/templates/{{ $template->id }}">
    @csrf
    @method('PUT')
    <div style="display:grid;grid-template-columns:1fr 320px;gap:16px;align-items:start">
        <div>
            <div class="card">
                <div class="card-h"><h2>Template Details</h2></div>
                <div class="card-body">
                    <div class="form-grid">
                        <div class="field">
                            <label>Template Name *</label>
                            <input class="control" name="name" value="{{ old('name', $template->name) }}" required>
                        </div>
                        <div class="field">
                            <label>Event Key *</label>
                            <input class="control" name="event_key" value="{{ old('event_key', $template->event_key) }}" required>
                        </div>
                        <div class="field">
                            <label>Type</label>
                            <select class="control" name="type">
                                @foreach(['marketing','transactional','notification','newsletter'] as $type)
                                    <option value="{{ $type }}" {{ ($template->type ?? '') === $type ? 'selected' : '' }}>{{ ucfirst($type) }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="field">
                            <label>Subject Line *</label>
                            <input class="control" name="subject" value="{{ old('subject', $template->subject) }}" required>
                        </div>
                    </div>
                    <div class="field">
                        <label>Description</label>
                        <input class="control" name="description" value="{{ old('description', $template->description) }}">
                    </div>
                    <div class="field">
                        <label style="display:flex;align-items:center;gap:8px">
                            <input type="checkbox" name="is_active" value="1" {{ $template->is_active ? 'checked' : '' }}>
                            Active (visible to campaigns)
                        </label>
                    </div>
                </div>
            </div>

            <div class="card" style="margin-top:16px">
                <div class="card-h"><h2>HTML Body *</h2></div>
                <div class="card-body">
                    <textarea class="control" name="body_html" rows="18" required style="font-family:monospace;font-size:13px;line-height:1.5">{{ old('body_html', $template->html_body) }}</textarea>
                </div>
            </div>

            <div class="card" style="margin-top:16px">
                <div class="card-h"><h2>Plain Text Body</h2></div>
                <div class="card-body">
                    <textarea class="control" name="body_text" rows="8" style="font-family:monospace;font-size:13px">{{ old('body_text', $template->body_text) }}</textarea>
                </div>
            </div>

            <div style="margin-top:16px;display:flex;gap:8px">
                <button type="submit" class="btn btn-primary">Save Changes</button>
                <a href="/email/templates/{{ $template->id }}" class="btn btn-ghost">Cancel</a>
            </div>
        </div>

        <div>
            <div class="card">
                <div class="card-h"><h2>Block Library</h2></div>
                <div class="card-body" style="padding:8px">
                    <p style="color:var(--muted);font-size:.82rem;margin:0 0 12px">Click to copy block HTML to clipboard.</p>
                    @foreach($blocks as $key => $block)
                    <div style="padding:8px;border:1px solid var(--border);border-radius:6px;margin-bottom:6px;cursor:pointer" onclick="copyBlock('{{ $key }}')">
                        <div style="display:flex;justify-content:space-between;align-items:center">
                            <strong style="font-size:.85rem">{{ $block['name'] }}</strong>
                            <span class="badge b-muted" style="font-size:.72rem">copy</span>
                        </div>
                        <p style="color:var(--muted);font-size:.76rem;margin:2px 0 0">{{ $block['description'] }}</p>
                    </div>
                    @endforeach
                </div>
            </div>
        </div>
    </div>
</form>

<script>
const blocks = @json($blocks);
function copyBlock(key) {
    navigator.clipboard.writeText(blocks[key].html).then(() => {
        const badge = event.currentTarget.querySelector('.badge');
        badge.textContent = 'copied!';
        badge.classList.add('b-ok');
        setTimeout(() => { badge.textContent = 'copy'; badge.classList.remove('b-ok'); }, 1500);
    });
}
</script>
@endsection
