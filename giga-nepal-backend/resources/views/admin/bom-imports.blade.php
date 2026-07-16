@extends('admin.layout')
@section('title','BOM Imports')
@section('crumb','BOM procurement imports')
@section('page_actions')
    <a class="btn btn-ghost" href="/admin/rfqs">RFQ Inbox</a>
    <a class="btn btn-ghost" href="/admin/procurement">Procurement</a>
@endsection
@section('content')

@php
    $badge = fn($s) => match($s) { 'converted' => 'b-ok', 'matched' => 'b-info', 'parsed' => 'b-muted', default => 'b-warn' };
    $lineBadge = fn($s) => match($s) { 'exact', 'manual' => 'b-ok', 'multiple' => 'b-warn', default => 'b-muted' };
    $statuses = ['parsed','matched','converted'];
@endphp

<div class="grid kpis">
    <div class="kpi"><div class="t">BOM Imports</div><div class="v tnum">{{ number_format($stats['total']) }}</div><div class="s">uploaded or pasted BOMs</div></div>
    <div class="kpi"><div class="t">Matched</div><div class="v tnum">{{ number_format($stats['matched']) }}</div><div class="s">ready for review/RFQ</div></div>
    <div class="kpi"><div class="t">Converted</div><div class="v tnum">{{ number_format($stats['converted']) }}</div><div class="s">RFQ generated</div></div>
    <div class="kpi"><div class="t">Unmatched Lines</div><div class="v tnum">{{ number_format($stats['openLines']) }}</div><div class="s">need manual match or supplier quote</div></div>
</div>

