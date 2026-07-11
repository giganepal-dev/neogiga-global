@extends('pcb.layout')

@section('title', $project->name.' — NeoGiga PCB')
@section('robots', 'noindex,nofollow,noarchive')

@push('styles')
<style>
    .status-track{display:grid;grid-template-columns:repeat(5,1fr);gap:6px}.status-step{border-top:4px solid #dbe4ec;padding-top:8px;color:var(--muted);font-size:.72rem;font-weight:800}.status-step.done{border-color:var(--cyan);color:#0e7490}.status-step.current{border-color:var(--gold);color:#7c4a03}.quote-total{display:flex;align-items:end;justify-content:space-between;gap:18px;padding:16px;background:#f8fafc;border:1px solid var(--line);border-radius:7px}.quote-total strong{font-size:1.8rem}.security-note{display:grid;grid-template-columns:30px 1fr;gap:10px;background:#edf9fb;border:1px solid #c7edf3;padding:12px;border-radius:7px}.security-note b{color:#0e7490}.security-icon{width:30px;height:30px;border-radius:7px;background:var(--cyan);color:#04202a;display:grid;place-items:center;font-weight:900}.spec-list{display:grid;grid-template-columns:repeat(2,minmax(0,1fr));gap:12px}.spec-list div{border-bottom:1px solid var(--line);padding-bottom:8px}.spec-list small{display:block;color:var(--muted);text-transform:uppercase;font-weight:800}.spec-list b{display:block;margin-top:2px}.danger-zone{border-color:#fecaca}.danger-zone .card-head{background:#fff7f7}
    @media(max-width:600px){.status-track{grid-template-columns:1fr}.status-step{border-top:0;border-left:4px solid #dbe4ec;padding:6px 0 6px 10px}.spec-list{grid-template-columns:1fr}.quote-total{display:block}.quote-total .actions{margin-top:12px}}
</style>
@endpush

@section('content')
@php
    $quote = $project->quoteConfigurations->first();
    $statusOrder = ['draft','files_ready','quote_pending','quoted','ordered','manufacturing','inspection','shipped','completed'];
    $statusIndex = array_search($project->status, $statusOrder, true);
    $statusIndex = $statusIndex === false ? 0 : $statusIndex;
    $stages = [0 => 'Requirements', 1 => 'Files ready', 2 => 'Engineering review', 3 => 'Quote', 4 => 'Order / production'];
@endphp
<section class="page">
    <div class="wrap">
        <nav class="crumbs"><a href="/en/projects">Projects</a><span>/</span><span>{{ $project->code }}</span></nav>
        @if(session('status'))<div class="notice">{{ session('status') }}</div>@endif
        @if($errors->any())<div class="errors"><ul>@foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul></div>@endif

        <header class="page-head">
            <div><div class="eyebrow">{{ $project->code }}</div><h1 class="page-title">{{ $project->name }}</h1><p class="lead">{{ $project->description ?: 'PCB fabrication project workspace' }}</p></div>
            <div class="actions"><span class="badge badge-{{ $project->status }}">{{ str_replace('_',' ',$project->status) }}</span></div>
        </header>

        <div class="card" style="margin-bottom:16px"><div class="card-body"><div class="status-track">
            @foreach($stages as $index => $label)
                @php $mapped = $index === 4 ? 4 : $index; @endphp
                <div class="status-step {{ $statusIndex > $mapped ? 'done' : ($statusIndex === $mapped ? 'current' : '') }}">{{ $label }}</div>
            @endforeach
        </div></div></div>

        <div class="grid split">
            <div class="stack">
                <section class="card" aria-labelledby="files-title">
                    <div class="card-head"><div><h2 id="files-title">Project files</h2><div class="muted">Private storage · authorized downloads only</div></div></div>
                    <div class="card-body">
                        <div class="security-note"><span class="security-icon">S</span><div><b>Private by default</b><div class="muted">Downloads use short-lived signed links and are recorded in the project access log. ZIP files are checked for unsafe paths, expansion size and compression ratio before storage.</div></div></div>
                        <form method="post" action="/en/projects/{{ $project->id }}/files" enctype="multipart/form-data" style="margin-top:16px">
                            @csrf
                            <div class="form-grid">
                                <div class="field"><label for="file_type">Document type</label><select class="control" id="file_type" name="file_type" required><option value="gerber">Gerber ZIP</option><option value="bom">Bill of materials</option><option value="cpl">Component placement list</option><option value="schematic">Schematic</option><option value="pcb_source">PCB source</option><option value="assembly_drawing">Assembly drawing</option><option value="step">STEP model</option><option value="other">Other document</option></select></div>
                                <div class="field"><label for="file">Choose file</label><input class="control" id="file" type="file" name="file" required></div>
                            </div>
                            <div class="form-actions"><button class="btn btn-primary" type="submit">Upload private file</button></div>
                        </form>
                    </div>
                    @if($project->files->count())
                        <div class="table-wrap"><table class="table"><thead><tr><th>File</th><th>Type</th><th>Validation</th><th>Uploaded</th><th></th></tr></thead><tbody>
                        @foreach($project->files as $file)
                            @php $scan = $file->scanResults->first(); @endphp
                            <tr>
                                <td><div class="file-name">{{ $file->filename_original }}</div><div class="muted">{{ number_format($file->file_size / 1024, 1) }} KB</div></td>
                                <td><span class="badge badge-draft">{{ str_replace('_',' ',$file->file_type) }}</span></td>
                                <td><div class="file-security"><span class="badge badge-completed">Structure checked</span>@if($file->malware_scanned)<span class="badge badge-completed">Malware scanned</span>@else<span class="badge badge-review">Malware scan pending</span>@endif</div></td>
                                <td>{{ $file->created_at->format('M j, Y H:i') }}</td>
                                <td><a class="btn btn-light" href="{{ $downloadUrls[$file->id] }}">Download</a></td>
                            </tr>
                        @endforeach
                        </tbody></table></div>
                    @endif
                </section>

                <section class="card" aria-labelledby="quote-title">
                    <div class="card-head"><div><h2 id="quote-title">Engineering quote</h2><div class="muted">Board configuration and commercial approval</div></div>@if($quote)<span class="badge badge-{{ $quote->status }}">{{ $quote->status }}</span>@endif</div>
                    <div class="card-body">
                        @if(!$project->files->where('file_type','gerber')->count())
                            <div class="notice">Upload a Gerber ZIP to unlock the engineering quote request.</div>
                        @elseif(!$quote || in_array($quote->status,['draft','rejected'],true))
                            @if($quote?->status === 'rejected')<div class="notice">Your requested quote changes are recorded. Update the specification and submit again.</div>@endif
                            <form method="post" action="/en/projects/{{ $project->id }}/quotes">
                                @csrf
                                <div class="form-grid">
                                    <div class="field"><label for="board_type">Board type</label><select class="control" id="board_type" name="board_type" required>@foreach(['double_sided'=>'Double sided','single_sided'=>'Single sided','multilayer'=>'Multilayer','rigid_flex'=>'Rigid-flex','flex'=>'Flex','aluminum'=>'Aluminum','ceramic'=>'Ceramic'] as $value=>$label)<option value="{{ $value }}" @selected(old('board_type',$quote?->board_type ?? 'double_sided')===$value)>{{ $label }}</option>@endforeach</select></div>
                                    <div class="field"><label for="quantity">Board quantity</label><input class="control" id="quantity" type="number" name="quantity" min="1" max="1000000" value="{{ old('quantity',$quote?->quantity ?? $project->target_quantity) }}" required></div>
                                    <div class="field"><label for="length_mm">Length (mm)</label><input class="control" id="length_mm" type="number" step="0.01" min="1" max="2000" name="length_mm" value="{{ old('length_mm',$quote?->length_mm) }}" required></div>
                                    <div class="field"><label for="width_mm">Width (mm)</label><input class="control" id="width_mm" type="number" step="0.01" min="1" max="2000" name="width_mm" value="{{ old('width_mm',$quote?->width_mm) }}" required></div>
                                    <div class="field"><label for="thickness_mm">Thickness (mm)</label><input class="control" id="thickness_mm" type="number" step="0.1" min="0.2" max="10" name="thickness_mm" value="{{ old('thickness_mm',$quote?->thickness_mm ?? 1.6) }}" required></div>
                                    <div class="field"><label for="layer_count">Copper layers</label><input class="control" id="layer_count" type="number" min="1" max="64" name="layer_count" value="{{ old('layer_count',$quote?->layer_count ?? 2) }}" required></div>
                                    <div class="field"><label for="substrate_material">Material</label><select class="control" id="substrate_material" name="substrate_material"><option>FR-4</option><option>High-Tg FR-4</option><option>Aluminum</option><option>Polyimide</option><option>Ceramic</option></select></div>
                                    <div class="field"><label for="outer_copper_oz">Outer copper</label><select class="control" id="outer_copper_oz" name="outer_copper_oz"><option value="1">1 oz</option><option value="2">2 oz</option><option value="3">3 oz</option></select></div>
                                    <div class="field"><label for="solder_mask_color">Solder mask</label><select class="control" id="solder_mask_color" name="solder_mask_color">@foreach(['green','black','blue','red','white','yellow'] as $color)<option @selected(($quote?->solder_mask_color ?? 'green')===$color)>{{ $color }}</option>@endforeach</select></div>
                                    <div class="field"><label for="silkscreen_color">Silkscreen</label><select class="control" id="silkscreen_color" name="silkscreen_color"><option>white</option><option>black</option></select></div>
                                    <div class="field"><label for="surface_finish">Surface finish</label><select class="control" id="surface_finish" name="surface_finish"><option value="HASL_Lead_Free">Lead-free HASL</option><option value="ENIG">ENIG</option><option value="OSP">OSP</option><option value="Immersion_Silver">Immersion silver</option><option value="Immersion_Tin">Immersion tin</option><option value="Gold_Fingers">Gold fingers</option><option value="HASL">HASL</option></select></div>
                                    <div class="field"><label for="via_covering">Via covering</label><select class="control" id="via_covering" name="via_covering"><option value="tented">Tented</option><option value="plugged">Plugged</option><option value="filled">Filled</option><option value="open">Open</option></select></div>
                                    <div class="field"><label for="panelization_type">Panelization</label><select class="control" id="panelization_type" name="panelization_type"><option value="none">None</option><option value="v_score">V-score</option><option value="routing">Routing</option><option value="tab_route">Tab route</option></select></div>
                                    <div class="field"><label for="production_speed">Production speed</label><select class="control" id="production_speed" name="production_speed"><option value="standard">Standard</option><option value="fast">Fast</option><option value="express">Express review</option></select></div>
                                </div>
                                <details class="advanced" style="margin-top:14px"><summary>Testing and advanced fabrication</summary><div class="check-grid">
                                    @foreach(['aoi_testing'=>'AOI testing','electrical_test'=>'Electrical test','impedance_control'=>'Controlled impedance','blind_buried_vias'=>'Blind / buried vias','hdi'=>'HDI process','edge_plating'=>'Edge plating','castellated_holes'=>'Castellated holes'] as $name=>$label)
                                        <label class="check"><input type="checkbox" name="{{ $name }}" value="1" @checked(in_array($name,['aoi_testing','electrical_test'],true) || old($name) || $quote?->{$name})> {{ $label }}</label>
                                    @endforeach
                                </div></details>
                                <div class="form-actions"><button class="btn btn-primary" type="submit">Request engineering quote</button></div>
                            </form>
                        @elseif($quote->status === 'submitted')
                            <div class="notice"><b>Engineering review in progress.</b> The board configuration and private files are waiting for manual review. No automatic pricing or manufacturing commitment has been made.</div>
                            <div class="spec-list">
                                <div><small>Board</small><b>{{ str_replace('_',' ',$quote->board_type) }} · {{ $quote->layer_count }} layers</b></div>
                                <div><small>Size</small><b>{{ $quote->length_mm }} × {{ $quote->width_mm }} mm</b></div>
                                <div><small>Quantity</small><b>{{ number_format($quote->quantity) }}</b></div>
                                <div><small>Finish</small><b>{{ str_replace('_',' ',$quote->surface_finish) }}</b></div>
                            </div>
                        @elseif($quote->status === 'quoted')
                            <div class="spec-list" style="margin-bottom:16px">
                                <div><small>Board</small><b>{{ str_replace('_',' ',$quote->board_type) }} · {{ $quote->layer_count }} layers</b></div>
                                <div><small>Quantity</small><b>{{ number_format($quote->quantity) }}</b></div>
                                <div><small>Lead time</small><b>{{ $quote->lead_time_days }} days after approval</b></div>
                                <div><small>Valid until</small><b>{{ $quote->quote_valid_until?->format('M j, Y') ?: 'Confirm with engineering' }}</b></div>
                            </div>
                            @if($quote->engineering_notes)<p>{{ $quote->engineering_notes }}</p>@endif
                            <table class="table"><tbody><tr><td>Fabrication</td><td class="price">{{ $quote->currency }} {{ number_format($quote->total_fabrication_price,2) }}</td></tr><tr><td>Setup</td><td class="price">{{ $quote->currency }} {{ number_format($quote->setup_charge,2) }}</td></tr><tr><td>Engineering</td><td class="price">{{ $quote->currency }} {{ number_format($quote->engineering_charge,2) }}</td></tr>@foreach($quote->lineItems as $item)<tr><td>{{ $item->description }}</td><td class="price">{{ $item->currency }} {{ number_format($item->total_price,2) }}</td></tr>@endforeach</tbody></table>
                            <div class="quote-total"><div><span class="muted">Commercial total</span><strong>{{ $quote->currency }} {{ number_format($quote->total_price,2) }}</strong></div><div class="actions"><details class="advanced"><summary class="btn btn-primary">Approve quote</summary><div><form method="post" action="/en/projects/{{ $project->id }}/quotes/{{ $quote->id }}/approve">@csrf<div class="field"><label for="customer_notes">Order note</label><textarea class="control" id="customer_notes" name="customer_notes"></textarea></div><div class="form-actions"><button class="btn btn-primary" type="submit">Approve and create order</button></div></form></div></details></div></div>
                            <details class="advanced" style="margin-top:14px"><summary>Request quote changes</summary><div><form method="post" action="/en/projects/{{ $project->id }}/quotes/{{ $quote->id }}/reject">@csrf<div class="field"><label for="change_notes">Required changes</label><textarea class="control" id="change_notes" name="customer_notes" required></textarea></div><div class="form-actions"><button class="btn btn-danger" type="submit">Send change request</button></div></form></div></details>
                        @elseif($quote->status === 'approved')
                            <div class="notice"><b>Quote approved.</b> Order {{ $quote->order?->order_number }} is now in the shared NeoGiga order workflow. Payment and production start remain subject to manual confirmation.</div>
                            <div class="spec-list"><div><small>Order</small><b>{{ $quote->order?->order_number }}</b></div><div><small>Order status</small><b>{{ $quote->order?->status }}</b></div><div><small>Total</small><b>{{ $quote->currency }} {{ number_format($quote->total_price,2) }}</b></div><div><small>Payment</small><b>{{ $quote->order?->payment_status }}</b></div></div>
                        @endif
                    </div>
                </section>

                <section class="card" aria-labelledby="activity-title"><div class="card-head"><h2 id="activity-title">Project activity</h2></div><div class="card-body"><div class="timeline">@forelse($project->activityLogs as $event)<div class="timeline-item"><span class="timeline-dot"></span><div><p>{{ $event->description ?: str_replace('_',' ',$event->action) }}</p><time>{{ $event->created_at->format('M j, Y H:i') }}</time></div></div>@empty<p class="muted">No activity recorded.</p>@endforelse</div></div></section>
            </div>

            <aside class="stack">
                <section class="card"><div class="card-head"><h2>Project summary</h2></div><div class="card-body"><div class="spec-list">
                    <div><small>Type</small><b>{{ ucfirst($project->project_type) }}</b></div><div><small>Quantity</small><b>{{ number_format($project->target_quantity) }}</b></div><div><small>Destination</small><b>{{ $project->destination_country }}</b></div><div><small>Required date</small><b>{{ $project->required_date?->format('M j, Y') ?: 'Flexible' }}</b></div><div><small>Confidentiality</small><b>{{ str_replace('_',' ',$project->confidentiality) }}</b></div><div><small>Currency</small><b>{{ $project->currency }}</b></div>
                </div></div></section>

                @if(in_array($project->status,['draft','requirements_pending','files_ready','quote_pending'],true))
                <details class="advanced"><summary>Edit project requirements</summary><div><form method="post" action="/en/projects/{{ $project->id }}">@csrf @method('PATCH')<div class="form-grid">
                    <div class="field full"><label for="edit_name">Project name</label><input class="control" id="edit_name" name="name" value="{{ old('name',$project->name) }}" required></div>
                    <div class="field full"><label for="edit_description">Brief</label><textarea class="control" id="edit_description" name="description">{{ old('description',$project->description) }}</textarea></div>
                    <div class="field"><label for="edit_application">Application</label><input class="control" id="edit_application" name="application_type" value="{{ old('application_type',$project->application_type) }}"></div>
                    <div class="field"><label for="edit_quantity">Quantity</label><input class="control" id="edit_quantity" type="number" name="target_quantity" min="1" value="{{ old('target_quantity',$project->target_quantity) }}" required></div>
                    <div class="field"><label for="edit_budget">Budget</label><input class="control" id="edit_budget" type="number" step="0.01" name="target_budget" value="{{ old('target_budget',$project->target_budget) }}"></div>
                    <div class="field"><label for="edit_currency">Currency</label><input class="control" id="edit_currency" name="currency" maxlength="3" value="{{ old('currency',$project->currency) }}" required></div>
                    <div class="field"><label for="edit_date">Required date</label><input class="control" id="edit_date" type="date" name="required_date" value="{{ old('required_date',$project->required_date?->toDateString()) }}"></div>
                    <div class="field"><label for="edit_country">Destination</label><input class="control" id="edit_country" name="destination_country" value="{{ old('destination_country',$project->destination_country) }}" required></div>
                    <div class="field full"><label for="edit_postal">Postal code</label><input class="control" id="edit_postal" name="shipping_postal_code" value="{{ old('shipping_postal_code',$project->shipping_postal_code) }}"></div>
                </div><div class="form-actions"><button class="btn btn-primary" type="submit">Save requirements</button></div></form></div></details>
                @endif

                <section class="card"><div class="card-head"><h2>Engineering support</h2></div><div class="card-body"><p class="muted">Reference project code <b>{{ $project->code }}</b> when contacting the PCB engineering desk.</p><a class="btn btn-light" href="mailto:pcb@neogiga.com?subject={{ urlencode($project->code.' '.$project->name) }}">Contact engineering</a></div></section>

                @if(in_array($project->status,['draft','requirements_pending','files_ready','quote_pending','quoted'],true))
                <section class="card danger-zone"><div class="card-head"><h2>Cancel project</h2></div><div class="card-body"><p class="muted">Cancelling stops the active workflow. Files remain retained for audit and recovery.</p><form method="post" action="/en/projects/{{ $project->id }}/cancel">@csrf<button class="btn btn-danger" type="submit">Cancel project</button></form></div></section>
                @endif
            </aside>
        </div>
    </div>
</section>
@endsection
