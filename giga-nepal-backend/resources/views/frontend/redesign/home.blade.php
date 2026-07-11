<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>NeoGiga — The Global Engineering Marketplace</title>
<meta name="robots" content="noindex,nofollow">
<link rel="preconnect" href="https://fonts.googleapis.com">
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&family=JetBrains+Mono:wght@500&display=swap" rel="stylesheet">
<style>
/* NeoGiga "Precision Engineering" — self-contained (no runtime CDN dependency) */
:root{
  --bg:#101417;--surface:#0b0f11;--surface-1:#181c1f;--surface-2:#1d2023;--surface-3:#272a2d;
  --on:#e0e3e6;--muted:#c5c6cd;--faint:#8f9097;
  --primary:#bbc7e0;--secondary:#28d8fb;--secondary-soft:rgba(40,216,251,.12);
  --tertiary:#f9bd2c;--success:#10b981;
  --line:rgba(255,255,255,.08);--glass:rgba(255,255,255,.03);
  --max:1440px;--r:16px;
}
*{box-sizing:border-box}
html{scroll-behavior:smooth}
body{margin:0;background:
  radial-gradient(circle at 18% 0%,rgba(40,216,251,.10),transparent 42rem),
  radial-gradient(circle at 85% 2%,rgba(249,189,44,.06),transparent 36rem),
  var(--bg);
  color:var(--on);font-family:'Inter',ui-sans-serif,system-ui,-apple-system,"Segoe UI",Roboto,Arial,sans-serif;
  line-height:1.55;-webkit-font-smoothing:antialiased;overflow-x:hidden}
a{color:inherit;text-decoration:none}
.mono{font-family:'JetBrains Mono',ui-monospace,SFMono-Regular,Menlo,monospace;letter-spacing:.02em}
.wrap{width:min(var(--max),calc(100% - 48px));margin-inline:auto}
@media(max-width:640px){.wrap{width:calc(100% - 32px)}}

