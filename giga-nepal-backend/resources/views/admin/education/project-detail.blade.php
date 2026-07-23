@extends('admin.layout')
@section('title', $project->title)
@section('crumb', 'Admin / Education / Projects / ' . $project->title)

@section('content')
<div class="page-head">
    <div>
        <h2>{{ $project->title }}</h2>
        <p>Created {{ $project->created_at->diffForHumans() }} by {{ $project->author?->name ?? 'Unknown' }}</p>
    </div>
    <div class="page-actions">
        @if($project->verification_status !== 'published')
        <form method="POST" action="/admin/education/projects/{{ $project->id }}/approve" style="display:inline">
            @csrf <button class="btn btn-primary" type="submit">Publish</button>
        </form>
        @endif
        @if($project->verification_status !== 'archived')
        <form method="POST" action="/admin/education/projects/{{ $project->id }}/archive" style="display:inline">
            @csrf <button class="btn btn-ghost danger" type="submit">Archive</button>
        </form>
        @endif
    </div>
</div>

@if(session('success'))
    <div class="note" style="background:#dcfce7;border-color:#86efac;color:#166534">{{ session('success') }}</div>
@endif

<div class="grid split" style="align-items:start">
    {{-- Main content --}}
    <div>
        <div class="card" style="margin-bottom:16px">
            <div class="card-h"><h2>Project Details</h2>
                <span class="badge {{ match($project->verification_status) {
                    'published' => 'b-ok',
                    'draft','ai_generated' => 'b-muted',
                    'review_required','needs_update' => 'b-warn',
                    default => 'b-muted'
                } }}">{{ str_replace('_', ' ', $project->verification_status) }}</span>
            </div>
            <div style="padding:16px">
                <form method="POST" action="/admin/education/projects/{{ $project->id }}/update">
                    @csrf
                    <div class="form-grid">
                        <div class="field"><label>Title</label><input class="control" name="title" value="{{ $project->title }}"></div>
                        <div class="field"><label>Category</label><input class="control" name="category" value="{{ $project->category }}"></div>
                        <div class="field"><label>Skill Level</label>
                            <select class="control" name="skill_level">
                                @foreach(['beginner','intermediate','advanced','expert'] as $l)
                                    <option {{ $project->skill_level === $l ? 'selected' : '' }}>{{ $l }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="field"><label>Controller</label><input class="control" name="main_controller" value="{{ $project->main_controller }}"></div>
                        <div class="field"><label>Status</label>
                            <select class="control" name="verification_status">
                                @foreach(['draft','ai_generated','review_required','technically_reviewed','published','needs_update','archived'] as $s)
                                    <option value="{{ $s }}" {{ $project->verification_status === $s ? 'selected' : '' }}>{{ ucfirst(str_replace('_',' ',$s)) }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="field"><label>Featured</label>
                            <select class="control" name="is_featured">
                                <option value="0" {{ !$project->is_featured ? 'selected' : '' }}>No</option>
                                <option value="1" {{ $project->is_featured ? 'selected' : '' }}>Yes</option>
                            </select>
                        </div>
                    </div>
                    <div class="field"><label>Summary</label><textarea class="control" name="summary" rows="3">{{ $project->summary }}</textarea></div>
                    <div class="field"><label>Description</label><textarea class="control" name="description" rows="6">{{ $project->description }}</textarea></div>
                    <button class="btn btn-primary" type="submit" style="margin-top:12px">Save Changes</button>
                </form>
            </div>
        </div>

        {{-- BOM --}}
        <div class="card" style="margin-bottom:16px">
            <div class="card-h"><h2>Bill of Materials</h2><span class="sub">{{ $project->bomLines->count() }} lines · Est. cost: {{ number_format($totalBomCost, 2) }}</span></div>
            <div class="scroll-x">
                <table class="tbl">
                    <thead><tr><th>#</th><th>Role</th><th>MPN</th><th>Product</th><th>Qty</th><th>Price</th><th></th></tr></thead>
                    <tbody>
                        @forelse($project->bomLines as $line)
                        <tr>
                            <td>{{ $line->line_no }}</td>
                            <td>{{ $line->component_role ?? '-' }}</td>
                            <td class="mono">{{ $line->preferred_mpn ?? '-' }}</td>
                            <td>{{ $line->preferredProduct?->name ?? '-' }}</td>
                            <td>{{ $line->quantity }}</td>
                            <td>{{ $line->unit_price ? number_format($line->unit_price * $line->quantity, 2) : '-' }}</td>
                            <td>
                                <form method="POST" action="/admin/education/projects/{{ $project->id }}/bom/{{ $line->id }}/delete" style="display:inline" onsubmit="return confirm('Delete this BOM line?')">
                                    @csrf <button class="btn btn-ghost icon-btn danger" type="submit" title="Delete">✕</button>
                                </form>
                            </td>
                        </tr>
                        @empty
                        <tr><td colspan="7" class="empty">No BOM lines yet.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            <div style="padding:16px;border-top:1px solid var(--line)">
                <h3 style="font-size:.88rem;margin:0 0 10px">Add BOM Line</h3>
                <form method="POST" action="/admin/education/projects/{{ $project->id }}/bom/add" style="display:flex;gap:8px;flex-wrap:wrap;align-items:end">
                    @csrf
                    <div class="field" style="flex:1;min-width:120px"><label>Role</label><input class="control" name="component_role" placeholder="e.g. Motor Driver"></div>
                    <div class="field" style="flex:1;min-width:120px"><label>MPN</label><input class="control" name="preferred_mpn" placeholder="e.g. L298N"></div>
                    <div class="field" style="width:80px"><label>Qty</label><input class="control" name="quantity" type="number" value="1" min="1"></div>
                    <div class="field" style="width:100px"><label>Product ID</label><input class="control" name="product_id" type="number"></div>
                    <div class="field"><label>Required</label><select class="control" name="is_required"><option value="1">Yes</option><option value="0">No</option></select></div>
                    <button class="btn btn-primary" type="submit">Add</button>
                </form>
            </div>
        </div>

        {{-- Code Files --}}
        <div class="card" style="margin-bottom:16px">
            <div class="card-h"><h2>Code Files</h2><span class="sub">{{ $project->codeFiles->count() }} files</span></div>
            @forelse($project->codeFiles as $code)
            <div style="padding:16px;border-bottom:1px solid var(--line)">
                <div style="display:flex;justify-content:space-between;align-items:center">
                    <div>
                        <strong>{{ $code->title }}</strong>
                        <span class="badge b-info" style="margin-left:8px">{{ $code->language }}</span>
                        <span class="badge {{ $code->verification_status==='verified' ? 'b-ok':'b-muted' }}">{{ $code->verification_status }}</span>
                    </div>
                    <span class="mono" style="font-size:.78rem;color:var(--muted)">{{ $code->target_board ?? 'Generic' }}</span>
                </div>
                <pre style="background:#1e293b;color:#e2e8f0;border-radius:8px;padding:12px;font-size:.78rem;overflow-x:auto;max-height:200px;margin-top:10px;white-space:pre-wrap">{{ $code->source_code }}</pre>
            </div>
            @empty
            <div class="empty"><p>No code files yet.</p></div>
            @endforelse
        </div>
    </div>

    {{-- Sidebar --}}
    <div>
        <div class="card" style="margin-bottom:16px">
            <div class="card-h"><h2>Project Info</h2></div>
            <div style="padding:16px">
                <div style="display:flex;justify-content:space-between;padding:8px 0;border-bottom:1px solid var(--line);font-size:.88rem"><span style="color:var(--muted)">Slug</span><span class="mono">{{ $project->slug }}</span></div>
                <div style="display:flex;justify-content:space-between;padding:8px 0;border-bottom:1px solid var(--line);font-size:.88rem"><span style="color:var(--muted)">Views</span><span>{{ number_format($project->view_count) }}</span></div>
                <div style="display:flex;justify-content:space-between;padding:8px 0;border-bottom:1px solid var(--line);font-size:.88rem"><span style="color:var(--muted)">Enrollments</span><span>{{ $project->enrollment_count }}</span></div>
                <div style="display:flex;justify-content:space-between;padding:8px 0;border-bottom:1px solid var(--line);font-size:.88rem"><span style="color:var(--muted)">Rating</span><span>{{ $project->rating_avg ? number_format($project->rating_avg, 1) : 'N/A' }} ({{ $project->rating_count }})</span></div>
                <div style="display:flex;justify-content:space-between;padding:8px 0;border-bottom:1px solid var(--line);font-size:.88rem"><span style="color:var(--muted)">Cost</span><span>{{ $project->currency }} {{ number_format($project->estimated_cost ?? 0, 2) }}</span></div>
                <div style="display:flex;justify-content:space-between;padding:8px 0;border-bottom:1px solid var(--line);font-size:.88rem"><span style="color:var(--muted)">Duration</span><span>{{ $project->estimated_duration_minutes ? $project->estimated_duration_minutes . ' min' : 'Variable' }}</span></div>
                <div style="display:flex;justify-content:space-between;padding:8px 0;font-size:.88rem"><span style="color:var(--muted)">LMS Course</span><span>{{ $project->lms_course_id ? 'Linked' : 'None' }}</span></div>
            </div>
        </div>

        @if($project->safety_warnings)
        <div class="card" style="margin-bottom:16px;border-color:#fca5a5">
            <div class="card-h" style="background:#fef2f2"><h2 style="color:#991b1b">Safety Warnings</h2></div>
            <div style="padding:16px;color:#991b1b;font-size:.88rem">{{ $project->safety_warnings }}</div>
        </div>
        @endif

        <div class="card">
            <div class="card-h"><h2>Quick Stats</h2></div>
            <div style="padding:16px">
                <div style="display:flex;justify-content:space-between;padding:8px 0;border-bottom:1px solid var(--line);font-size:.88rem"><span style="color:var(--muted)">BOM Lines</span><span>{{ $project->bomLines->count() }}</span></div>
                <div style="display:flex;justify-content:space-between;padding:8px 0;border-bottom:1px solid var(--line);font-size:.88rem"><span style="color:var(--muted)">Required</span><span>{{ $project->bomLines->where('is_required', true)->count() }}</span></div>
                <div style="display:flex;justify-content:space-between;padding:8px 0;border-bottom:1px solid var(--line);font-size:.88rem"><span style="color:var(--muted)">Optional</span><span>{{ $project->bomLines->where('is_required', false)->count() }}</span></div>
                <div style="display:flex;justify-content:space-between;padding:8px 0;font-size:.88rem"><span style="color:var(--muted)">Code Files</span><span>{{ $project->codeFiles->count() }}</span></div>
            </div>
        </div>
    </div>
</div>
@endsection
