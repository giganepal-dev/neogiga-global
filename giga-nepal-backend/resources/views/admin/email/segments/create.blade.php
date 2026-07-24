@extends('admin.layout')
@section('title', 'Create Segment')
@section('crumb', 'Email / Segments / Create')

@section('content')
<div class="page-head">
    <div>
        <h2>Create Segment</h2>
        <p>Define rules to dynamically segment subscribers.</p>
    </div>
</div>

<div class="card">
    <form method="POST" action="/email/segments">
        @csrf
        <div class="card-body" style="padding:16px">
            <div class="form-grid">
                <div class="field">
                    <label>Name *</label>
                    <input class="control" name="name" value="{{ old('name') }}" required>
                </div>
                <div class="field">
                    <label>Type</label>
                    <select class="control" name="segment_type">
                        <option value="dynamic" {{ old('segment_type') === 'dynamic' ? 'selected' : '' }}>Dynamic</option>
                        <option value="static" {{ old('segment_type') === 'static' ? 'selected' : '' }}>Static</option>
                    </select>
                </div>
            </div>
            <div class="field" style="margin-top:16px">
                <label>Description</label>
                <textarea class="control" name="description" rows="2">{{ old('description') }}</textarea>
            </div>

            <div class="field" style="margin-top:16px">
                <label>Rules</label>
                <div id="rules-container">
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
                </div>
                <button type="button" class="btn btn-ghost" onclick="addRule()">+ Add Rule</button>
            </div>
        </div>
        <div style="padding:16px;border-top:1px solid var(--line);display:flex;gap:8px;justify-content:flex-end">
            <a href="/email/segments" class="btn btn-ghost">Cancel</a>
            <button type="submit" class="btn btn-primary">Create Segment</button>
        </div>
    </form>
</div>

<script>
var ruleIndex = 1;
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