/* Nav */
.nav{position:sticky;top:0;z-index:50;background:rgba(16,20,23,.82);backdrop-filter:blur(14px);border-bottom:1px solid var(--line)}
.nav-in{height:76px;display:flex;align-items:center;justify-content:space-between;gap:24px}
.brand{display:flex;align-items:center;gap:11px;font-weight:800;font-size:1.35rem;color:var(--primary);letter-spacing:-.02em}
.brand .mk{width:36px;height:36px;border-radius:10px;display:grid;place-items:center;background:var(--secondary-soft);border:1px solid rgba(40,216,251,.4)}
.nav-links{display:flex;gap:30px}
.nav-links a{font-size:.82rem;font-weight:600;color:var(--muted);transition:color .2s}
.nav-links a:hover{color:var(--secondary)}
.nav-cta{display:flex;align-items:center;gap:14px}
.region{display:inline-flex;align-items:center;gap:6px;font-size:.76rem;color:var(--muted);border:1px solid var(--line);border-radius:999px;padding:7px 12px}
.pill{display:inline-flex;align-items:center;gap:8px;border-radius:999px;padding:9px 22px;font-size:.82rem;font-weight:600;transition:.15s;white-space:nowrap}
.pill-solid{background:var(--secondary);color:#003640}.pill-solid:hover{filter:brightness(1.1);transform:translateY(-1px)}
.pill-ghost{color:var(--muted)}.pill-ghost:hover{color:var(--secondary)}
@media(max-width:1000px){.nav-links,.region{display:none}}

/* Hero */
.hero{min-height:600px;display:flex;flex-direction:column;align-items:center;justify-content:center;text-align:center;padding:88px 0 64px}
.eyebrow{display:inline-flex;align-items:center;gap:8px;border:1px solid var(--line);border-radius:999px;padding:6px 16px;font-size:.75rem;color:var(--muted);margin-bottom:26px}
.dot{width:8px;height:8px;border-radius:999px;background:var(--success);box-shadow:0 0 10px var(--success)}
.hero h1{font-size:clamp(2.7rem,6.2vw,4.3rem);font-weight:800;letter-spacing:-.025em;line-height:1.04;margin:0 0 20px;text-shadow:0 0 44px rgba(40,216,251,.22)}
.hero h1 em{font-style:normal;color:var(--secondary)}
.hero .sub{max-width:640px;color:var(--muted);font-size:1.08rem;margin:0 auto 36px}
.searchbox{position:relative;max-width:820px;margin:0 auto;width:100%}
.searchbox::before{content:"";position:absolute;inset:-2px;border-radius:999px;background:linear-gradient(90deg,var(--secondary),var(--primary));filter:blur(14px);opacity:.25;transition:opacity .6s}
.searchbox:focus-within::before{opacity:.5}
.searchbar{position:relative;display:flex;align-items:center;gap:16px;background:var(--surface-1);border:1px solid var(--line);border-radius:999px;padding:12px 16px 12px 24px;box-shadow:0 30px 80px rgba(0,0,0,.4)}
.searchbar svg{flex:none;color:var(--secondary)}
.searchbar input{flex:1;min-width:0;background:transparent;border:0;outline:0;color:var(--on);font-size:1.02rem;font-family:'JetBrains Mono',monospace}
.searchbar input::placeholder{color:rgba(197,198,205,.4)}
.kbd{font-size:.7rem;color:var(--faint);background:var(--surface-3);border-radius:6px;padding:4px 8px}
.hero-actions{display:flex;gap:16px;justify-content:center;flex-wrap:wrap;margin-top:26px}
.btn-line{border:1px solid var(--line);color:var(--on)}.btn-line:hover{border-color:var(--secondary);color:var(--secondary)}
@media(max-width:640px){.kbd{display:none}.searchbar{padding:10px 12px 10px 18px}}

/* Stats */
.stats{border-block:1px solid var(--line);background:var(--surface)}
.stats .wrap{display:grid;grid-template-columns:repeat(4,1fr);gap:24px;padding:36px 0}
.stat b{display:block;font-size:clamp(1.6rem,3vw,2.2rem);font-weight:800;color:var(--on);letter-spacing:-.02em}
.stat span{color:var(--muted);font-size:.82rem}
.stat .accent{color:var(--secondary)}
@media(max-width:640px){.stats .wrap{grid-template-columns:1fr 1fr;gap:20px}}

/* Sections */
section.block{padding:80px 0}
.head{display:flex;align-items:flex-end;justify-content:space-between;gap:16px;margin-bottom:44px}
.head .kicker{color:var(--secondary);font-size:.72rem;font-weight:700;letter-spacing:.14em;text-transform:uppercase;margin-bottom:10px}
.head h2{font-size:clamp(1.7rem,3.2vw,2.2rem);font-weight:700;letter-spacing:-.015em;margin:0 0 8px}
.head p{margin:0;color:var(--muted);max-width:56ch}
.link{color:var(--secondary);font-size:.82rem;font-weight:600;display:inline-flex;align-items:center;gap:6px;white-space:nowrap}
.link:hover{text-decoration:underline}

/* Grid + cards */
.grid{display:grid;gap:24px}
.g4{grid-template-columns:repeat(4,1fr)}.g3{grid-template-columns:repeat(3,1fr)}.g2{grid-template-columns:repeat(2,1fr)}
@media(max-width:1000px){.g4{grid-template-columns:repeat(2,1fr)}.g3{grid-template-columns:repeat(2,1fr)}}
@media(max-width:640px){.g4,.g3,.g2{grid-template-columns:1fr}}
.glass{background:var(--glass);border:1px solid var(--line);backdrop-filter:blur(12px)}

/* Capability cards */
.cap{border-radius:var(--r);padding:30px}
.cap .ic{width:46px;height:46px;border-radius:12px;background:var(--secondary-soft);color:var(--secondary);display:grid;place-items:center;margin-bottom:20px}
.cap h3{font-size:1.12rem;font-weight:600;margin:0 0 8px}
.cap p{color:var(--muted);font-size:.9rem;margin:0}

/* Category cards */
.cat{border-radius:var(--r);padding:28px;display:block;transition:.2s}
.cat:hover{border-color:rgba(40,216,251,.5);transform:translateY(-3px)}
.chip{width:46px;height:46px;border-radius:12px;background:var(--secondary-soft);color:var(--secondary);display:grid;place-items:center;margin-bottom:20px;transition:transform .2s}
.cat:hover .chip{transform:scale(1.1)}
.cat h3{font-size:1.15rem;font-weight:600;margin:0 0 8px}
.cat p{color:var(--muted);font-size:.88rem;margin:0 0 14px;display:-webkit-box;-webkit-line-clamp:2;-webkit-box-orient:vertical;overflow:hidden}
.cat .go{color:var(--secondary);font-size:.76rem}

/* Editions */
.ed{border-radius:14px;padding:20px 22px;display:flex;align-items:center;justify-content:space-between;gap:14px;transition:.2s}
.ed:hover{border-color:rgba(40,216,251,.5)}
.ed .n{font-weight:600}
.ed .m{color:var(--muted);font-size:.76rem;margin-top:2px}
.ed .cur{font-size:.72rem;color:var(--secondary);border:1px solid rgba(40,216,251,.3);border-radius:999px;padding:4px 10px;white-space:nowrap}

/* Partners */
.partners{border-block:1px solid var(--line);background:var(--surface)}
.partners .wrap{display:flex;flex-wrap:wrap;align-items:center;justify-content:space-between;gap:32px;padding:30px 0;opacity:.55;transition:opacity .5s;font-weight:700;color:var(--muted);letter-spacing:.05em}
.partners:hover .wrap{opacity:1}

/* Products */
.pcard{border-radius:var(--r);overflow:hidden;display:flex;flex-direction:column}
.pcard .media{height:200px;background:var(--surface-2);display:grid;place-items:center;position:relative}
.pcard .media svg{width:72px;height:72px;color:rgba(197,198,205,.25)}
.badge{position:absolute;top:16px;left:16px;font-size:.68rem;padding:4px 8px;border-radius:6px;border:1px solid}
.badge.in{background:rgba(16,185,129,.15);color:var(--success);border-color:rgba(16,185,129,.3)}
.badge.low{background:rgba(249,189,44,.15);color:var(--tertiary);border-color:rgba(249,189,44,.3)}
.pbody{padding:22px;display:flex;flex-direction:column;flex:1}
.prow1{display:flex;justify-content:space-between;gap:12px;margin-bottom:16px}
.prow1 h4{margin:0 0 4px;font-size:1.02rem;font-weight:600}
.prow1 .mpn{font-size:.72rem;color:var(--muted)}
.price{color:var(--secondary);font-size:1.2rem;font-weight:600;text-align:right}
.price small{display:block;font-size:.62rem;color:var(--muted);font-weight:400}
.specs{margin-bottom:22px}
.specs div{display:flex;justify-content:space-between;font-size:.76rem;padding:2px 0}
.specs span{color:var(--muted)}
.pbtn{margin-top:auto;display:flex;align-items:center;justify-content:center;gap:8px;padding:12px;border-radius:10px;background:var(--surface-3);color:var(--on);font-size:.82rem;font-weight:600;transition:.2s}
.pbtn:hover{background:var(--secondary);color:#003640}

/* CTA / newsletter */
.news{border-radius:28px;padding:clamp(32px,5vw,72px);position:relative;overflow:hidden}
.news::after{content:"";position:absolute;right:-120px;top:-120px;width:360px;height:360px;border-radius:999px;background:radial-gradient(circle,rgba(40,216,251,.18),transparent 70%)}
.news h2{font-size:clamp(1.8rem,4vw,2.4rem);font-weight:800;margin:0 0 20px;position:relative}
.news h2 em{font-style:normal;color:var(--secondary)}
.news p{color:var(--muted);font-size:1.05rem;max-width:60ch;margin:0 0 30px;position:relative}
.news form{display:flex;gap:16px;flex-wrap:wrap;position:relative}
.news input{flex:1;min-width:220px;background:var(--surface-2);border:1px solid var(--line);border-radius:12px;padding:15px 22px;color:var(--on);outline:0}
.news input:focus{border-color:var(--secondary)}
.news button{background:var(--secondary);color:#003640;border:0;border-radius:12px;padding:15px 32px;font-weight:600;font-size:.85rem;cursor:pointer;transition:.15s}
.news button:hover{filter:brightness(1.1)}

/* Footer */
footer{border-top:1px solid var(--line);background:var(--surface);margin-top:80px}
footer .wrap{display:grid;grid-template-columns:1.6fr repeat(3,1fr);gap:24px;padding:72px 0 40px}
@media(max-width:800px){footer .wrap{grid-template-columns:1fr 1fr}}
footer h5{font-size:.8rem;font-weight:600;margin:0 0 22px;color:var(--on)}
footer li{list-style:none;margin:0 0 14px}
footer ul{padding:0;margin:0}
footer a{color:var(--muted);font-size:.85rem}
footer a:hover{color:var(--secondary)}
footer .copy{color:var(--muted);font-size:.78rem;max-width:36ch}
.footbar{border-top:1px solid var(--line)}
.footbar .wrap{display:flex;justify-content:space-between;gap:16px;flex-wrap:wrap;padding:20px 0;color:var(--faint);font-size:.74rem}
</style>
</head>
<body>
@php
  $fmt = function($n){ $n=(int)$n; if($n<=0) return null; if($n>=1000000) return round($n/1000000,1).'M+'; if($n>=1000) return round($n/1000,1).'K+'; return number_format($n).'+'; };
  $catIcon = '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><rect x="4" y="4" width="16" height="16" rx="2"/><path d="M9 9h6v6H9z"/><path d="M4 9h2M4 15h2M18 9h2M18 15h2M9 4v2M15 4v2M9 18v2M15 18v2"/></svg>';
  $sampleCats = collect([
    (object)['name'=>'Semiconductors','description'=>'MCUs, FPGAs and specialized ASICs for high-performance computing.','slug'=>'semiconductors'],
    (object)['name'=>'Robotics','description'=>'Servo controllers, lidar modules and kinetic drive systems.','slug'=>'robotics'],
    (object)['name'=>'IoT & Wireless','description'=>'LoRaWAN, 5G modules and multi-protocol mesh nodes.','slug'=>'iot-wireless'],
    (object)['name'=>'Power Technology','description'=>'GaN power stages, BMS controllers and energy storage.','slug'=>'power-technology'],
    (object)['name'=>'Sensors','description'=>'Precision measurement, motion and environmental sensing.','slug'=>'sensors'],
    (object)['name'=>'Automation','description'=>'PLCs, drives and industrial control systems.','slug'=>'industrial-automation'],
  ]);
  $cats = ($categories ?? collect())->isNotEmpty() ? $categories : $sampleCats;
  $sampleProducts = collect([
    (object)['name'=>'X-series Ultra Core v4','mpn'=>'AG-990-XC4-24','base_price'=>1240,'slug'=>null,'spec'=>'RISC-V 128-bit','avail'=>'4,290 units','stock'=>'in'],
    (object)['name'=>'OpticLidar Pro H2','mpn'=>'SN-OP-H2-88','base_price'=>895.5,'slug'=>null,'spec'=>'250m @ 0.1° res','avail'=>'142 units','stock'=>'low'],
    (object)['name'=>'AeroTorque HV 450','mpn'=>'DR-MO-450-HV','base_price'=>315,'slug'=>null,'spec'=>'18.5kg thrust','avail'=>'880 units','stock'=>'in'],
  ]);
  $prods = ($products ?? collect())->isNotEmpty() ? $products : $sampleProducts;
  $eds = $editions ?? collect();
  $st = $stats ?? [];
@endphp

<header class="nav"><div class="wrap nav-in">
  <a class="brand" href="/"><span class="mk"><svg width="20" height="20" viewBox="0 0 32 32" fill="none"><path d="M9 22V10l14 12V10" stroke="#28d8fb" stroke-width="2.6" stroke-linecap="round" stroke-linejoin="round"/></svg></span>NeoGiga</a>
  <nav class="nav-links">
    <a href="/products">Products</a><a href="/categories">Categories</a><a href="/ai-commerce">AI Builder</a><a href="/rfq">Bulk RFQ</a><a href="/sell-on-neogiga">Sell</a><a href="/distributors">Distributors</a>
  </nav>
  <div class="nav-cta">
    <span class="region"><svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="9"/><path d="M3 12h18M12 3c3 3 3 15 0 18M12 3c-3 3-3 15 0 18"/></svg> Global · USD</span>
    <a class="pill pill-ghost" href="/admin/login">Login</a>
    <a class="pill pill-solid" href="/products">Start sourcing</a>
  </div>
</div></header>

<main>
<section class="hero"><div class="wrap">
  <div class="eyebrow"><span class="dot"></span> One global MPN catalog · {{ ($st['marketplaces'] ?? 0) ?: 26 }} regional editions · live inventory</div>
  <h1>The Global Marketplace for <em>Engineering</em>.</h1>
  <p class="sub">Source semiconductors, robotics, IoT and power components across regions — with AI part matching, regional pricing and verified global sellers.</p>
  <form class="searchbox" action="/products" method="get"><div class="searchbar">
    <svg width="22" height="22" viewBox="0 0 24 24" fill="currentColor"><path d="M12 2l1.5 4.5L18 8l-4.5 1.5L12 14l-1.5-4.5L6 8l4.5-1.5L12 2z"/></svg>
    <input name="q" type="text" placeholder='Search by MPN, part or "I want to build a drone"'>
    <span class="kbd mono">CMD + K</span>
    <button class="pill pill-solid" type="submit">Search</button>
  </div></form>
  <div class="hero-actions">
    <a class="pill btn-line" href="/ai-commerce">Try the AI Builder →</a>
    <a class="pill btn-line" href="/rfq">Request a bulk quote</a>
  </div>
</div></section>

<!-- Global stats -->
<section class="stats"><div class="wrap">
  <div class="stat"><b class="accent">{{ ($st['marketplaces'] ?? 0) ?: 26 }}</b><span>Regional marketplaces</span></div>
  <div class="stat"><b>{{ ($st['countries'] ?? 0) > 0 ? $st['countries'] : '25' }}+</b><span>Countries served</span></div>
  <div class="stat"><b>{{ $fmt($st['products'] ?? 0) ?? '600K+' }}</b><span>Components indexed</span></div>
  <div class="stat"><b>{{ ($st['categories'] ?? 0) > 0 ? $st['categories'] : '170' }}+</b><span>Technical categories</span></div>
</div></section>

<!-- Capabilities -->
<section class="block"><div class="wrap">
  <div class="head"><div>
    <div class="kicker">Built for global engineering teams</div>
    <h2>One platform. Every region.</h2>
    <p>A single canonical catalog, localized to each market with its own pricing, currency, sellers and logistics.</p>
  </div></div>
  <div class="grid g4">
    <div class="glass cap"><div class="ic"><svg viewBox="0 0 24 24" width="22" fill="none" stroke="currentColor" stroke-width="1.8"><path d="M12 3l1.8 5.2L19 10l-5.2 1.8L12 17l-1.8-5.2L5 10l5.2-1.8z"/></svg></div><h3>AI part matching</h3><p>Describe the build in plain language — get cross-referenced parts, alternates and BOMs from millions of datasheets.</p></div>
    <div class="glass cap"><div class="ic"><svg viewBox="0 0 24 24" width="22" fill="none" stroke="currentColor" stroke-width="1.8"><circle cx="12" cy="12" r="9"/><path d="M3 12h18M12 3c3 3 3 15 0 18M12 3c-3 3-3 15 0 18"/></svg></div><h3>Regional editions</h3><p>Localized storefronts per country with the right currency, tax, duty and in-region stock — from one global PIM.</p></div>
    <div class="glass cap"><div class="ic"><svg viewBox="0 0 24 24" width="22" fill="none" stroke="currentColor" stroke-width="1.8"><path d="M20 7l-8-4-8 4 8 4 8-4zM4 7v10l8 4 8-4V7"/></svg></div><h3>Verified global sellers</h3><p>Manufacturers, distributors and local sellers — approved per marketplace, with transparent MOQ and lead times.</p></div>
    <div class="glass cap"><div class="ic"><svg viewBox="0 0 24 24" width="22" fill="none" stroke="currentColor" stroke-width="1.8"><path d="M3 13h13V6H3zM16 8h3l2 3v2h-5z"/><circle cx="7" cy="17" r="2"/><circle cx="18" cy="17" r="2"/></svg></div><h3>Freight & ETA</h3><p>Nearest-warehouse routing with real duty, freight and delivery estimates before you commit to a build.</p></div>
  </div>
</div></section>

<!-- Global editions -->
@if($eds->isNotEmpty())
<section class="block" style="padding-top:0"><div class="wrap">
  <div class="head"><div>
    <div class="kicker">Live regional editions</div>
    <h2>Shop in your market</h2>
    <p>Active NeoGiga storefronts with local currency and in-region availability.</p>
  </div><a class="link" href="/products">Enter the marketplace →</a></div>
  <div class="grid g3">
    @foreach($eds->take(6) as $e)
    <a class="glass ed" href="{{ $e->domain ? 'https://'.$e->domain : '/products' }}">
      <div><div class="n">{{ $e->name }}</div><div class="m mono">{{ $e->domain ?? $e->generated_domain ?? ($e->country->name ?? 'Global') }}</div></div>
      <span class="cur mono">{{ $e->currency->code ?? 'USD' }}</span>
    </a>
    @endforeach
  </div>
</div></section>
@endif

<!-- Categories -->
<section class="block" style="padding-top:0"><div class="wrap">
  <div class="head"><div>
    <div class="kicker">Explore the catalog</div>
    <h2>Technical Categories</h2>
    <p>Precision components for every engineering vertical.</p>
  </div><a class="link" href="/categories">View all →</a></div>
  <div class="grid g3">
    @foreach($cats->take(6) as $c)
    <a class="glass cat" href="/products?category={{ $c->slug ?? '' }}">
      <div class="chip">{!! $catIcon !!}</div>
      <h3>{{ $c->name }}</h3>
      <p>{{ $c->description ?: 'Precision components for advanced engineering builds.' }}</p>
      <div class="go mono">Explore catalog →</div>
    </a>
    @endforeach
  </div>
</div></section>

<section class="partners"><div class="wrap"><div>ESP32</div><div>STMICRO</div><div>RASPBERRY PI</div><div>TEXAS INSTRUMENTS</div><div>NORDIC</div><div>INFINEON</div></div></section>

<!-- Featured products -->
<section class="block"><div class="wrap">
  <div class="head"><div>
    <div class="kicker">Active supply intelligence</div>
    <h2>Trending across regions</h2>
    <p>Live pricing and availability, aggregated from global sellers.</p>
  </div><a class="link" href="/products">Browse all →</a></div>
  <div class="grid g3">
    @foreach($prods->take(3) as $i => $p)
    @php $stock = $p->stock ?? (($i % 3 === 1) ? 'low' : 'in'); @endphp
    <div class="glass pcard">
      <div class="media">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.4"><rect x="4" y="4" width="16" height="16" rx="2"/><path d="M9 9h6v6H9zM4 10h2M4 14h2M18 10h2M18 14h2M10 4v2M14 4v2M10 18v2M14 18v2"/></svg>
        <span class="badge {{ $stock }}">{{ $stock==='low' ? 'LOW STOCK' : 'IN STOCK' }}</span>
      </div>
      <div class="pbody">
        <div class="prow1">
          <div><h4>{{ $p->name }}</h4><div class="mpn mono">MPN: {{ $p->mpn ?? $p->sku ?? '—' }}</div></div>
          <div class="price">${{ number_format((float)($p->base_price ?? 0), 2) }}<small class="mono">per unit</small></div>
        </div>
        <div class="specs mono">
          <div><span>Spec</span><b>{{ $p->spec ?? 'See datasheet' }}</b></div>
          <div><span>Availability</span><b>{{ $p->avail ?? 'Live inventory' }}</b></div>
        </div>
        <a class="pbtn" href="{{ $p->slug ? '/products/'.$p->slug : '/products' }}">View product →</a>
      </div>
    </div>
    @endforeach
  </div>
</div></section>

<!-- CTA -->
<section class="block"><div class="wrap">
  <div class="glass news">
    <h2>Build with <em>global</em> intelligence.</h2>
    <p>Join engineers and procurement teams sourcing across {{ ($st['marketplaces'] ?? 0) ?: 26 }} regional markets — with AI-curated hardware insights and live supply alerts.</p>
    <form action="/api/v1/newsletter/subscribe" method="post">
      <input type="email" name="email" placeholder="Professional email">
      <button type="submit">Get started</button>
    </form>
  </div>
</div></section>
</main>

<footer><div class="wrap">
  <div>
    <a class="brand" href="/" style="margin-bottom:22px"><span class="mk"><svg width="20" height="20" viewBox="0 0 32 32" fill="none"><path d="M9 22V10l14 12V10" stroke="#28d8fb" stroke-width="2.6" stroke-linecap="round" stroke-linejoin="round"/></svg></span>NeoGiga</a>
    <p class="copy mono">The global engineering marketplace. One canonical catalog, localized to every region.</p>
  </div>
  <div><h5>Marketplace</h5><ul><li><a href="/products">Products</a></li><li><a href="/categories">Categories</a></li><li><a href="/rfq">Bulk RFQ</a></li><li><a href="/sell-on-neogiga">Become a Seller</a></li></ul></div>
  <div><h5>Platform</h5><ul><li><a href="/ai-commerce">AI Builder</a></li><li><a href="/learn">Learning Hub</a></li><li><a href="/distributors">Distributors</a></li><li><a href="/admin/login">Business Login</a></li></ul></div>
  <div><h5>Regional editions</h5><ul><li><a href="https://neogiga.com">Global · neogiga.com</a></li><li><a href="https://neogiga.in">India · neogiga.in</a></li><li><a href="https://giganepal.com">Nepal · giganepal.com</a></li></ul></div>
</div>
<div class="footbar"><div class="wrap"><span>© {{ date('Y') }} NeoGiga. All rights reserved.</span><span class="mono">Precision engineering for global innovation.</span></div></div>
</footer>

<script>
document.addEventListener('keydown',function(e){if((e.metaKey||e.ctrlKey)&&e.key==='k'){e.preventDefault();document.querySelector('input[name=q]')?.focus();}});
</script>
</body>
</html>
