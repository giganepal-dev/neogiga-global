@extends('admin.layout')
@section('title',$project->name)
@section('crumb','Commerce / PCB / '.$project->code)
@section('page_actions')<a class="btn btn-ghost" href="/admin/pcb">Back to PCB queue</a>@endsection

@section('content')
@php $quote = $project->quoteConfigurations->first(); @endphp
@if($errors->any())<div class="note" style="background:#fff1f2;border-color:#fecaca;color:#991b1b"><strong>Action not saved.</strong><ul>@foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul></div>@endif

<div class="grid kpis">
    <div class="kpi"><div class="t">Status</div><div class="v" style="font-size:1.15rem"><span class="badge b-info">{{ str_replace('_',' ',$project->status) }}</span></div><div class="s">Updated {{ $project->updated_at->diffForHumans() }}</div></div>
    <div class="kpi"><div class="t">Quantity</div><div class="v tnum">{{ number_format($project->target_quantity) }}</div><div class="s">{{ ucfirst($project->project_type) }}</div></div>
    <div class="kpi"><div class="t">Private files</div><div class="v tnum">{{ number_format($project->files->count()) }}</div><div class="s">{{ str_replace('_',' ',$project->confidentiality) }}</div></div>
    <div class="kpi"><div class="t">Quote</div><div class="v" style="font-size:1.15rem">{{ $quote ? ucfirst($quote->status) : 'Not submitted' }}</div><div class="s">{{ $quote?->currency ?: $project->currency }}</div></div>
</div>

