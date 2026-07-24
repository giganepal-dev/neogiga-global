@extends('admin.layout')
@section('title', 'Edit Group')
@section('crumb', 'Email / Groups / Edit')

@section('content')
<div class="page-head">
    <div>
        <h2>Edit Group</h2>
        <p>{{ $row->name }}</p>
    </div>
</div>

<div class="card">
    <form method="POST" action="/email/groups/{{ $row->id }}">
        @csrf @method('PUT')
        <div class="card-body" style="padding:16px">
            <div class="form-grid">
                <div class="field">
                    <label>Name *</label>
                    <input class="control" name="name" value="{{ old('name', $row->name) }}" required>
                </div>
                <div class="field">
                    <label>Type</label>
                    <input class="control" name="group_type" value="{{ old('group_type', $row->group_type) }}">
                </div>
                <div class="field">
                    <label>Country Code</label>
                    <input class="control" name="country_code" value="{{ old('country_code', $row->country_code) }}" maxlength="2">
                </div>
                <div class="field">
                    <label>Max Emails/Day</label>
                    <input class="control" name="max_emails_per_day" type="number" value="{{ old('max_emails_per_day', $row->max_emails_per_day) }}">
                </div>
                <div class="field">
                    <label>Max Emails/Month</label>
                    <input class="control" name="max_emails_per_month" type="number" value="{{ old('max_emails_per_month', $row->max_emails_per_month) }}">
                </div>
            </div>
            <div class="field" style="margin-top:16px">
                <label>Description</label>
                <textarea class="control" name="description" rows="3">{{ old('description', $row->description) }}</textarea>
            </div>
        </div>
        <div style="padding:16px;border-top:1px solid var(--line);display:flex;gap:8px;justify-content:flex-end">
            <a href="/email/groups/{{ $row->id }}" class="btn btn-ghost">Cancel</a>
            <button type="submit" class="btn btn-primary">Update Group</button>
        </div>
    </form>
</div>
@endsection
