@extends('admin.layout')
@section('title', 'Add Subscriber')
@section('crumb', 'Email / Subscribers / Create')

@section('content')
<div class="page-head">
    <div>
        <h2>Add Subscriber</h2>
        <p>Add a new subscriber to your email list.</p>
    </div>
</div>

<div class="card">
    <form method="POST" action="/email/subscribers">
        @csrf
        <div class="card-body" style="padding:16px">
            <div class="form-grid">
                <div class="field">
                    <label>Email *</label>
                    <input class="control" name="email" type="email" value="{{ old('email') }}" required>
                </div>
                <div class="field">
                    <label>First Name</label>
                    <input class="control" name="first_name" value="{{ old('first_name') }}">
                </div>
                <div class="field">
                    <label>Last Name</label>
                    <input class="control" name="last_name" value="{{ old('last_name') }}">
                </div>
                <div class="field">
                    <label>Phone</label>
                    <input class="control" name="phone" value="{{ old('phone') }}">
                </div>
                <div class="field">
                    <label>Company</label>
                    <input class="control" name="company" value="{{ old('company') }}">
                </div>
                <div class="field">
                    <label>Country (2-letter code)</label>
                    <input class="control" name="country" value="{{ old('country') }}" maxlength="2">
                </div>
                <div class="field">
                    <label>Status</label>
                    <select class="control" name="status">
                        <option value="active" {{ old('status') === 'active' ? 'selected' : '' }}>Active</option>
                        <option value="unsubscribed" {{ old('status') === 'unsubscribed' ? 'selected' : '' }}>Unsubscribed</option>
                    </select>
                </div>
                <div class="field">
                    <label>Source</label>
                    <input class="control" name="source" value="{{ old('source', 'manual') }}">
                </div>
            </div>

            @if($groups->count())
            <div class="field" style="margin-top:16px">
                <label>Assign to Groups</label>
                <div style="display:flex;gap:8px;flex-wrap:wrap">
                    @foreach($groups as $g)
                        <label style="display:flex;align-items:center;gap:4px;padding:6px 10px;border:1px solid var(--line);border-radius:6px;cursor:pointer;font-size:.88rem">
                            <input type="checkbox" name="groups[]" value="{{ $g->id }}" {{ in_array($g->id, old('groups', [])) ? 'checked' : '' }}>
                            {{ $g->name }}
                        </label>
                    @endforeach
                </div>
            </div>
            @endif
        </div>
        <div style="padding:16px;border-top:1px solid var(--line);display:flex;gap:8px;justify-content:flex-end">
            <a href="/email/subscribers" class="btn btn-ghost">Cancel</a>
            <button type="submit" class="btn btn-primary">Create Subscriber</button>
        </div>
    </form>
</div>
@endsection
