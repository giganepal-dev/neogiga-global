{{-- Gerber Analysis Results — expects $run (PcbGerberAnalysisRun) --}}
@php
    $severityClass = fn($s) => match($s) {
        'blocking' => 'b-danger',
        'warning' => 'b-warn',
        'info' => 'b-info',
        default => 'b-muted'
    };
    $layerColors = [
        'top_copper' => '#ef4444', 'bottom_copper' => '#3b82f6', 'inner_copper' => '#8b5cf6',
        'top_solder_mask' => '#10b981', 'bottom_solder_mask' => '#059669',
        'top_silkscreen' => '#f9bd2c', 'bottom_silkscreen' => '#d97706',
        'top_paste' => '#94a3b8', 'bottom_paste' => '#64748b',
        'board_outline' => '#f8fafc', 'drill' => '#ec4899', 'mechanical' => '#6b7280', 'unknown' => '#4b5563',
    ];
@endphp

<div class="card" style="margin-bottom:16px">
    <div class="card-head">
        <div><h2>Gerber analysis</h2><div class="muted" style="font-size:.78rem">
            Parser {{ $run->parser_version }} · {{ ucfirst($run->confidence_level) }} confidence
            · {{ $run->created_at->diffForHumans() }}
        </div></div>
        <div style="display:flex;align-items:center;gap:8px;flex-wrap:wrap"><span class="badge {{ $run->status === 'completed' ? 'b-ok' : ($run->status === 'failed' ? 'b-danger' : 'b-warn') }}">{{ $run->status }}</span>@if($run->file && $project->canBeEditedBy(auth()->user()))<form method="post" action="/en/projects/{{ $project->id }}/gerber/{{ $run->file->id }}/analyze">@csrf<button type="submit" class="btn btn-ghost" style="min-height:32px;padding:0 10px;font-size:.75rem">Refresh analysis</button></form>@endif</div>
    </div>
    <div class="card-body">
        @if($run->detectedLayers->count())
            <h3 style="font-size:.9rem;margin:0 0 12px;color:var(--muted)">Detected layers</h3>
            <div style="display:flex;flex-wrap:wrap;gap:6px;margin-bottom:16px">
                @foreach($run->detectedLayers as $layer)
                    <div style="display:flex;align-items:center;gap:7px;padding:6px 10px;border:1px solid var(--line);border-radius:8px;font-size:.78rem;background:rgba(255,255,255,.02)">
                        <span style="width:10px;height:10px;border-radius:3px;background:{{ $layerColors[$layer->detected_type] ?? '#4b5563' }};flex:none"></span>
                        <span style="font-weight:600;color:var(--on)">{{ $layer->filename }}</span>
                        <span class="badge {{ $layer->is_matched ? 'b-ok' : 'b-warn' }}" style="font-size:.65rem">{{ str_replace('_',' ',$layer->detected_type) }}</span>
                    </div>
                @endforeach
            </div>
        @endif

        @if($run->detected_width_mm || $run->detected_hole_count)
            <div class="spec-list" style="margin-bottom:12px">
                @if($run->detected_width_mm)<div><small>Board size (est.)</small><span>{{ $run->detected_width_mm }} × {{ $run->detected_height_mm }} mm</span></div>@endif
                @if($run->detected_layer_count)<div><small>Copper layers</small><span>{{ $run->detected_layer_count }}</span></div>@endif
                @if($run->detected_hole_count)<div><small>Drill holes</small><span>{{ $run->detected_hole_count }}</span></div>@endif
                @if($run->detected_board_area_cm2)<div><small>Board area</small><span>{{ $run->detected_board_area_cm2 }} cm²</span></div>@endif
            </div>
        @endif

        @php $blocking = $run->warnings->where('severity','blocking'); $others = $run->warnings->where('severity','!=','blocking'); @endphp
        @if($blocking->count())
            @foreach($blocking as $w)
                <div style="padding:10px 12px;background:rgba(239,68,68,.08);border:1px solid rgba(239,68,68,.2);border-radius:8px;margin-bottom:6px">
                    <span class="badge b-danger" style="font-size:.65rem;margin-right:6px">{{ $w->warning_code }}</span>
                    <span style="color:#f87171;font-size:.84rem">{{ $w->message }}</span>
                </div>
            @endforeach
        @endif
        @if($others->count())
            <details style="margin-top:8px"><summary style="color:var(--faint);font-size:.78rem;cursor:pointer">{{ $others->count() }} warning(s) / info</summary>
                <div style="margin-top:8px;display:grid;gap:4px">
                    @foreach($others as $w)
                        <div style="padding:8px 10px;border:1px solid var(--line);border-radius:7px;font-size:.82rem;display:flex;align-items:flex-start;gap:8px">
                            <span class="badge {{ $severityClass($w->severity) }}" style="font-size:.65rem;flex:none">{{ $w->warning_code }}</span>
                            <span style="color:var(--muted)">{{ $w->message }}</span>
                        </div>
                    @endforeach
                </div>
            </details>
        @endif

        @if(!$run->detectedLayers->count() && !$run->warnings->count())
            <p class="muted">Analysis completed with no issues detected.</p>
        @endif
    </div>
</div>