<section class="card">
    <div class="card-h">
        <div>
            <h2>BOM Procurement Queue</h2>
            <div class="sub">Customer BOM uploads are parsed, matched by normalized MPN, then converted into RFQs.</div>
        </div>
        <span class="badge b-info">API: /api/v1/bom/imports</span>
    </div>
    <form class="filters" method="get">
        <input class="control" name="q" value="{{ request('q') }}" placeholder="Search BOM name, customer email, RFQ number">
        <select class="control" name="status">
            <option value="">All statuses</option>
            @foreach($statuses as $s)
                <option value="{{ $s }}" @selected($statusFilter===$s)>{{ ucfirst($s) }}</option>
            @endforeach
        </select>
        <button class="btn btn-ghost" type="submit">Filter</button>
    </form>

    <div class="scroll-x"><table class="tbl">
        <thead><tr><th>BOM</th><th>Customer</th><th class="num">Lines</th><th class="num">Matched</th><th>Status</th><th>RFQ</th><th>Actions</th><th>Created</th></tr></thead>
        <tbody>
        @forelse($imports as $import)
            <tr>
                <td>
                    <strong>{{ $import->name }}</strong>
                    <div class="sub mono">#{{ $import->id }} · {{ strtoupper($import->currency ?? 'USD') }} · {{ $import->source_format }}</div>
                </td>
                <td>{{ $import->user_name ?? '—' }}<div class="sub">{{ $import->user_email ?? 'guest/API' }}</div></td>
                <td class="num tnum">{{ number_format($import->total_lines) }}</td>
                <td class="num tnum">{{ number_format($import->matched_lines) }} / {{ number_format($import->unmatched_lines) }}</td>
                <td><span class="badge {{ $badge($import->status) }}">{{ $import->status }}</span></td>
                <td>
                    @if($import->rfq_request_id)
                        <a class="btn btn-ghost" href="/admin/rfqs/{{ $import->rfq_request_id }}">{{ $import->rfq_number ?? ('RFQ #'.$import->rfq_request_id) }}</a>
                    @else
                        <span class="badge b-muted">not converted</span>
                    @endif
                </td>
                <td>
                    @if($import->status !== 'converted')
                        <form method="post" action="/admin/bom-imports/{{ $import->id }}/rematch">@csrf
                            <button class="btn btn-ghost" type="submit" onclick="return confirm('Re-run catalog matching for this BOM? Manual line decisions will be preserved.')">Rematch</button>
                        </form>
                    @else
                        <span class="badge b-muted">RFQ history locked</span>
                    @endif
                </td>
                <td class="sub">{{ \Illuminate\Support\Carbon::parse($import->created_at)->format('Y-m-d H:i') }}</td>
            </tr>
            <tr>
                <td colspan="8" style="background:#fbfdff;padding:0">
                    <div class="scroll-x">
                        <table class="tbl" style="font-size:.82rem">
                            <thead><tr><th>Line</th><th>Ref</th><th>MPN</th><th>Manufacturer</th><th class="num">Qty</th><th>Match</th><th>Product</th><th>Review action</th></tr></thead>
                            <tbody>
                            @foreach(($lines[$import->id] ?? collect())->take(8) as $line)
                                <tr>
                                    <td class="mono">{{ $line->line_no }}</td>
                                    <td>{{ $line->raw_reference ?? '—' }}</td>
                                    <td class="mono">{{ $line->mpn ?? '—' }}</td>
                                    <td>{{ $line->manufacturer ?? '—' }}</td>
                                    <td class="num tnum">{{ number_format((float) $line->quantity, 3) }}</td>
                                    <td><span class="badge {{ $lineBadge($line->match_status) }}">{{ $line->match_status }} · {{ $line->match_confidence }}%</span></td>
                                    <td>
                                        @if($line->matched_product_id)
                                            <a href="/admin/products/{{ $line->matched_product_id }}"><strong>{{ $line->matched_product_sku ?? ('#'.$line->matched_product_id) }}</strong></a>
                                            <div class="sub">{{ $line->matched_product_name ?? '' }}</div>
                                        @else
                                            <span class="badge b-warn">supplier quote needed</span>
                                        @endif
                                    </td>
                                    <td>
                                        @if($import->status !== 'converted')
                                            @php
                                                $rawCandidates = is_array($line->candidates)
                                                    ? $line->candidates
                                                    : json_decode((string) ($line->candidates ?? '[]'), true);
                                                $candidateIds = collect(is_array($rawCandidates) ? $rawCandidates : [])
                                                    ->pluck('product_id')
                                                    ->map(static fn ($id): int => (int) $id)
                                                    ->filter(static fn (int $id): bool => $id > 0)
                                                    ->values();
                                            @endphp
                                            <details class="modal">
                                                <summary class="btn btn-ghost">Assign</summary>
                                                <div class="modal-panel">
                                                    <div class="modal-h"><h3>Set BOM Line Match</h3><span class="badge b-info">published catalog only</span></div>
                                                    <form class="modal-b form-stack" method="post" action="/admin/bom-imports/{{ $import->id }}/lines/{{ $line->id }}/match">@csrf
                                                        <label>Published product ID
                                                            <input class="control" type="number" min="1" name="matched_product_id" value="{{ $line->matched_product_id }}" list="bom-candidates-{{ $line->id }}" placeholder="Leave blank for supplier RFQ">
                                                        </label>
                                                        <datalist id="bom-candidates-{{ $line->id }}">
                                                            @foreach($candidateIds as $candidateId)
                                                                @php($candidate = $candidateProducts->get($candidateId))
                                                                @if($candidate)
                                                                    <option value="{{ $candidate->id }}" label="{{ $candidate->sku }} · {{ $candidate->name }}"></option>
                                                                @endif
                                                            @endforeach
                                                        </datalist>
                                                        @if($candidateIds->isNotEmpty())
                                                            <div class="sub">Candidates: @foreach($candidateIds as $candidateId) @php($candidate = $candidateProducts->get($candidateId)) @if($candidate)<a href="/admin/products/{{ $candidate->id }}">{{ $candidate->sku }}</a>@if(! $loop->last), @endif @endif @endforeach</div>
                                                        @endif
                                                        <div class="sub">Candidate options come from the original MPN match. The entered product is checked again against the current public catalog.</div>
                                                        <button class="btn btn-primary" type="submit">Save Match</button>
                                                    </form>
                                                </div>
                                            </details>
                                        @else
                                            <span class="sub">Converted to RFQ</span>
                                        @endif
                                    </td>
                                </tr>
                            @endforeach
                            @if(($lines[$import->id] ?? collect())->count() > 8)
                                <tr><td colspan="8" class="sub">Showing first 8 lines of {{ ($lines[$import->id] ?? collect())->count() }}.</td></tr>
                            @endif
                            </tbody>
                        </table>
                    </div>
                </td>
            </tr>
        @empty
            <tr><td colspan="8"><div class="empty"><h3>No BOM imports yet</h3><p>Customer BOM uploads through the authenticated BOM API will appear here.</p></div></td></tr>
        @endforelse
        </tbody>
    </table></div>
    @if($imports->hasPages())
        <div style="padding:12px 16px;border-top:1px solid var(--line)">{{ $imports->links() }}</div>
    @endif
</section>

<div class="note" style="margin-top:16px">Manual assignment and rematching use the existing BOM importer. Only published catalog products can be assigned, and converted BOMs remain locked to preserve their RFQ history.</div>

@endsection
