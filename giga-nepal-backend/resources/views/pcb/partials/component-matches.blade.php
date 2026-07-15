{{-- Component Sourcing — expects $project with componentMatches loaded --}}
@php
    $confidenceBadge = fn($c) => match($c) {
        'exact' => 'b-ok', 'high' => 'b-ok', 'medium' => 'b-info', 'low' => 'b-warn', default => 'b-muted'
    };
    $matches = $project->componentMatches ?? collect();
    $exact = $matches->where('match_confidence', 'exact')->count();
    $partial = $matches->whereIn('match_confidence', ['high', 'medium'])->count();
    $noMatch = $matches->where('match_confidence', 'no_match')->count();
@endphp

<div class="card" style="margin-bottom:16px">
    <div class="card-head">
        <div><h2>Component sourcing</h2><div class="muted" style="font-size:.78rem">BOM components matched against NeoGiga catalog</div></div>
        <div style="display:flex;gap:6px">
            @if($exact > 0)<span class="badge b-ok">{{ $exact }} matched</span>@endif
            @if($partial > 0)<span class="badge b-info">{{ $partial }} partial</span>@endif
            @if($noMatch > 0)<span class="badge b-danger">{{ $noMatch }} unmatched</span>@endif
        </div>
    </div>
    <div class="card-body">
        @if($matches->isEmpty())
            <div class="empty"><strong>No components matched yet</strong><p>Upload a BOM or CPL file to automatically match components against the NeoGiga product catalog. Matched components can be sourced directly.</p></div>
        @else
            <div class="table-wrap"><table class="table">
                <thead><tr><th>MPN / Designator</th><th>Catalog match</th><th>Manufacturer</th><th>Confidence</th><th>Status</th></tr></thead>
                <tbody>
                @foreach($matches->take(50) as $match)
                    <tr>
                        <td>
                            <span style="font-weight:700;font-family:ui-monospace,monospace;font-size:.82rem">{{ $match->requested_mpn ?: '—' }}</span>
                            @if($match->requested_description)<div class="muted" style="font-size:.74rem">{{ $match->requested_description }}</div>@endif
                        </td>
                        <td>
                            @if($match->matched_product_id)
                                <a href="https://neogiga.com/en/products/{{ $match->matched_product_id }}" target="_blank" rel="noopener" style="color:var(--cyan);font-weight:600;font-size:.84rem">
                                    {{ $match->matched_mpn ?: 'View product' }}
                                </a>
                            @else
                                <span class="muted">—</span>
                            @endif
                        </td>
                        <td style="font-size:.82rem">{{ $match->matched_manufacturer ?: '—' }}</td>
                        <td><span class="badge {{ $confidenceBadge($match->match_confidence) }}">{{ $match->match_confidence }}</span></td>
                        <td>
                            @if($match->customer_approved)
                                <span class="badge b-ok">Approved</span>
                            @elseif($match->match_confidence === 'no_match')
                                <span class="badge b-danger">Needs sourcing</span>
                            @else
                                <span class="badge b-warn">Review</span>
                            @endif
                        </td>
                    </tr>
                @endforeach
                </tbody>
            </table></div>
            @if($matches->count() > 50)
                <p class="muted" style="text-align:center;font-size:.82rem;margin-top:8px">Showing 50 of {{ $matches->count() }} components.</p>
            @endif
        @endif
    </div>
</div>