<div class="grid dashboard-split">
    <div class="grid">
        <section class="card">
            <div class="card-h"><div><h2>Engineering requirements</h2><div class="sub">Customer {{ $project->user?->name }} · {{ $project->user?->email }}</div></div></div>
            <div class="modal-b"><div class="form-grid">
                <div class="field"><label>Application</label><div>{{ $project->application_type ?: 'Not specified' }}</div></div><div class="field"><label>Destination</label><div>{{ $project->destination_country }} {{ $project->shipping_postal_code }}</div></div>
                <div class="field"><label>Required date</label><div>{{ $project->required_date?->format('M j, Y') ?: 'Flexible' }}</div></div><div class="field"><label>Budget</label><div>{{ $project->target_budget ? $project->currency.' '.number_format($project->target_budget,2) : 'Not stated' }}</div></div>
                <div class="field" style="grid-column:1/-1"><label>Project brief</label><div>{{ $project->description ?: 'No brief supplied.' }}</div></div>
            </div></div>
        </section>

        <section class="card">
            <div class="card-h"><div><h2>Private engineering files</h2><div class="sub">Every admin download is written to the access log</div></div></div>
            <div class="scroll-x"><table class="tbl"><thead><tr><th>File</th><th>Type</th><th>Safety state</th><th>Uploaded</th><th></th></tr></thead><tbody>
            @forelse($project->files as $file)
                <tr><td><strong>{{ $file->filename_original }}</strong><div class="sub">{{ number_format($file->file_size/1024,1) }} KB</div></td><td>{{ str_replace('_',' ',$file->file_type) }}</td><td><span class="badge b-ok">Structure checked</span> @if(!$file->malware_scanned)<span class="badge b-warn">Malware scan pending</span>@endif</td><td>{{ $file->created_at->format('M j, Y H:i') }}</td><td><a class="btn btn-ghost" href="/admin/pcb/projects/{{ $project->id }}/files/{{ $file->id }}/download">Download</a></td></tr>
            @empty<tr><td colspan="5"><div class="empty"><h3>No files uploaded</h3><p>The customer has not added design files.</p></div></td></tr>@endforelse
            </tbody></table></div>
        </section>

        @foreach($project->gerberAnalysisRuns as $run)
        <section class="card">
            <div class="card-h"><div><h2>Gerber structure review</h2><div class="sub">{{ $run->parser_version }} · {{ $run->confidence_level }} confidence · {{ $run->created_at->diffForHumans() }}</div></div><span class="badge {{ $run->status === 'completed' ? 'b-ok' : ($run->status === 'failed' ? 'b-danger' : 'b-warn') }}">{{ $run->status }}</span></div>
            <div class="modal-b">
                @if($run->detected_width_mm || $run->detected_hole_count)
                    <div style="display:grid;grid-template-columns:repeat(4,1fr);gap:12px;margin-bottom:14px">
                        @if($run->detected_width_mm)<div><small style="color:var(--faint);text-transform:uppercase;font-size:.68rem">Board size</small><div style="font-weight:700">{{ $run->detected_width_mm }} × {{ $run->detected_height_mm }} mm</div></div>@endif
                        @if($run->detected_layer_count)<div><small style="color:var(--faint);text-transform:uppercase;font-size:.68rem">Copper layers</small><div style="font-weight:700">{{ $run->detected_layer_count }}</div></div>@endif
                        @if($run->detected_hole_count)<div><small style="color:var(--faint);text-transform:uppercase;font-size:.68rem">Drill holes</small><div style="font-weight:700">{{ $run->detected_hole_count }}</div></div>@endif
                        @if($run->detected_board_area_cm2)<div><small style="color:var(--faint);text-transform:uppercase;font-size:.68rem">Board area</small><div style="font-weight:700">{{ $run->detected_board_area_cm2 }} cm²</div></div>@endif
                    </div>
                @endif
            </div>
            <div class="scroll-x"><table class="tbl"><thead><tr><th>Archive entry</th><th>Detected layer</th><th>Matched</th></tr></thead><tbody>@forelse($run->detectedLayers as $layer)<tr><td><strong>{{ $layer->filename }}</strong></td><td>{{ str_replace('_',' ',$layer->detected_type) }}</td><td>@if($layer->is_matched)<span class="badge b-ok">Yes</span>@else<span class="badge b-warn">Review</span>@endif</td></tr>@empty<tr><td colspan="3">No standard layer names detected.</td></tr>@endforelse</tbody></table></div>
            @if($run->warnings->count())<div class="modal-b">@foreach($run->warnings as $warning)<div class="note" style="background:{{ $warning->severity === 'blocking' ? 'rgba(239,68,68,.08)' : ($warning->severity === 'warning' ? 'rgba(249,189,44,.08)' : 'rgba(40,216,251,.05)') }};border-color:{{ $warning->severity === 'blocking' ? 'rgba(239,68,68,.25)' : ($warning->severity === 'warning' ? 'rgba(249,189,44,.25)' : 'rgba(40,216,251,.15)') }}"><strong>[{{ $warning->warning_code }}]</strong> {{ $warning->message }}</div>@endforeach</div>@endif
        </section>
        @endforeach

        <section class="card">
            <div class="card-h"><div><h2>Commercial quote</h2><div class="sub">Manual price and lead-time approval</div></div>@if($quote)<span class="badge b-info">{{ $quote->status }}</span>@endif</div>
            @if($quote)
                <div class="modal-b">
                    <div class="form-grid" style="margin-bottom:14px"><div class="field"><label>Board</label><div>{{ str_replace('_',' ',$quote->board_type) }} · {{ $quote->layer_count }} layers</div></div><div class="field"><label>Size / quantity</label><div>{{ $quote->length_mm }} × {{ $quote->width_mm }} mm · {{ number_format($quote->quantity) }} pcs</div></div><div class="field"><label>Material / copper</label><div>{{ $quote->substrate_material }} · {{ $quote->outer_copper_oz }} oz</div></div><div class="field"><label>Finish</label><div>{{ str_replace('_',' ',$quote->surface_finish) }}</div></div></div>
                    @if(in_array($quote->status,['submitted','rejected','draft','quoted'],true))
                    <form method="post" action="/admin/pcb/projects/{{ $project->id }}/quotes/{{ $quote->id }}">@csrf<div class="form-grid">
                        <div class="field"><label for="setup_charge">Setup charge</label><input class="control" id="setup_charge" type="number" step="0.01" min="0" name="setup_charge" value="{{ old('setup_charge',$quote->setup_charge) }}" required></div>
                        <div class="field"><label for="engineering_charge">Engineering charge</label><input class="control" id="engineering_charge" type="number" step="0.01" min="0" name="engineering_charge" value="{{ old('engineering_charge',$quote->engineering_charge) }}" required></div>
                        <div class="field"><label for="fabrication_unit_price">Fabrication / board</label><input class="control" id="fabrication_unit_price" type="number" step="0.0001" min="0" name="fabrication_unit_price" value="{{ old('fabrication_unit_price',$quote->fabrication_unit_price) }}" required></div>
                        <div class="field"><label for="currency">Currency</label><input class="control" id="currency" name="currency" maxlength="3" value="{{ old('currency',$quote->currency) }}" required></div>
                        <div class="field"><label for="lead_time_days">Lead time (days)</label><input class="control" id="lead_time_days" type="number" min="1" max="365" name="lead_time_days" value="{{ old('lead_time_days',$quote->lead_time_days) }}" required></div>
                        <div class="field"><label for="quote_valid_until">Valid until</label><input class="control" id="quote_valid_until" type="date" min="{{ now()->toDateString() }}" name="quote_valid_until" value="{{ old('quote_valid_until',$quote->quote_valid_until?->toDateString() ?: now()->addDays(14)->toDateString()) }}" required></div>
                        <div class="field" style="grid-column:1/-1"><label for="engineering_notes">Engineering and commercial notes</label><textarea class="control" id="engineering_notes" name="engineering_notes" required>{{ old('engineering_notes',$quote->engineering_notes) }}</textarea></div>
                    </div><div class="actions" style="margin-top:12px"><button class="btn btn-primary" type="submit">Issue customer quote</button></div></form>
                    @else
                        <div class="note"><strong>Commercial snapshot locked.</strong> Quote {{ $quote->status }}@if($quote->order), order {{ $quote->order->order_number }}@endif.</div>
                    @endif
                </div>
            @else
                <div class="empty"><h3>Board specification not submitted</h3><p>The customer must upload Gerber and submit the board configuration before pricing.</p></div>
            @endif
        </section>
    </div>

    <aside class="grid">
        <section class="card"><div class="card-h"><h2>Update workflow</h2></div><form class="modal-b form-stack" method="post" action="/admin/pcb/projects/{{ $project->id }}/status">@csrf<div class="field"><label for="status">Status</label><select class="control" id="status" name="status">@foreach($statuses as $status)<option value="{{ $status }}" @selected($project->status===$status)>{{ ucfirst(str_replace('_',' ',$status)) }}</option>@endforeach</select></div><div class="field"><label for="note">Audit note</label><textarea class="control" id="note" name="note" required placeholder="Reason, review result or production update"></textarea></div><button class="btn btn-primary" type="submit">Save status</button></form></section>
        <section class="card"><div class="card-h"><h2>Activity log</h2></div><div class="modal-b">@forelse($project->activityLogs as $event)<div style="padding:0 0 12px;margin-bottom:12px;border-bottom:1px solid var(--line)"><strong>{{ $event->description ?: str_replace('_',' ',$event->action) }}</strong><div class="sub">{{ $event->user?->name ?: 'System' }} · {{ $event->created_at->format('M j, Y H:i') }}</div></div>@empty<div class="empty"><p>No activity recorded.</p></div>@endforelse</div></section>
    </aside>
</div>
@endsection
