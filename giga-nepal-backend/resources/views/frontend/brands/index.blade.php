@extends('frontend.layout')
@section('title', 'Electronic Component Brands & Manufacturers | NeoGiga')
@section('description', 'Explore verified semiconductor, electronics, IoT, robotics, sensor, battery and engineering brands available through NeoGiga.')

@push('head')
<script nonce="{{ $csp_nonce ?? '' }}" type="application/ld+json">
{!! json_encode([
    '@context' => 'https://schema.org',
    '@type' => 'ItemList',
    'name' => 'NeoGiga electronic component brands & manufacturers',
    'numberOfItems' => $totalBrands,
    'itemListElement' => collect($brands)->take(200)->values()->map(fn ($brand, $index) => [
        '@type' => 'ListItem',
        'position' => $index + 1,
        'name' => $brand->name,
        'url' => url($publicBase.'/brand/'.$brand->slug),
    ])->all(),
], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) !!}
</script>
<script nonce="{{ $csp_nonce ?? '' }}" type="application/ld+json">
{!! json_encode([
    '@context' => 'https://schema.org',
    '@type' => 'BreadcrumbList',
    'itemListElement' => [
        ['@type' => 'ListItem', 'position' => 1, 'name' => 'Home', 'item' => url($publicBase)],
        ['@type' => 'ListItem', 'position' => 2, 'name' => 'Brands', 'item' => url($publicBase.'/brands')],
    ],
], JSON_UNESCAPED_SLASHES) !!}
</script>
@endpush

