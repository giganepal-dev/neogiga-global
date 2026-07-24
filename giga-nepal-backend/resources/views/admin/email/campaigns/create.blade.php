@extends('admin.layout')
@section('title', 'Create Campaign')
@section('crumb', 'Email / Campaigns / Create')

@section('content')
<div class="page-head">
    <div>
        <h2>Create Campaign</h2>
        <p>Set up a new email campaign.</p>
    </div>
</div>

<div class="card">
    <form method="POST" action="/email/campaigns">
        @csrf
        <div class="card-body" style="padding:16px">
            <div class="form-grid">
                <div class="field">
                    <label>Campaign Name *</label>
                    <input class="control" name="name" value="{{ old('name') }}" required>
                </div>
                <div class="field">
                    <label>Subject Line *</label>
                    <input class="control" name="subject" value="{{ old('subject') }}" required>
                </div>
                <div class="field">
                    <label>Template</label>
                    <select class="control" name="template_id">
                        <option value="">No template</option>
                        @foreach($templates as $t)
                            <option value="{{ $t->id }}" {{ old('template_id') == $t->id ? 'selected' : '' }}>{{ $t->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="field">
                    <label>Sender</label>
                    <select class="control" name="sender_id">
                        <option value="">Default sender</option>
                        @foreach($senders as $s)
                            <option value="{{ $s->id }}" {{ old('sender_id') == $s->id ? 'selected' : '' }}>{{ $s->sender_name }} &lt;{{ $s->sender_email }}&gt;</option>
                        @endforeach
                    </select>
                </div>
            </div>

            <div class="form-grid" style="margin-top:16px">
                <div class="field">
                    <label>Recipient Groups</label>
                    <div style="display:flex;gap:8px;flex-wrap:wrap">
                        @foreach($groups as $g)
                            <label style="display:flex;align-items:center;gap:4px;padding:6px 10px;border:1px solid var(--line);border-radius:6px;cursor:pointer;font-size:.88rem">
                                <input type="checkbox" name="group_ids[]" value="{{ $g->id }}" {{ in_array($g->id, old('group_ids', [])) ? 'checked' : '' }}>
                                {{ $g->name }}
                            </label>
                        @endforeach
                    </div>
                </div>
                <div class="field">
                    <label>Segment</label>
                    <select class="control" name="segment_id">
                        <option value="">No segment</option>
                        @foreach($segments as $seg)
                            <option value="{{ $seg->id }}" {{ old('segment_id') == $seg->id ? 'selected' : '' }}>{{ $seg->name }} ({{ $seg->subscriber_count ?? 0 }})</option>
                        @endforeach
                    </select>
                </div>
            </div>

            <div class="form-grid" style="margin-top:16px">
                <div class="field">
                    <label>Schedule (leave blank for draft)</label>
                    <input class="control" name="scheduled_at" type="datetime-local" value="{{ old('scheduled_at') }}">
                </div>
                <div class="field">
                    <label>Preview Text</label>
                    <input class="control" name="preview_text" value="{{ old('preview_text') }}" maxlength="255">
                </div>
            </div>
        </div>
        <div style="padding:16px;border-top:1px solid var(--line);display:flex;gap:8px;justify-content:flex-end">
            <a href="/email/campaigns" class="btn btn-ghost">Cancel</a>
            <button type="submit" class="btn btn-primary">Create Campaign</button>
        </div>
    </form>
</div>
@endsection
