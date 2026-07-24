@extends('admin.layout')
@section('title', 'Import Campaign Contacts')
@section('crumb', 'Marketing / Campaign Contacts / Import')

@section('content')
<div class="page-head">
    <div>
        <h2>Import Campaign Contacts</h2>
        <p>Upload CSV, XLS, XLSX, or XML files to import campaign contacts.</p>
    </div>
    <div class="page-actions">
        <a href="/admin/marketing/campaign-contacts" class="btn btn-ghost">Back to List</a>
    </div>
</div>

@if(session('status'))
    <div class="note" style="background:#dcfce7;border-color:#86efac;color:#166534">{{ session('status') }}</div>
@endif

@if(isset($preview))
    {{-- Show preview and import form --}}
    <div class="card">
        <div class="card-h"><h2>Preview: {{ $file_name }}</h2></div>
        <div class="card-body">
            @if(isset($preview['error']))
                <div class="note" style="background:#fee2e2;border-color:#fca5a5;color:#991b1b">
                    {{ $preview['error'] }}
                </div>
            @else
                <p style="color:var(--muted);margin-bottom:16px">
                    @if(isset($preview['total_nodes']))
                        Found {{ $preview['total_nodes'] }} contacts in XML.
                    @elseif(isset($preview['format']))
                        File format: {{ strtoupper($preview['format']) }}
                    @endif
                </p>

                @if(isset($preview['available_fields']))
                <div style="margin-bottom:16px">
                    <strong>Available Fields:</strong>
                    <div style="display:flex;gap:6px;flex-wrap:wrap;margin-top:6px">
                        @foreach($preview['available_fields'] as $field)
                            <span class="badge b-muted">{{ $field }}</span>
                        @endforeach
                    </div>
                </div>
                @endif

                @if(isset($preview['sample_data']) && count($preview['sample_data']) > 0)
                <div style="margin-bottom:16px">
                    <strong>Sample Data:</strong>
                    <table class="tbl" style="margin-top:8px">
                        <thead><tr><th>Field</th><th>Value</th></tr></thead>
                        <tbody>
                            @foreach($preview['sample_data'] as $field => $value)
                            <tr>
                                <td class="mono">{{ $field }}</td>
                                <td>{{ $value }}</td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
                @endif

                <form method="POST" action="/admin/marketing/campaign-contacts" style="margin-top:20px">
                    @csrf
                    <input type="hidden" name="preview_token" value="{{ $preview['token'] ?? '' }}">

                    <div class="form-grid">
                        <div class="field">
                            <label>Country Group</label>
                            <select class="control" name="country_group_id">
                                <option value="">None</option>
                                @foreach(DB::table('email_groups')->where('type', 'country')->orderBy('name')->get() as $group)
                                    <option value="{{ $group->id }}">{{ $group->name }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="field">
                            <label>Source</label>
                            <input class="control" name="source" value="{{ $file_name }}" placeholder="Import source">
                        </div>
                    </div>

                    <div class="field">
                        <label>Custom Groups</label>
                        <div style="display:flex;gap:8px;flex-wrap:wrap">
                            @foreach(DB::table('email_groups')->where('type', 'custom')->orderBy('name')->get() as $group)
                            <label style="display:flex;align-items:center;gap:4px;font-size:.88rem">
                                <input type="checkbox" name="group_ids[]" value="{{ $group->id }}">
                                {{ $group->name }}
                            </label>
                            @endforeach
                        </div>
                    </div>

                    <button type="submit" class="btn btn-primary" style="margin-top:16px">Import Contacts</button>
                </form>
            @endif
        </div>
    </div>
@else
    {{-- Show upload form --}}
    <div class="card">
        <div class="card-h"><h2>Upload File</h2></div>
        <div class="card-body">
            <form method="POST" action="/admin/marketing/campaign-contacts/preview" enctype="multipart/form-data">
                @csrf
                <div class="field">
                    <label>Select File (CSV, XLS, XLSX, or XML)</label>
                    <input type="file" name="file" accept=".csv,.xls,.xlsx,.xml" class="control" required>
                </div>
                <button type="submit" class="btn btn-primary" style="margin-top:12px">Preview Import</button>
            </form>
        </div>
    </div>

    <div class="card" style="margin-top:16px">
        <div class="card-h"><h2>Supported Formats</h2></div>
        <div class="card-body">
            <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(200px,1fr));gap:12px">
                <div style="padding:12px;background:var(--bg);border-radius:8px">
                    <strong>CSV</strong>
                    <p style="color:var(--muted);font-size:.82rem;margin:4px 0 0">Comma-separated values. Auto-detects delimiter.</p>
                </div>
                <div style="padding:12px;background:var(--bg);border-radius:8px">
                    <strong>XLS/XLSX</strong>
                    <p style="color:var(--muted);font-size:.82rem;margin:4px 0 0">Excel spreadsheets. Select sheet if multiple.</p>
                </div>
                <div style="padding:12px;background:var(--bg);border-radius:8px">
                    <strong>XML</strong>
                    <p style="color:var(--muted);font-size:.82rem;margin:4px 0 0">Structured XML with contact records.</p>
                </div>
            </div>
        </div>
    </div>
@endif
@endsection
