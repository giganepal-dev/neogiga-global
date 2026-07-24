@extends('admin.layout')
@section('title', 'Add Sender')
@section('crumb', 'Email / Senders / Create')

@section('content')
<div class="page-head">
    <div>
        <h2>Add Sender Identity</h2>
        <p>Create a new email sender profile.</p>
    </div>
</div>

<div class="card">
    <form method="POST" action="/email/senders">
        @csrf
        <div class="card-body" style="padding:16px">
            <div class="form-grid">
                <div class="field">
                    <label>Sender Name *</label>
                    <input class="control" name="sender_name" value="{{ old('sender_name') }}" required>
                </div>
                <div class="field">
                    <label>Sender Email *</label>
                    <input class="control" name="sender_email" type="email" value="{{ old('sender_email') }}" required>
                </div>
                <div class="field">
                    <label>Reply-To Email</label>
                    <input class="control" name="reply_to" type="email" value="{{ old('reply_to') }}">
                </div>
                <div class="field" style="display:flex;align-items:flex-end">
                    <label style="display:flex;align-items:center;gap:6px;cursor:pointer">
                        <input type="checkbox" name="is_default" value="1" {{ old('is_default') ? 'checked' : '' }}>
                        Set as default sender
                    </label>
                </div>
            </div>
        </div>
        <div style="padding:16px;border-top:1px solid var(--line);display:flex;gap:8px;justify-content:flex-end">
            <a href="/email/senders" class="btn btn-ghost">Cancel</a>
            <button type="submit" class="btn btn-primary">Create Sender</button>
        </div>
    </form>
</div>
@endsection
