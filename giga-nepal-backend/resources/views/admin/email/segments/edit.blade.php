@extends('admin.layout')
@section('title', 'Edit Segment')
@section('crumb', 'Email / Segments / Edit')

@section('content')
<div class="page-head">
    <div>
        <h2>Edit Segment</h2>
        <p>{{ $row->name }}</p>
    </div>
</div>

<div class="card">
    <form method="POST" action="/email/segments/{{ $row->id }}">
        @csrf @method('PUT')
        <div class="card-body" style="padding:16px">
            <div class="form-grid">
                <div class="field">
                    <label>Name *</label>
                    <input class="control" name="name" value="{{ old('name', $row->name) }}" required>
                </div>
                <div class="field">
                    <label>Type</label>
                    <select class="control" name="segment_type">
                        <option value="dynamic" {{ old('segment_type', $row->segment_type) === 'dynamic' ? 'selected' : '' }}>Dynamic</option>
                        <option value="static" {{ old('segment_type', $row->segment_type) === 'static' ? 'selected' : '' }}>Static</option>
                    </select>
                </div>
            </div>
            <div class="field" style="margin-top:16px">
                <label>Description</label>
                <textarea class="control" name="description" rows="2">{{ old('description', $row->description) }}</textarea>
            </div>

            <div class="field" style="margin-top:16px">
                <label>Rules</label>
                <div id="rules-container">
                    @forelse($rules as $index => $r)
                    <div class="rule-row" style="display:flex;gap:8px;margin-bottom:8px;align-items:center">
                        <select name="rules[{{ $index }}][field]" class="control" style="width:180px">
                            @foreach($fields as $key => $label)
                                <option value="{{ $key }}" {{ $r->field === $key ? 'selected' : '' }}>{{ $label }}</option>
                            @endforeach
                        </select>
                        <select name="rules[{{ $index }}][operator]" class="control" style="width:160px">
                            @foreach($operators as $op)
                                <option value="{{ $op }}" {{ $r->operator === $op ? 'selected' : '' }}>{{ $op }}</option>
                            @endforeach
                        </select>
                        <input class="control" name="rules[{{ $index }}][value]" value="{{ $r->value }}" placeholder="Value" style="width:200px">
                        @if($index > 0)
                        <select name="rules[{{ $index }}][boolean_operator]" class="control" style="width:80px">
                            <option value="and" {{ $r->boolean_operator === 'and' ? 'selected' : '' }}>AND</option>
                            <option value="or" {{ $r->boolean_operator === 'or' ? 'selected' : '' }}>OR</option>
                        </select>
                        @endif
                        <button type="button" class="btn btn-ghost danger" onclick="this.closest('.rule-row').remove()">Remove</button>
                    </div>
                    @empty
                    <div class="rule-row" style="display:flex;gap:8px;margin-bottom:8px;align-items:center">
                        <select name="rules[0][field]" class="control" style="width:180px">
                            <option value="">Select field</option>
                            @foreach($fields as $key => $label)
                                <option value="{{ $key }}">{{ $label }}</option>
                            @endforeach
                        </select>
                        <select name="rules[0][operator]" class="control" style="width:160px">
                            @foreach($operators as $op)
                                <option value="{{ $op }}">{{ $op }}</option>
                            @endforeach
                        </select>
                        <input class="control" name="rules[0][value]" placeholder="Value" style="width:200px">
                        <button type="button" class="btn btn-ghost danger" onclick="this.closest('.rule-row').remove()">Remove</button>
                    </div>
                    @endforelse
                </div>
                <button type="button" class="btn btn-ghost" onclick="addRule()">+ Add Rule</button>
            </div>
        </div>
        <div style="padding:16px;border-top:1px solid var(--line);display:flex;gap:8px;justify-content:flex-end">
            <a href="/email/segments/{{ $row->id }}" class="btn btn-ghost">Cancel</a>
            <button type="submit" class="btn btn-primary">Update Segment</button>
        </div>
    </form>
</div>

<script>
var ruleIndex = {{ count($rules) ?: 1 }};
function addRule() {
    var container = document.getElementById('rules-container');
    var row = container.querySelector('.rule-row').cloneNode(true);
    row.querySelectorAll('select, input').forEach(function(el) {
        el.name = el.name.replace(/\[\d+\]/, '[' + ruleIndex + ']');
        el.value = '';
    });
    container.appendChild(row);
    ruleIndex++;
}
</script>
@endsection