@section('content')
<style nonce="{{ $csp_nonce ?? '' }}">
    .brand-dir{padding:24px 0 48px}
    .brand-dir .crumbs{margin:0 0 12px}
    .brand-dir-head{display:flex;align-items:flex-start;justify-content:space-between;gap:16px;flex-wrap:wrap;margin-bottom:18px}
    .brand-dir-head h1{font-size:clamp(1.4rem,3vw,1.9rem);margin:0 0 6px;letter-spacing:-.02em;line-height:1.1}
    .brand-dir-head p{margin:0;color:var(--muted);max-width:62ch;font-size:.92rem}
    .brand-badge{display:inline-flex;align-items:center;gap:6px;padding:6px 12px;border:1px solid var(--line);border-radius:999px;background:var(--s1);color:var(--muted);font-size:.78rem;font-weight:700;white-space:nowrap}
    .brand-badge b{color:var(--cyan);font-variant-numeric:tabular-nums}
    .brand-toolbar{display:flex;gap:10px;flex-wrap:wrap;align-items:center;margin-bottom:12px}
    .brand-search{position:relative;flex:1 1 240px}
    .brand-search input{width:100%;min-height:44px;border:1px solid var(--line);border-radius:10px;background:var(--s1);color:var(--on);padding:0 14px 0 40px;font:inherit}
    .brand-search input:focus{outline:0;border-color:var(--cyan);box-shadow:0 0 0 3px rgba(15,98,230,.12)}
    .brand-search svg{position:absolute;left:13px;top:50%;transform:translateY(-50%);color:var(--faint)}
    .brand-sort{min-height:44px;border:1px solid var(--line);border-radius:10px;background:var(--s1);color:var(--on);padding:0 12px;font:inherit}
    .alpha{display:flex;gap:4px;overflow-x:auto;padding:8px 0;margin-bottom:16px;position:sticky;top:64px;z-index:6;background:var(--bg);scrollbar-width:thin}
    .alpha a{flex:none;min-width:34px;height:34px;display:grid;place-items:center;border-radius:8px;border:1px solid var(--line);color:var(--muted);font-size:.82rem;font-weight:700;background:var(--s1);transition:.12s}
    .alpha a:hover{border-color:var(--cyan);color:var(--cyan)}
    .alpha a.active{background:var(--cyan);color:#fff;border-color:transparent}
    .alpha a.is-disabled{opacity:.3;pointer-events:none}
    .brand-grid{display:grid;grid-template-columns:repeat(auto-fill,minmax(152px,1fr));gap:12px}
    .brand-card{display:flex;flex-direction:column;align-items:center;gap:8px;text-align:center;padding:16px 12px;border:1px solid var(--line);border-radius:12px;background:var(--s1);transition:border-color .15s,box-shadow .15s,transform .15s;content-visibility:auto;contain-intrinsic-size:auto 158px}
    .brand-card:hover{border-color:var(--cyan);box-shadow:0 8px 22px rgba(23,43,77,.1);transform:translateY(-2px)}
    .brand-card:focus-visible{outline:2px solid var(--cyan);outline-offset:2px}
    .brand-logo{width:100%;height:56px;display:grid;place-items:center}
    .brand-logo img{max-height:56px;max-width:100%;object-fit:contain}
    .brand-initials{width:46px;height:46px;border-radius:11px;background:var(--s3,#eef1f6);color:var(--cyan);display:grid;place-items:center;font-weight:800;font-size:1rem;letter-spacing:.02em}
    .brand-name{font-size:.86rem;font-weight:700;color:var(--on);line-height:1.25;display:-webkit-box;-webkit-line-clamp:2;-webkit-box-orient:vertical;overflow:hidden}
    .brand-count{font-size:.71rem;color:var(--faint);font-family:ui-monospace,SFMono-Regular,Menlo,monospace}
    .brand-empty{display:none;padding:48px 20px;text-align:center;color:var(--muted)}
    .brand-empty.show{display:block}
    .brand-empty svg{color:var(--faint);margin-bottom:10px}
    @media(max-width:640px){.brand-grid{grid-template-columns:repeat(2,minmax(0,1fr))}.alpha{top:56px}.brand-dir-head{gap:10px}}
</style>

@php($publicBase = $publicBase ?? '/'.($marketplaceContext['locale'] ?? 'en'))
<div class="wrap brand-dir">
    <nav class="crumbs" aria-label="Breadcrumb"><a href="{{ $publicBase }}">Home</a><span><x-icon name="chevron-right" size="12"/></span><strong>Brands</strong></nav>

    <div class="brand-dir-head">
        <div>
            <h1>Brands &amp; Manufacturers</h1>
            <p>Explore electronic component manufacturers and engineering brands available through NeoGiga.</p>
        </div>
        <span class="brand-badge"><x-icon name="brands" size="14"/> <b id="brand-shown-count">{{ number_format($totalBrands) }}</b>&nbsp;verified brands</span>
    </div>

    @if($totalBrands === 0)
        <div class="panel" style="padding:32px"><h2 class="section-title" style="font-size:1.2rem">Brands are being configured</h2><p class="sub">Browse the catalog or request a sourced part through RFQ.</p><a class="btn btn-primary" href="{{ $publicBase }}/products">Browse products</a></div>
    @else
        <div class="brand-toolbar">
            <label class="brand-search">
                <span style="position:absolute;left:-9999px">Search brands or manufacturers</span>
                <x-icon name="search" size="18"/>
                <input id="brand-search-input" type="search" autocomplete="off" placeholder="Search brands or manufacturers" aria-label="Search brands or manufacturers" value="{{ $searchQuery }}">
            </label>
            <select id="brand-sort" class="brand-sort" aria-label="Sort brands">
                <option value="az" @selected($activeSort==='az')>A–Z</option>
                <option value="za" @selected($activeSort==='za')>Z–A</option>
                <option value="products" @selected($activeSort==='products')>Most Products</option>
                <option value="recent" @selected($activeSort==='recent')>Recently Added</option>
            </select>
        </div>

        <nav id="brand-alpha" class="alpha" aria-label="Filter brands by first letter">
            <a href="{{ $publicBase }}/brands" data-letter="all" class="{{ $activeLetter==='ALL' ? 'active':'' }}">All</a>
            @foreach(array_merge(['0-9'], range('A','Z')) as $L)
                @php($has = in_array($L, $availableLetters, true))
                <a href="{{ $publicBase }}/brands?letter={{ $L }}" data-letter="{{ $L }}"
                   class="{{ $activeLetter===$L ? 'active':'' }} {{ $has ? '' : 'is-disabled' }}"
                   @if(!$has) aria-disabled="true" tabindex="-1" @endif>{{ $L==='0-9' ? '0–9' : $L }}</a>
            @endforeach
        </nav>

        <div id="brand-grid" class="brand-grid">
            @foreach($brands as $brand)
                @php($c = mb_strtoupper(mb_substr(trim((string) $brand->name), 0, 1)))
                @php($bucket = ($c >= '0' && $c <= '9') ? '0-9' : (($c >= 'A' && $c <= 'Z') ? $c : '0-9'))
                <a class="brand-card" href="{{ $publicBase }}/brand/{{ $brand->slug }}"
                   data-letter="{{ $bucket }}"
                   data-name="{{ mb_strtolower($brand->name) }}"
                   data-search="{{ mb_strtolower($brand->name.' '.$brand->slug) }}"
                   data-count="{{ (int) $brand->public_products_count }}"
                   data-added="{{ optional($brand->created_at)->getTimestamp() }}">
                    <span class="brand-logo">
                        @if($brand->logo_path)
                            <img src="{{ $brand->logo_path }}" alt="{{ $brand->name }} logo" loading="lazy" width="120" height="48">
                        @else
                            <span class="brand-initials" aria-hidden="true">{{ mb_strtoupper(mb_substr($brand->name, 0, 2)) }}</span>
                        @endif
                    </span>
                    <span class="brand-name">{{ $brand->name }}</span>
                    <span class="brand-count">{{ number_format($brand->public_products_count) }} products</span>
                </a>
            @endforeach
        </div>

        <div id="brand-empty" class="brand-empty">
            <x-icon name="search" size="34"/>
            <h3 style="margin:0 0 4px;color:var(--on)">No brands found</h3>
            <p style="margin:0">Try a different name, or clear the filter to see all brands.</p>
        </div>
    @endif
</div>

<script nonce="{{ $csp_nonce ?? '' }}">
(function () {
    var grid = document.getElementById('brand-grid');
    if (!grid) return;
    var cards = Array.prototype.slice.call(grid.querySelectorAll('.brand-card'));
    var search = document.getElementById('brand-search-input');
    var sortSel = document.getElementById('brand-sort');
    var alpha = document.getElementById('brand-alpha');
    var empty = document.getElementById('brand-empty');
    var counter = document.getElementById('brand-shown-count');

    function params() { return new URLSearchParams(location.search); }
    var letter = (params().get('letter') || 'all').toUpperCase();
    var q = (params().get('q') || '').toLowerCase();

    function apply() {
        var shown = 0;
        for (var i = 0; i < cards.length; i++) {
            var card = cards[i];
            // Search spans every letter; the letter filter applies only when not searching.
            var okLetter = q !== '' || letter === 'ALL' || card.getAttribute('data-letter') === letter;
            var okSearch = q === '' || card.getAttribute('data-search').indexOf(q) !== -1;
            var visible = okLetter && okSearch;
            card.style.display = visible ? '' : 'none';
            if (visible) shown++;
        }
        if (empty) empty.classList.toggle('show', shown === 0);
        if (counter) counter.textContent = shown.toLocaleString();
        if (alpha) {
            var mark = q !== '' ? '__searching__' : letter;
            Array.prototype.forEach.call(alpha.querySelectorAll('a'), function (a) {
                a.classList.toggle('active', (a.getAttribute('data-letter') || '').toUpperCase() === mark);
            });
        }
    }

    if (alpha) {
        alpha.addEventListener('click', function (e) {
            var a = e.target.closest('a');
            if (!a || a.classList.contains('is-disabled')) return;
            e.preventDefault();
            letter = (a.getAttribute('data-letter') || 'all').toUpperCase();
            if (search) search.value = '';
            q = '';
            var u = new URL(location.href);
            if (letter === 'ALL') u.searchParams.delete('letter'); else u.searchParams.set('letter', letter);
            u.searchParams.delete('q');
            history.pushState({ letter: letter }, '', u);
            apply();
        });
    }

    if (search) {
        var t;
        search.addEventListener('input', function () {
            clearTimeout(t);
            t = setTimeout(function () { q = search.value.trim().toLowerCase(); apply(); }, 180);
        });
        search.addEventListener('keydown', function (e) {
            if (e.key === 'Escape') { search.value = ''; q = ''; apply(); }
        });
    }

    if (sortSel) {
        sortSel.addEventListener('change', function () {
            var mode = sortSel.value;
            cards.sort(function (a, b) {
                if (mode === 'za') return b.getAttribute('data-name').localeCompare(a.getAttribute('data-name'));
                if (mode === 'products') return (+b.getAttribute('data-count')) - (+a.getAttribute('data-count'));
                if (mode === 'recent') return (+b.getAttribute('data-added')) - (+a.getAttribute('data-added'));
                return a.getAttribute('data-name').localeCompare(b.getAttribute('data-name'));
            });
            cards.forEach(function (c) { grid.appendChild(c); });
            var u = new URL(location.href);
            if (mode === 'az') u.searchParams.delete('sort'); else u.searchParams.set('sort', mode);
            history.replaceState({}, '', u);
        });
    }

    window.addEventListener('popstate', function () {
        letter = (params().get('letter') || 'all').toUpperCase();
        q = (params().get('q') || '').toLowerCase();
        if (search) search.value = q;
        apply();
    });

    apply();
})();
</script>
@endsection
