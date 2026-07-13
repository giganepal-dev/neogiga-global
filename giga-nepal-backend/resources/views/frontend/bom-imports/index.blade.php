@extends('frontend.layout')
@section('title', 'BOM Uploader | NeoGiga')
@section('description', 'Upload a CSV, TSV, TXT, or pasted bill of materials to match manufacturer part numbers against the NeoGiga catalog and prepare an RFQ.')

@section('content')
@php
    $publicBase = '/'.$localePrefix;
    $badgeClass = fn (string $status) => match ($status) {
        'exact', 'manual', 'matched', 'converted' => 'b-ok',
        'multiple' => 'b-warn',
        default => 'b-muted',
    };
@endphp
<section class="section" style="padding-top:22px">
    <div class="wrap">
        <nav class="crumbs" aria-label="Breadcrumb"><a href="{{ $publicBase }}">Home</a><span>/</span><strong style="color:var(--soft)">BOM Uploader</strong></nav>
        <div class="section-head"><div><p class="eyebrow">Procurement workspace</p><h1 style="font-size:clamp(2rem,4vw,3.2rem);margin:6px 0 10px">Upload a bill of materials</h1><p class="lead" style="margin:0">Match MPNs against the NeoGiga catalog, review the result, then hand matched and unmatched lines to sourcing through RFQ.</p></div></div>

        @if(session('status'))<div class="notice">{{ session('status') }}</div>@endif

        @guest
            <section class="panel" style="padding:28px;max-width:760px">
                <h2 style="margin-top:0">Sign in to upload a BOM</h2>
                <p class="sub">BOM uploads are saved to the account that submits them so catalog matches and RFQ handoff remain private.</p>
                <a class="btn btn-primary" href="/admin/login">B2B Login</a>
            </section>
        @else
            <div class="layout-2" style="grid-template-columns:minmax(0,1.4fr) minmax(260px,.6fr)">
                <form class="panel" method="post" action="{{ route('localized.bom-imports.store', ['localePrefix' => $localePrefix]) }}" enctype="multipart/form-data" style="padding:24px">
                    @csrf
                    <h2 style="margin-top:0">New BOM import</h2>
                    <div class="grid" style="grid-template-columns:repeat(auto-fit,minmax(220px,1fr))">
                        <label class="field"> <span>Name</span><input class="control" name="name" value="{{ old('name') }}" maxlength="160" placeholder="Motor controller BOM" required></label>
                        <label class="field"> <span>Currency</span><select class="control" name="currency"><option value="USD" @selected(old('currency', 'USD') === 'USD')>USD</option><option value="INR" @selected(old('currency') === 'INR')>INR</option><option value="NPR" @selected(old('currency') === 'NPR')>NPR</option></select></label>
                    </div>
                    <label class="field"><span>CSV, TSV, or TXT file</span><input class="control" type="file" name="file" accept=".csv,.tsv,.txt,text/csv,text/plain"></label>
                    <label class="field"><span>Or paste BOM lines</span><textarea class="control" name="content" rows="10" placeholder="MPN, Manufacturer, Quantity&#10;ESP32-WROOM-32, Espressif, 2&#10;LM2596S-ADJ, Texas Instruments, 5">{{ old('content') }}</textarea></label>
                    @error('name')<p class="sub" style="color:#ff9d9d">{{ $message }}</p>@enderror
                    @error('file')<p class="sub" style="color:#ff9d9d">{{ $message }}</p>@enderror
                    @error('content')<p class="sub" style="color:#ff9d9d">{{ $message }}</p>@enderror
                    <button class="btn btn-primary" type="submit">Upload and Match BOM</button>
                </form>
                <aside class="panel" style="padding:24px"><h2 style="margin-top:0;font-size:1.1rem">Supported flow</h2><ol class="sub" style="padding-left:20px;display:grid;gap:10px"><li>Upload or paste a parts list.</li><li>Match normalized MPNs to the global catalog.</li><li>Review exact and ambiguous matches.</li><li>Convert the BOM to an RFQ when ready.</li></ol><p class="sub" style="margin-bottom:0">CSV, TSV, TXT and pasted text are accepted. Each upload is scoped to your account.</p></aside>
            </div>

            @if($selectedImport)
                <section class="panel" style="padding:24px;margin-top:28px">
                    <div class="section-head" style="margin-bottom:14px"><div><p class="eyebrow">Match result</p><h2 style="margin:4px 0">{{ $selectedImport->name }}</h2><p class="sub" style="margin:0">{{ $selectedImport->total_lines }} lines · {{ strtoupper($selectedImport->currency) }} · {{ $selectedImport->source_format }}</p></div><span class="badge {{ $badgeClass($selectedImport->status) }}">{{ $selectedImport->status }}</span></div>
                    <div class="grid" style="grid-template-columns:repeat(auto-fit,minmax(150px,1fr));margin-bottom:18px"><div class="info-card"><strong>{{ $selectedImport->total_lines }}</strong><div class="sub">total lines</div></div><div class="info-card"><strong>{{ $selectedImport->matched_lines }}</strong><div class="sub">matched</div></div><div class="info-card"><strong>{{ $selectedImport->unmatched_lines }}</strong><div class="sub">need sourcing</div></div></div>
                    <div style="overflow-x:auto"><table class="spec-table"><thead><tr><th>Line</th><th>MPN / Description</th><th>Qty</th><th>Match</th><th>Catalog product</th></tr></thead><tbody>@foreach($selectedImport->lines as $line)<tr><td>{{ $line->line_no }}</td><td><strong>{{ $line->mpn ?: $line->description ?: 'Unspecified part' }}</strong>@if($line->manufacturer)<br><span class="sub">{{ $line->manufacturer }}</span>@endif</td><td>{{ number_format((float) $line->quantity, 3) }}</td><td><span class="badge {{ $badgeClass($line->match_status) }}">{{ $line->match_status }} · {{ $line->match_confidence }}%</span></td><td>@if($line->matchedProduct)<a href="{{ $publicBase }}/products/{{ $line->matchedProduct->slug }}">{{ $line->matchedProduct->sku }}</a>@else<span class="sub">RFQ sourcing</span>@endif</td></tr>@endforeach</tbody></table></div>
                </section>
            @endif

            @if($imports->isNotEmpty())
                <section style="margin-top:28px"><h2 style="font-size:1.2rem">Recent BOM uploads</h2><div class="grid" style="grid-template-columns:repeat(auto-fit,minmax(240px,1fr))">@foreach($imports as $import)<a class="info-card" href="{{ route('localized.bom-imports', ['localePrefix' => $localePrefix, 'import' => $import->id]) }}"><strong>{{ $import->name }}</strong><p class="sub">{{ $import->lines_count }} lines · {{ $import->matched_lines }} matched</p><span class="badge {{ $badgeClass($import->status) }}">{{ $import->status }}</span></a>@endforeach</div></section>
            @endif
        @endguest
    </div>
</section>
@endsection
