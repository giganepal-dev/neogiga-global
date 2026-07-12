<!DOCTYPE html>
<html lang="{{ $locale ?? 'en-IN' }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">

    <title>{{ $title ?? config('seo.default_title') }}</title>
    <meta name="description" content="{{ $description ?? config('seo.default_description') }}">
    <link rel="canonical" href="{{ $canonical ?? url('/') }}">

    {{-- Each alternate keeps its own marketplace host; it must never inherit this host. --}}
    @foreach (($marketplaceContext['hreflang'] ?? []) as $alternate)
        <link rel="alternate" hreflang="{{ $alternate['hreflang'] }}" href="{{ $alternate['url'] }}">
    @endforeach

    {{-- OpenGraph --}}
    <meta property="og:type" content="website">
    <meta property="og:site_name" content="{{ config('seo.site_name') }}">
    <meta property="og:title" content="{{ $title ?? config('seo.default_title') }}">
    <meta property="og:description" content="{{ $description ?? config('seo.default_description') }}">
    <meta property="og:url" content="{{ $canonical ?? url('/') }}">
    <meta property="og:image" content="{{ url(config('seo.default_og_image')) }}">

    {{-- Twitter Card --}}
    <meta name="twitter:card" content="summary_large_image">
    <meta name="twitter:site" content="{{ config('seo.social.handle') }}">
    <meta name="twitter:title" content="{{ $title ?? config('seo.default_title') }}">
    <meta name="twitter:description" content="{{ $description ?? config('seo.default_description') }}">

    {{-- JSON-LD: Organization + WebSite + Breadcrumb + FAQ (Blueprint §42) --}}
    <script type="application/ld+json">@json($jsonLd, JSON_UNESCAPED_SLASHES)</script>

    <style>
        /* NeoGiga design tokens — deep navy / dark blue / electric cyan / gold */
        :root{
            --ng-navy:#081527;--ng-navy-2:#0D2240;--ng-blue:#123A6B;
            --ng-cyan:#19D3F5;--ng-cyan-dim:#0FA8C9;--ng-gold:#F5B928;
            --ng-white:#FFFFFF;--ng-gray:#C8D3E0;--ng-gray-2:#8FA1B8;
            --ng-radius:14px;--ng-max:1180px;
        }
        *,*::before,*::after{box-sizing:border-box;margin:0;padding:0}
        html{scroll-behavior:smooth}
        body{
            font-family:ui-sans-serif,system-ui,-apple-system,"Segoe UI",Roboto,Arial,sans-serif;
            background:var(--ng-navy);color:var(--ng-gray);line-height:1.6;
            -webkit-font-smoothing:antialiased;
        }
        a{color:var(--ng-cyan);text-decoration:none}
        a:hover{text-decoration:underline}
        img,svg{display:block;max-width:100%}
        .wrap{max-width:var(--ng-max);margin:0 auto;padding:0 20px}
        .btn{display:inline-block;padding:12px 26px;border-radius:999px;font-weight:600;
            border:1px solid transparent;cursor:pointer;font-size:1rem}
        .btn-gold{background:var(--ng-gold);color:var(--ng-navy)}
        .btn-gold:hover{filter:brightness(1.08);text-decoration:none}
        .btn-ghost{border-color:var(--ng-cyan);color:var(--ng-cyan)}
        .btn-ghost:hover{background:rgba(25,211,245,.08);text-decoration:none}
        .chip{display:inline-block;padding:4px 12px;border-radius:999px;font-size:.78rem;
            background:rgba(25,211,245,.12);color:var(--ng-cyan);letter-spacing:.04em}
        /* Header */
        header{position:sticky;top:0;z-index:50;background:rgba(8,21,39,.92);
            border-bottom:1px solid rgba(25,211,245,.15);backdrop-filter:blur(8px)}
        .nav{display:flex;align-items:center;gap:18px;height:64px}
        .logo{display:flex;align-items:center;gap:10px;color:var(--ng-white);font-weight:800;
            font-size:1.25rem;letter-spacing:.02em}
        .logo em{color:var(--ng-cyan);font-style:normal}
        .nav nav{display:none;gap:22px;margin-left:14px}
        .nav nav a{color:var(--ng-gray);font-size:.92rem}
        .nav nav a:hover{color:var(--ng-cyan);text-decoration:none}
        .nav .spacer{flex:1}
        .switcher{display:flex;gap:8px}
        .switcher select{
            background:var(--ng-navy-2);color:var(--ng-gray);border:1px solid rgba(25,211,245,.25);
            border-radius:8px;padding:6px 10px;font-size:.85rem}
        .marketplace-recommend{background:#fff7df;color:#3b2b06;border-bottom:1px solid #f4d47b}
        .marketplace-recommend .wrap{display:flex;align-items:center;justify-content:space-between;gap:14px;padding:10px 20px;flex-wrap:wrap}
        .marketplace-recommend strong{color:#081527}
        .recommend-actions{display:flex;gap:8px;flex-wrap:wrap}
        .recommend-actions form{margin:0}
        .recommend-actions button{border:1px solid #d9a514;border-radius:8px;min-height:36px;padding:0 12px;font-weight:800;background:#fff;color:#081527}
        .recommend-actions .primary{background:var(--ng-gold)}
        /* Hero */
        .hero{position:relative;overflow:hidden;
            background:radial-gradient(1200px 500px at 70% -10%,rgba(18,58,107,.9),transparent),
                       radial-gradient(800px 400px at 10% 110%,rgba(25,211,245,.12),transparent),
                       var(--ng-navy)}
        .hero .wrap{position:relative;padding:84px 20px 72px;text-align:left}
        .hero .watermark{position:absolute;right:-60px;top:50%;transform:translateY(-50%);
            width:420px;height:420px;opacity:.07;pointer-events:none}
        .hero h1{color:var(--ng-white);font-size:clamp(2.1rem,5vw,3.4rem);line-height:1.12;
            max-width:14ch;font-weight:800}
        .hero h1 span{color:var(--ng-cyan)}
        .hero p.lead{max-width:56ch;margin:20px 0 30px;font-size:1.08rem}
        .hero .cta-row{display:flex;flex-wrap:wrap;gap:14px}
        .hero .domains{margin-top:34px;font-size:.85rem;color:var(--ng-gray-2)}
        .hero .domains a{color:var(--ng-gray-2);text-decoration:underline}
        /* Sections */
        section{padding:64px 0}
        .sec-title{color:var(--ng-white);font-size:1.7rem;font-weight:700;margin-bottom:8px}
        .sec-sub{color:var(--ng-gray-2);margin-bottom:34px;max-width:70ch}
        .grid{display:grid;gap:16px;grid-template-columns:repeat(auto-fill,minmax(240px,1fr))}
        .card{background:var(--ng-navy-2);border:1px solid rgba(25,211,245,.12);
            border-radius:var(--ng-radius);padding:22px;transition:border-color .15s,transform .15s}
        a.card{display:block;color:inherit;text-decoration:none}
        .card:hover{border-color:rgba(25,211,245,.45);transform:translateY(-2px)}
        .card .ic{width:42px;height:42px;border-radius:10px;display:grid;place-items:center;
            background:rgba(25,211,245,.1);color:var(--ng-cyan);font-size:1.3rem;margin-bottom:14px}
        .card h3{color:var(--ng-white);font-size:1.02rem;margin-bottom:6px}
        .card p{font-size:.88rem;color:var(--ng-gray-2)}
        .pillars .card{border-top:3px solid var(--ng-gold)}
        /* Seller band */
        .band{background:linear-gradient(120deg,var(--ng-blue),var(--ng-navy-2));
            border:1px solid rgba(245,185,40,.35);border-radius:var(--ng-radius);
            padding:40px;display:flex;flex-wrap:wrap;align-items:center;gap:24px}
        .band h2{color:var(--ng-white);font-size:1.5rem;flex:1 1 340px}
        .band h2 small{display:block;font-size:.95rem;font-weight:400;color:var(--ng-gray);margin-top:8px}
        /* Newsletter */
        .newsletter form{display:flex;flex-wrap:wrap;gap:12px;max-width:520px}
        .newsletter input[type=email]{flex:1 1 260px;padding:12px 16px;border-radius:999px;
            border:1px solid rgba(25,211,245,.3);background:var(--ng-navy-2);color:var(--ng-white);font-size:1rem}
        .newsletter input::placeholder{color:var(--ng-gray-2)}
        .notice{margin:0 0 18px;padding:12px 14px;border:1px solid rgba(25,211,245,.4);border-radius:10px;color:var(--ng-white);background:rgba(25,211,245,.1)}
        /* Footer */
        footer{border-top:1px solid rgba(25,211,245,.15);padding:48px 0 32px;font-size:.9rem}
        .foot-grid{display:grid;gap:28px;grid-template-columns:repeat(auto-fit,minmax(180px,1fr))}
        footer h4{color:var(--ng-white);font-size:.95rem;margin-bottom:12px}
        footer ul{list-style:none}
        footer li{margin-bottom:8px}
        footer a{color:var(--ng-gray-2)}
        footer a:hover{color:var(--ng-cyan)}
        .foot-bottom{margin-top:36px;padding-top:20px;border-top:1px solid rgba(255,255,255,.06);
            display:flex;flex-wrap:wrap;gap:12px;justify-content:space-between;color:var(--ng-gray-2);font-size:.82rem}
        @media(min-width:900px){.nav nav{display:flex}}
        @media(prefers-reduced-motion:reduce){.card:hover{transform:none}}
    </style>
</head>
<body>
@if($marketplaceContext['show_recommendation'] ?? false)
    <aside class="marketplace-recommend" aria-label="Marketplace recommendation">
        <div class="wrap">
            <span>We detected a closer NeoGiga edition: <strong>{{ $marketplaceContext['recommended']['name'] }}</strong> with {{ $marketplaceContext['recommended']['currency_code'] }} pricing and regional stock. Your choice is remembered.</span>
            <div class="recommend-actions">
                <form method="post" action="{{ route('marketplace.preference') }}">
                    @csrf
                    <input type="hidden" name="marketplace" value="{{ $marketplaceContext['recommended']['code'] }}">
                    <input type="hidden" name="return_path" value="{{ request()->getRequestUri() }}">
                    <input type="hidden" name="action" value="switch">
                    <button class="primary" type="submit">Switch to {{ $marketplaceContext['recommended']['name'] }}</button>
                </form>
                <form method="post" action="{{ route('marketplace.preference') }}">
                    @csrf
                    <input type="hidden" name="marketplace" value="{{ strtolower($marketplaceContext['current']->code ?? 'global') }}">
                    <input type="hidden" name="return_path" value="{{ request()->getRequestUri() }}">
                    <input type="hidden" name="action" value="stay">
                    <button type="submit">Stay on {{ $marketplaceContext['current']->name ?? 'NeoGiga Global' }}</button>
                </form>
            </div>
        </div>
    </aside>
@endif

<header>
    <div class="wrap nav">
        <a class="logo" href="{{ $storefrontPath }}" aria-label="{{ $marketplaceName }} home">
            {{-- NeoGiga mark: hex/circuit motif in cyan + gold --}}
            <svg width="34" height="34" viewBox="0 0 48 48" fill="none" aria-hidden="true">
                <path d="M24 3 42 13.5v21L24 45 6 34.5v-21L24 3Z" stroke="#19D3F5" stroke-width="2.6"/>
                <path d="M24 13v10l8.5 5" stroke="#F5B928" stroke-width="2.6" stroke-linecap="round"/>
                <circle cx="24" cy="23" r="2.6" fill="#19D3F5"/>
            </svg>
            {{ $marketplaceName }}
        </a>
        <nav aria-label="Primary">
            <a href="#categories">Categories</a>
            <a href="{{ $storefrontPath }}/products">Products</a>
            <a href="{{ $storefrontPath }}/lms">Learn</a>
            <a href="{{ $storefrontPath }}/sell-on-neogiga">Sell</a>
            <a href="{{ $storefrontPath }}/rfq">B2B &amp; RFQ</a>
        </nav>
        <div class="spacer"></div>
        <form class="switcher" method="post" action="{{ route('marketplace.preference') }}">
            @csrf
            <label class="sr-only" for="ng-country" style="position:absolute;left:-9999px">Country</label>
            <select id="ng-country" name="marketplace" aria-label="Choose marketplace">
                @foreach (($marketplaceContext['editions'] ?? []) as $edition)
                    <option value="{{ $edition['code'] }}" @selected($edition['id'] === ($marketplaceContext['current']->id ?? null))>{{ $edition['name'] }} · {{ $edition['currency_code'] }}</option>
                @endforeach
            </select>
            <input type="hidden" name="return_path" value="{{ request()->getRequestUri() }}">
            <input type="hidden" name="action" value="switch">
            <button class="btn btn-ghost" style="padding:6px 10px;font-size:.85rem" type="submit">Go</button>
            <label for="ng-lang" style="position:absolute;left:-9999px">Language</label>
            <select id="ng-lang" aria-label="Current language" disabled>
                <option>{{ strtoupper(strtok($locale, '-')) }}</option>
            </select>
        </form>
    </div>
</header>

<main>
    {{-- HERO --}}
    <section class="hero" aria-labelledby="hero-title">
        <div class="wrap">
            <svg class="watermark" viewBox="0 0 48 48" fill="none" aria-hidden="true">
                <path d="M24 3 42 13.5v21L24 45 6 34.5v-21L24 3Z" stroke="#19D3F5" stroke-width="1.2"/>
                <path d="M24 13v10l8.5 5" stroke="#F5B928" stroke-width="1.2"/>
            </svg>
            <span class="chip">{{ $countryName === 'Global' ? 'Global marketplace' : $countryName . ' marketplace' }} · {{ $currencyCode }}</span>
            <h1 id="hero-title" style="margin-top:16px">{{ $marketplaceName }} for <span>Engineers</span></h1>
            <p class="lead">
                Source semiconductors, components, IoT, robotics and industrial tools. Browse the catalog,
                compare technical details, and request a bulk quote for your next build.
            </p>
            <div class="cta-row">
                <a class="btn btn-gold" href="{{ $storefrontPath }}/products">Browse Products</a>
                <a class="btn btn-ghost" href="{{ $storefrontPath }}/rfq">Request Bulk Quote</a>
            </div>
            <p class="domains">
                Current storefront: {{ request()->getHost() }} · Country, currency and stock availability are confirmed during checkout or quotation.
            </p>
        </div>
    </section>

    {{-- CATEGORY GRID --}}
    <section id="categories" aria-labelledby="cat-title">
        <div class="wrap">
            <h2 class="sec-title" id="cat-title">Shop by Engineering Domain</h2>
            <p class="sec-sub">A global product master, localized inventory. Explore the core domains of the NeoGiga catalog.</p>
            <div class="grid">
                @foreach ($categories as $category)
                    <a class="card" href="{{ $storefrontPath }}/products?category={{ $category['slug'] }}" aria-label="Browse {{ $category['name'] }}">
                        <div class="ic" aria-hidden="true">{{ $category['icon'] }}</div>
                        <h3>{{ $category['name'] }}</h3>
                        <p>{{ $category['blurb'] }}</p>
                    </a>
                @endforeach
            </div>
        </div>
    </section>

    {{-- PLATFORM PILLARS: AI / LMS / SELLER / B2B --}}
    <section id="ai" class="pillars" aria-labelledby="pillar-title" style="background:var(--ng-navy-2)">
        <div class="wrap">
            <h2 class="sec-title" id="pillar-title">More Than a Marketplace</h2>
            <p class="sec-sub">Commerce, knowledge, learning and AI — one ecosystem.</p>
            <div class="grid" style="grid-template-columns:repeat(auto-fill,minmax(255px,1fr))">
                <article class="card">
                    <div class="ic" aria-hidden="true">AI</div>
                    <h3>AI Commerce for Engineers and Makers</h3>
                    <p>Use the engineering assistant to turn an idea into a component list, alternatives,
                       tutorials and a sourcing path.</p>
                    <p style="margin-top:12px"><a class="btn btn-ghost" href="{{ $storefrontPath }}/ai-commerce">Open AI Project Builder</a></p>
                </article>
                <article class="card" id="learn">
                    <div class="ic" aria-hidden="true">🎓</div>
                    <h3>Learn &amp; Build (LMS)</h3>
                    <p>Courses and hands-on projects linked to real parts for students, universities and factory teams.</p>
                    <p style="margin-top:12px"><a class="btn btn-ghost" href="{{ $storefrontPath }}/lms">Explore Learning</a></p>
                </article>
                <article class="card" id="sellers">
                    <div class="ic" aria-hidden="true">S</div>
                    <h3>Sell on NeoGiga</h3>
                    <p>Reach engineers, makers, schools, labs, workshops, resellers, and B2B buyers across South Asia.
                       Submit your seller application and manage region-specific approval and offers.</p>
                    <p style="margin-top:12px"><a class="btn btn-ghost" href="{{ $storefrontPath }}/sell-on-neogiga">Become a Seller</a></p>
                </article>
                <article class="card" id="b2b">
                    <div class="ic" aria-hidden="true">🏭</div>
                    <h3>B2B Procurement</h3>
                    <p>Send an RFQ for sourcing, quantities, target pricing and delivery requirements for your organization.</p>
                    <p style="margin-top:12px"><a class="btn btn-ghost" href="{{ $storefrontPath }}/rfq">Create RFQ</a></p>
                </article>
            </div>
        </div>
    </section>

    {{-- SELLER CTA BAND --}}
    <section aria-labelledby="seller-band-title">
        <div class="wrap">
            <div class="band">
                <h2 id="seller-band-title">Sell on NeoGiga
                    <small>Apply to sell with regional approval, stock visibility, RFQ support and transparent settlement.</small>
                </h2>
                <a class="btn btn-gold" href="{{ $storefrontPath }}/sell-on-neogiga">Become a Seller</a>
                <a class="btn btn-ghost" href="{{ $storefrontPath }}/distributors">Join Distributor Network</a>
            </div>
        </div>
    </section>

    {{-- NEWSLETTER --}}
    <section id="newsletter" class="newsletter" aria-labelledby="nl-title" style="padding-top:0">
        <div class="wrap">
            <h2 class="sec-title" id="nl-title">Stay in the Loop</h2>
            <p class="sec-sub">Product launches, new courses and platform milestones. No spam.</p>
            @if (session('status'))
                <p class="notice" role="status">{{ session('status') }}</p>
            @endif
            <form action="{{ route('newsletter.subscribe') }}" method="post" aria-label="Newsletter signup">
                @csrf
                <input type="hidden" name="return_path" value="{{ request()->getRequestUri() }}">
                <input type="email" name="email" placeholder="you@company.com" autocomplete="email"
                       inputmode="email" aria-label="Email address" required>
                <button class="btn btn-ghost" type="submit">Subscribe</button>
            </form>
            <p style="margin-top:14px;font-size:.85rem;color:var(--ng-gray-2)">
                Follow {{ config('seo.social.handle') }} —
                <a href="{{ config('seo.social.twitter') }}" rel="noopener">X</a> ·
                <a href="{{ config('seo.social.youtube') }}" rel="noopener">YouTube</a> ·
                <a href="{{ config('seo.social.linkedin') }}" rel="noopener">LinkedIn</a> ·
                <a href="{{ config('seo.social.instagram') }}" rel="noopener">Instagram</a>
            </p>
        </div>
    </section>
</main>

<footer>
    <div class="wrap">
        <div class="foot-grid">
            <div>
                <h4>NeoGiga</h4>
                <ul>
                    <li><a href="{{ $storefrontPath }}/products">Catalog</a></li>
                    <li><a href="{{ $storefrontPath }}/lms">Learning</a></li>
                    <li><a href="{{ $storefrontPath }}/ai-commerce">AI Commerce</a></li>
                    <li><a href="{{ $storefrontPath }}/rfq">B2B &amp; RFQ</a></li>
                </ul>
            </div>
            <div>
                <h4>Global Editions</h4>
                <ul>
                    @foreach (($marketplaceContext['editions'] ?? []) as $edition)
                        @if ($edition['url'])
                            <li><a href="{{ $edition['url'] }}/en" rel="alternate">{{ $edition['name'] }} · {{ $edition['currency_code'] }}</a></li>
                        @endif
                    @endforeach
                </ul>
            </div>
            <div>
                <h4>Sellers</h4>
                <ul>
                    <li><a href="{{ $storefrontPath }}/sell-on-neogiga">Become a Seller</a></li>
                    <li><a href="{{ $storefrontPath }}/distributors">Distributor Network</a></li>
                    <li><a href="{{ $storefrontPath }}/rfq">Sourcing &amp; RFQ</a></li>
                </ul>
            </div>
            <div>
                <h4>Company</h4>
                <ul>
                    <li><a href="mailto:{{ config('seo.organization.email') }}">Contact</a></li>
                    <li><a href="{{ $storefrontPath }}/products">Product Catalog</a></li>
                    <li><a href="{{ $storefrontPath }}/lms">Learning Hub</a></li>
                </ul>
            </div>
        </div>
        <div class="foot-bottom">
            <span>© {{ date('Y') }} {{ config('seo.organization.legal_name') }} — NeoGiga. All rights reserved.</span>
            <span>{{ config('seo.social.handle') }}</span>
        </div>
    </div>
</footer>

</body>
</html>
