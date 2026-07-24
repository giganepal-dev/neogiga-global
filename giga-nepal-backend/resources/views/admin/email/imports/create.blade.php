@extends('admin.layout')
@section('title', 'New Import')
@section('crumb', 'Email / Imports / Create')

@section('content')
<div class="page-head">
    <div>
        <h2>Import Subscribers</h2>
        <p>Upload a CSV file to import subscribers.</p>
    </div>
</div>

<div class="card">
    <form method="POST" action="/email/imports/upload" enctype="multipart/form-data">
        @csrf
        <div class="card-body" style="padding:16px">
            <div class="field">
                <label>CSV File *</label>
                <div class="dropzone">
                    <input type="file" name="csv_file" accept=".csv,.txt" required style="width:100%">
                    <p style="margin-top:8px;color:var(--muted);font-size:.88rem">Upload a CSV file with headers. Max 10MB.</p>
                </div>
            </div>
            <div class="field" style="margin-top:16px">
                <label>Add to Group (optional)</label>
                <select class="control" name="group_id">
                    <option value="">No group</option>
                    @foreach($groups as $g)
                        <option value="{{ $g->id }}">{{ $g->name }}</option>
                    @endforeach
                </select>
            </div>
        </div>
        <div style="padding:16px;border-top:1px solid var(--line);display:flex;gap:8px;justify-content:flex-end">
            <a href="/email/imports" class="btn btn-ghost">Cancel</a>
            <button type="submit" class="btn btn-primary">Upload &amp; Preview</button>
        </div>
    </form>
</div>
@endsection
