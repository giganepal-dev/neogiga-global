@extends('admin.layout')
@section('title', 'Create Group')
@section('crumb', 'Email / Groups / Create')

@section('content')
<div class="page-head">
    <div>
        <h2>Create Group</h2>
        <p>Organize subscribers into a group.</p>
    </div>
</div>

<div class="card">
    <form method="POST" action="/email/groups">
        @csrf
        <div class="card-body" style="padding:16px">
            <div class="form-grid">
                <div class="field">
                    <label>Name *</label>
                    <input class="control" name="name" value="{{ old('name') }}" required>
                </div>
                <div class="field">
                    <label>Type</label>
                    <input class="control" name="group_type" value="{{ old('group_type', 'manual') }}">
                </div>
                <div class="field">
                    <label>Country Code</label>
                    <input class="control" name="country_code" value="{{ old('country_code') }}" maxlength="2">
                </div>
                <div class="field">
                    <label>Max Emails/Day</label>
                    <input class="control" name="max_emails_per_day" type="number" value="{{ old('max_emails_per_day') }}">
                </div>
                <div class="field">
                    <label>Max Emails/Month</label>
                    <input class="control" name="max_emails_per_month" type="number" value="{{ old('max_emails_per_month') }}">
                </div>
            </div>
            <div class="field" style="margin-top:16px">
                <label>Description</label>
                <textarea class="control" name="description" rows="3">{{ old('description') }}</textarea>
            </div>
        </div>
        <div style="padding:16px;border-top:1px solid var(--line);display:flex;gap:8px;justify-content:flex-end">
            <a href="/email/groups" class="btn btn-ghost">Cancel</a>
            <button type="submit" class="btn btn-primary">Create Group</button>
        </div>
    </form>
</div>
@endsection
