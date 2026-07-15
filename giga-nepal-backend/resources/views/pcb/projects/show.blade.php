@extends('pcb.layout')

@section('title', $project->name.' — NeoGiga PCB')
@section('robots', 'noindex,nofollow,noarchive')

@php
    $quote = $project->quoteConfigurations->first();
    $statusOrder = ['draft','files_ready','quote_pending','quoted','ordered','manufacturing','inspection','shipped','completed'];
    $statusIndex = array_search($project->status, $statusOrder, true);
    $statusIndex = $statusIndex === false ? 0 : $statusIndex;
    $stages = [0 => 'Requirements', 1 => 'Files ready', 2 => 'Eng. review', 3 => 'Quote', 4 => 'Production'];
    $statusColors = ['draft'=>'b-muted','cancelled'=>'b-danger','on_hold'=>'b-danger','quote_pending'=>'b-warn','requirements_pending'=>'b-warn','files_ready'=>'b-warn','quoted'=>'b-info','awaiting_approval'=>'b-info','ordered'=>'b-info','manufacturing'=>'b-info','inspection'=>'b-warn','shipped'=>'b-ok','completed'=>'b-ok','design_approved'=>'b-ok','design_review'=>'b-warn','design_in_progress'=>'b-info','approved'=>'b-ok','submitted'=>'b-info','rejected'=>'b-danger'];
@endphp

@section('content')
<section style="padding:28px 0 64px">
    <div class="wrap">
        <nav class="crumbs"><a href="/en/projects">Projects</a><span>/</span><span>{{ $project->code }}</span></nav>
        @if(session('status'))<div class="notice">{{ session('status') }}</div>@endif
        @if($errors->any())<div class="errors"><ul>@foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul></div>@endif

        <header style="display:flex;align-items:flex-start;justify-content:space-between;gap:20px;margin-bottom:20px;flex-wrap:wrap">
            <div><div class="eyebrow">{{ $project->code }}</div><h1 class="page-title" style="margin:5px 0 6px">{{ $project->name }}</h1><p class="muted" style="max-width:72ch;margin:0">{{ $project->description ?: 'PCB fabrication project workspace' }}</p></div>
            <span class="badge {{ $statusColors[$project->status] ?? 'b-muted' }}">{{ str_replace('_',' ',$project->status) }}</span>
        </header>

        <div class="card" style="margin-bottom:20px"><div class="card-body"><div class="status-track">
            @foreach($stages as $index => $label)
                <div class="status-step {{ $statusIndex > $index ? 'done' : ($statusIndex === $index ? 'current' : '') }}">{{ $label }}</div>
            @endforeach
        </div></div></div>

        <div class="grid split">
            <!-- Main column -->
            <div class="stack">
                <!-- Files -->
                <div class="card">
                    <div class="card-head"><div><h2>Project files</h2><div class="muted" style="font-size:.78rem">Private storage · authorized downloads only</div></div></div>
                    <div class="card-body">
                        <div class="security-note"><span class="security-icon">S</span><div><b style="color:var(--cyan)">Private by default</b><div class="muted" style="font-size:.82rem">Downloads use short-lived signed links and are recorded in the project access log. ZIP files are checked for unsafe paths, expansion size and compression ratio before storage.</div></div></div>
                        <form method="post" action="/en/projects/{{ $project->id }}/files" enctype="multipart/form-data" style="margin-top:16px">
                            @csrf
                            <div class="form-grid">
                                <div class="field"><label for="file_type">Document type</label><select class="control" id="file_type" name="file_type" required><option value="gerber">Gerber ZIP</option><option value="bom">Bill of materials</option><option value="cpl">Component placement list</option><option value="schematic">Schematic</option><option value="pcb_source">PCB source</option><option value="assembly_drawing">Assembly drawing</option><option value="step">STEP model</option><option value="other">Other document</option></select></div>
                                <div class="field"><label for="file">Choose file</label><input class="control" id="file" type="file" name="file" required style="padding:8px"></div>
                            </div>
                            <div class="form-actions"><button class="btn btn-primary" type="submit">Upload private file</button></div>
                        </form>
                    </div>
                    @if($project->files->count())
                        <div class="table-wrap"><table class="table"><thead><tr><th>File</th><th>Type</th><th>Validation</th><th>Uploaded</th><th></th></tr></thead><tbody>
                        @foreach($project->files as $file)
                            @php $scan = $file->scanResults->first(); @endphp
                            <tr>
                                <td><span style="font-weight:700;word-break:break-word">{{ $file->filename_original }}</span><div class="muted" style="font-size:.78rem">{{ number_format($file->file_size / 1024, 1) }} KB</div></td>
                                <td><span class="badge b-muted">{{ str_replace('_',' ',$file->file_type) }}</span></td>
                                <td><div style="display:flex;gap:5px;flex-wrap:wrap"><span class="badge b-ok">Structure checked</span>@if($file->malware_scanned)<span class="badge b-ok">Malware scanned</span>@else<span class="badge b-warn">Scan pending</span>@endif</div></td>
                                <td style="font-size:.82rem;color:var(--muted)">{{ $file->created_at->format('M j, Y H:i') }}</td>
                                <td><a class="btn btn-ghost" href="{{ $downloadUrls[$file->id] }}">Download</a></td>
                            </tr>
                        @endforeach
                        </tbody></table></div>
                    @endif
                </div>

                <!-- Gerber Analysis -->
                @foreach($project->gerberAnalysisRuns as $run)
                    @include('pcb.partials.gerber-analysis', ['run' => $run])
                @endforeach

                <!-- Component Sourcing -->
                @include('pcb.partials.component-matches')

                <!-- Quote -->
                <div class="card">
                    <div class="card-head"><div><h2>Engineering quote</h2><div class="muted" style="font-size:.78rem">Board configuration and commercial approval</div></div>@if($quote)<span class="badge {{ $statusColors[$quote->status] ?? 'b-muted' }}">{{ $quote->status }}</span>@endif</div>
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
                                <details class="advanced" style="margin-top:14px"><summary>Testing and advanced fabrication</summary><div class="check-grid" style="padding-top:8px">
                                    @foreach(['aoi_testing'=>'AOI testing','electrical_test'=>'Electrical test','impedance_control'=>'Controlled impedance','blind_buried_vias'=>'Blind / buried vias','hdi'=>'HDI process','edge_plating'=>'Edge plating','castellated_holes'=>'Castellated holes'] as $name=>$label)
                                        <label class="check"><input type="checkbox" name="{{ $name }}" value="1" @checked(in_array($name,['aoi_testing','electrical_test'],true) || old($name) || $quote?->{$name})> {{ $label }}</label>
                                    @endforeach
                                </div></details>
                                <div class="form-actions"><button class="btn btn-primary" type="submit">Request engineering quote</button></div>
                            </form>
                        @elseif($quote->status === 'submitted')
                            <div class="notice"><b style="color:var(--cyan)">Engineering review in progress.</b> The board configuration and private files are waiting for manual review. No automatic pricing or manufacturing commitment has been made.</div>
                            <div class="spec-list">
                                <div><small>Board</small><span>{{ str_replace('_',' ',$quote->board_type) }} · {{ $quote->layer_count }} layers</span></div>
                                <div><small>Size</small><span>{{ $quote->length_mm }} × {{ $quote->width_mm }} mm</span></div>
                                <div><small>Quantity</small><span>{{ number_format($quote->quantity) }}</span></div>
                                <div><small>Finish</small><span>{{ str_replace('_',' ',$quote->surface_finish) }}</span></div>
                            </div>
                        @elseif($quote->status === 'quoted')
                            <div class="spec-list" style="margin-bottom:16px">
                                <div><small>Board</small><span>{{ str_replace('_',' ',$quote->board_type) }} · {{ $quote->layer_count }} layers</span></div>
                                <div><small>Quantity</small><span>{{ number_format($quote->quantity) }}</span></div>
                                <div><small>Lead time</small><span>{{ $quote->lead_time_days }} days after approval</span></div>
                                <div><small>Valid until</small><span>{{ $quote->quote_valid_until ? \Carbon\Carbon::parse($quote->quote_valid_until)->format('M j, Y') : 'Confirm with engineering' }}</span></div>
                            </div>
                            @if($quote->engineering_notes)<p style="color:var(--muted)">{{ $quote->engineering_notes }}</p>@endif
                            <table class="table"><tbody>
                                <tr><td>Fabrication</td><td style="font-weight:700;text-align:right">{{ $quote->currency }} {{ number_format($quote->total_fabrication_price,2) }}</td></tr>
                                <tr><td>Setup</td><td style="font-weight:700;text-align:right">{{ $quote->currency }} {{ number_format($quote->setup_charge,2) }}</td></tr>
                                <tr><td>Engineering</td><td style="font-weight:700;text-align:right">{{ $quote->currency }} {{ number_format($quote->engineering_charge,2) }}</td></tr>
                                @foreach($quote->lineItems as $item)<tr><td>{{ $item->description }}</td><td style="font-weight:700;text-align:right">{{ $item->currency }} {{ number_format($item->total_price,2) }}</td></tr>@endforeach
                            </tbody></table>
                            <div class="quote-total"><div><span class="muted" style="font-size:.78rem">Commercial total</span><strong>{{ $quote->currency }} {{ number_format($quote->total_price,2) }}</strong></div><div style="display:flex;gap:8px">
                                <details class="advanced"><summary class="btn btn-primary">Approve quote</summary><div style="padding-top:12px"><form method="post" action="/en/projects/{{ $project->id }}/quotes/{{ $quote->id }}/approve">@csrf<div class="field"><label for="customer_notes">Order note</label><textarea class="control" id="customer_notes" name="customer_notes"></textarea></div><div class="form-actions"><button class="btn btn-primary" type="submit">Approve and create order</button></div></form></div></details>
                            </div></div>
                            <details class="advanced" style="margin-top:14px"><summary>Request quote changes</summary><div style="padding-top:12px"><form method="post" action="/en/projects/{{ $project->id }}/quotes/{{ $quote->id }}/reject">@csrf<div class="field"><label for="change_notes">Required changes</label><textarea class="control" id="change_notes" name="customer_notes" required></textarea></div><div class="form-actions"><button class="btn btn-danger" type="submit">Send change request</button></div></form></div></details>
                        @elseif($quote->status === 'approved')
                            <div class="notice"><b style="color:var(--cyan)">Quote approved.</b> Order {{ $quote->order?->order_number }} is now in the shared NeoGiga order workflow. Payment and production start remain subject to manual confirmation.</div>
                            <div class="spec-list"><div><small>Order</small><span>{{ $quote->order?->order_number }}</span></div><div><small>Order status</small><span>{{ $quote->order?->status }}</span></div><div><small>Total</small><span>{{ $quote->currency }} {{ number_format($quote->total_price,2) }}</span></div><div><small>Payment</small><span>{{ $quote->order?->payment_status }}</span></div></div>
                        @endif
                    </div>
                </div>

                <!-- Activity -->
                <div class="card">
                    <div class="card-head"><h2>Project activity</h2></div>
                    <div class="card-body"><div class="timeline">
                        @forelse($project->activityLogs as $event)
                            <div class="timeline-item"><span class="timeline-dot"></span><div><p>{{ $event->description ?: str_replace('_',' ',$event->action) }}</p><time>{{ $event->created_at->format('M j, Y H:i') }}</time></div></div>
                        @empty<p class="muted">No activity recorded.</p>@endforelse
                    </div></div>
                </div>
            </div>

            <!-- Sidebar -->
            <aside class="stack">
                @php $activeOrder = $quote && $quote->status === 'approved' ? $quote->order : null; @endphp
                @if($activeOrder)
                    @include('pcb.partials.order-tracking', ['order' => $activeOrder])
                @endif

                <div class="card"><div class="card-head"><h2>Project summary</h2></div><div class="card-body"><div class="spec-list">
                    <div><small>Type</small><span>{{ ucfirst($project->project_type) }}</span></div>
                    <div><small>Quantity</small><span>{{ number_format($project->target_quantity) }}</span></div>
                    <div><small>Destination</small><span>{{ $project->destination_country }}</span></div>
                    <div><small>Required date</small><span>{{ $project->required_date?->format('M j, Y') ?: 'Flexible' }}</span></div>
                    <div><small>Confidentiality</small><span>{{ str_replace('_',' ',$project->confidentiality) }}</span></div>
                    <div><small>Currency</small><span>{{ $project->currency }}</span></div>
                </div></div></div>

                @if(in_array($project->status,['draft','requirements_pending','files_ready','quote_pending'],true))
                <details class="advanced"><summary>Edit project requirements</summary><div style="padding-top:12px"><form method="post" action="/en/projects/{{ $project->id }}">@csrf @method('PATCH')<div class="form-grid">
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

                <div class="card"><div class="card-head"><h2>Engineering support</h2></div><div class="card-body"><p class="muted" style="font-size:.86rem">Reference project code <b style="color:var(--on)">{{ $project->code }}</b> when contacting the PCB engineering desk.</p><a class="btn btn-ghost" href="mailto:pcb@neogiga.com?subject={{ urlencode($project->code.' '.$project->name) }}">Contact engineering</a></div></div>

                @if(in_array($project->status,['draft','requirements_pending','files_ready','quote_pending','quoted'],true))
                <div class="card danger-zone"><div class="card-head"><h2>Cancel project</h2></div><div class="card-body"><p class="muted" style="font-size:.84rem">Cancelling stops the active workflow. Files remain retained for audit and recovery.</p><form method="post" action="/en/projects/{{ $project->id }}/cancel">@csrf<button class="btn btn-danger" type="submit">Cancel project</button></form></div></div>
                @endif
            </aside>
        </div>
    </div>
</section>
@endsection
