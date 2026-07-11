<!DOCTYPE html>
<html lang="en" class="dark">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>NeoGiga — Engineer the Future at Scale</title>
<meta name="robots" content="noindex,nofollow">
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700&family=JetBrains+Mono:wght@500&display=swap" rel="stylesheet">
<link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:wght,FILL@100..700,0..1&display=block" rel="stylesheet">
<script src="https://cdn.tailwindcss.com?plugins=forms,container-queries"></script>
<script id="tailwind-config">try{tailwind.config={darkMode:"class",theme:{extend:{
  colors:{"tertiary":"#f9bd2c","tertiary-container":"#1e1300","on-tertiary-container":"#a37800","success":"#10B981",
  "surface-container":"#1d2023","surface-container-lowest":"#0b0f11","surface-container-low":"#181c1f","surface-dim":"#101417",
  "surface-elevated":"#111E2F","surface-bright":"#363a3d","surface-container-high":"#272a2d","secondary":"#80e5ff",
  "on-primary":"#253144","outline":"#8f9097","on-surface":"#e0e3e6","secondary-fixed-dim":"#28d8fb",
  "border-subtle":"rgba(255,255,255,0.08)","on-secondary":"#003640","surface":"#101417","background":"#101417",
  "outline-variant":"#44474c","error":"#EF4444","on-surface-variant":"#c5c6cd","primary-container":"#081527",
  "surface-variant":"#323538","primary":"#bbc7e0","secondary-container":"#00cdef","on-primary-container":"#737f96"},
  borderRadius:{"DEFAULT":"0.25rem","lg":"0.5rem","xl":"0.75rem","full":"9999px"},
  spacing:{"margin-desktop":"64px","gutter":"24px","sm":"12px","md":"24px","xs":"4px","xl":"80px","margin-mobile":"16px","lg":"48px","base":"8px"},
  fontFamily:{"label-sm":["Inter"],"body-md":["Inter"],"technical-data":["JetBrains Mono"],"headline-lg":["Inter"],"display-lg":["Inter"]},
  fontSize:{"label-sm":["12px",{"lineHeight":"16px","fontWeight":"600"}],"body-md":["16px",{"lineHeight":"24px","fontWeight":"400"}],
  "technical-data":["14px",{"lineHeight":"20px","letterSpacing":"0.02em","fontWeight":"500"}],
  "headline-lg":["32px",{"lineHeight":"40px","letterSpacing":"-0.01em","fontWeight":"600"}],
  "display-lg":["64px",{"lineHeight":"72px","letterSpacing":"-0.02em","fontWeight":"700"}]}}}}catch(_e){}</script>
<style>
  body{background:#101417;color:#e0e3e6;font-family:Inter,ui-sans-serif,system-ui,sans-serif}
  .font-technical-data{font-family:'JetBrains Mono',ui-monospace,monospace}
  .material-symbols-outlined{font-family:'Material Symbols Outlined';font-weight:normal;font-style:normal;line-height:1;letter-spacing:normal;text-transform:none;display:inline-block;white-space:nowrap;direction:ltr}
  .glass-card{background:rgba(255,255,255,.03);border:1px solid rgba(255,255,255,.08);backdrop-filter:blur(12px)}
  .hero-gradient{background:radial-gradient(circle at 20% 15%,rgba(128,229,255,.14),transparent 40rem),radial-gradient(circle at 82% 10%,rgba(249,189,44,.08),transparent 34rem),linear-gradient(180deg,#0b1220,#101417 60%,#0b0f11)}
  .text-glow{text-shadow:0 0 40px rgba(128,229,255,.25)}
</style>
</head>
<body class="font-body-md text-body-md overflow-x-hidden">
@php
  $catIcons = ['memory','smart_toy','sensors','bolt','developer_board','cable','battery_charging_full','precision_manufacturing'];
  $sampleCats = collect([
    (object)['name'=>'Semiconductors','description'=>'MCUs, FPGAs and specialized ASICs for high-performance computing.','slug'=>'semiconductors'],
    (object)['name'=>'Robotics','description'=>'Servo controllers, lidar modules and kinetic drive systems.','slug'=>'robotics'],
    (object)['name'=>'IoT & Wireless','description'=>'LoRaWAN, 5G modules and multi-protocol mesh nodes.','slug'=>'iot-wireless'],
    (object)['name'=>'Power Tech','description'=>'GaN power stages, BMS controllers and energy storage.','slug'=>'power-technology'],
  ]);
  $cats = ($categories ?? collect())->isNotEmpty() ? $categories : $sampleCats;
  $sampleProducts = collect([
    (object)['name'=>'X-series Ultra Core v4','mpn'=>'AG-990-XC4-24','base_price'=>1240,'slug'=>null,'spec'=>'RISC-V 128-bit','avail'=>'4,290 units','stock'=>'in'],
    (object)['name'=>'OpticLidar Pro H2','mpn'=>'SN-OP-H2-88','base_price'=>895.5,'slug'=>null,'spec'=>'250m @ 0.1° res','avail'=>'142 units','stock'=>'low'],
    (object)['name'=>'AeroTorque HV 450','mpn'=>'DR-MO-450-HV','base_price'=>315,'slug'=>null,'spec'=>'18.5kg thrust','avail'=>'880 units','stock'=>'in'],
  ]);
  $prods = ($products ?? collect())->isNotEmpty() ? $products : $sampleProducts;
@endphp

<header class="fixed top-0 inset-x-0 z-50 border-b border-subtle backdrop-blur-md bg-surface/80">
<div class="flex justify-between items-center px-margin-mobile md:px-margin-desktop h-20 max-w-[1440px] mx-auto">
<a href="/" class="flex items-center gap-3">
<span class="w-9 h-9 rounded-lg grid place-items-center bg-secondary-container/20 border border-secondary/40 text-secondary"><svg width="20" height="20" viewBox="0 0 32 32" fill="none"><path d="M9 22V10l14 12V10" stroke="#28d8fb" stroke-width="2.6" stroke-linecap="round" stroke-linejoin="round"/></svg></span>
<span class="font-headline-lg text-2xl font-bold text-primary tracking-tighter">NeoGiga</span>
</a>
<nav class="hidden md:flex items-center gap-8">
<a href="/products" class="font-label-sm text-label-sm text-on-surface-variant hover:text-secondary transition-colors">Products</a>
<a href="/categories" class="font-label-sm text-label-sm text-on-surface-variant hover:text-secondary transition-colors">Categories</a>
<a href="/ai-commerce" class="font-label-sm text-label-sm text-on-surface-variant hover:text-secondary transition-colors">AI Builder</a>
<a href="/rfq" class="font-label-sm text-label-sm text-on-surface-variant hover:text-secondary transition-colors">RFQ</a>
<a href="/sell-on-neogiga" class="font-label-sm text-label-sm text-on-surface-variant hover:text-secondary transition-colors">Sell</a>
</nav>
<div class="flex items-center gap-4">
<a href="/admin/login" class="font-label-sm text-label-sm text-on-surface-variant hover:text-secondary transition-colors">Login</a>
<a href="/products" class="bg-secondary text-on-secondary px-6 py-2 rounded-full font-label-sm text-label-sm hover:brightness-110 hover:scale-105 active:scale-95 transition-all">Get Started</a>
</div>
</div>
</header>

<main class="pt-20">
<!-- Hero -->
<section class="relative min-h-[780px] flex flex-col items-center justify-center px-margin-mobile md:px-margin-desktop hero-gradient overflow-hidden">
<div class="relative z-10 w-full max-w-4xl text-center">
<div class="inline-flex items-center gap-2 mb-6 px-3 py-1 rounded-full border border-subtle text-on-surface-variant font-technical-data text-xs">
<span class="w-2 h-2 rounded-full bg-success"></span> Global engineering marketplace · single MPN catalog
</div>
<h1 class="font-display-lg text-5xl md:text-display-lg text-glow mb-8 tracking-tight leading-tight">
Engineer the <span class="text-secondary">Future</span> at Scale
</h1>
<form action="/products" method="get" class="relative w-full group">
<div class="absolute -inset-1 bg-gradient-to-r from-secondary to-primary rounded-full blur opacity-25 group-focus-within:opacity-50 transition duration-1000"></div>
<div class="relative flex items-center bg-surface-container-low border border-subtle rounded-full px-6 md:px-8 py-4 md:py-6 shadow-2xl">
<span class="material-symbols-outlined text-secondary mr-3 md:mr-4" style="font-variation-settings:'FILL' 1;">auto_awesome</span>
<input name="q" class="w-full bg-transparent border-none focus:ring-0 text-lg md:text-2xl font-technical-data placeholder:text-on-surface-variant/40" placeholder='Try "ESP32 dev board" or "LiFePO4 cell"' type="text">
<div class="flex items-center gap-3">
<span class="hidden md:block font-technical-data text-on-surface-variant/50 text-xs bg-surface-variant px-2 py-1 rounded">CMD + K</span>
<button type="submit" class="bg-secondary text-on-secondary px-5 md:px-6 py-2.5 md:py-3 rounded-full font-label-sm hover:brightness-110 transition-all flex items-center gap-2">
<span class="material-symbols-outlined text-sm">search</span> Search
</button>
</div>
</div>
</form>
<p class="mt-8 text-on-surface-variant font-technical-data text-sm">
Cross-referenced with global technical datasheets and live regional inventory.
</p>
</div>
</section>

<!-- Technical Categories -->
<section class="py-xl px-margin-mobile md:px-margin-desktop max-w-[1440px] mx-auto">
<div class="flex items-end justify-between mb-lg">
<div>
<h2 class="font-headline-lg text-headline-lg mb-2">Technical Categories</h2>
<p class="text-on-surface-variant font-body-md">Precision components for every engineering vertical.</p>
</div>
<a href="/categories" class="text-secondary font-label-sm flex items-center gap-2 hover:underline whitespace-nowrap">View all <span class="material-symbols-outlined text-sm">arrow_forward</span></a>
</div>
<div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-4 gap-gutter">
@foreach($cats->take(4) as $i => $c)
<a href="/products?category={{ $c->slug ?? '' }}" class="glass-card p-8 rounded-2xl group hover:border-secondary/50 transition-all cursor-pointer block">
<div class="w-12 h-12 bg-secondary/10 rounded-xl flex items-center justify-center mb-6 text-secondary group-hover:scale-110 transition-transform">
<span class="material-symbols-outlined" style="font-variation-settings:'FILL' 1;">{{ $catIcons[$i % count($catIcons)] }}</span>
</div>
<h3 class="font-headline-lg text-xl mb-2">{{ $c->name }}</h3>
<p class="text-on-surface-variant text-sm mb-4 line-clamp-2">{{ $c->description ?: 'Precision components for advanced engineering builds.' }}</p>
<div class="font-technical-data text-secondary text-xs">Explore catalog →</div>
</a>
@endforeach
</div>
</section>

<!-- Partner strip -->
<section class="py-lg border-y border-subtle bg-surface-container-lowest">
<div class="px-margin-mobile md:px-margin-desktop max-w-[1440px] mx-auto flex flex-wrap items-center justify-between gap-8 md:gap-xl opacity-50 hover:opacity-100 transition-all duration-500 font-bold text-on-surface-variant text-sm md:text-lg tracking-wide">
<div>ESP32</div><div>STMICRO</div><div>RASPBERRY PI</div><div>TEXAS INSTRUMENTS</div><div>NORDIC</div>
</div>
</section>

<!-- Featured products -->
<section class="py-xl px-margin-mobile md:px-margin-desktop max-w-[1440px] mx-auto">
<div class="flex items-center justify-between mb-lg">
<h2 class="font-headline-lg text-headline-lg">Active Supply Intelligence</h2>
<a href="/products" class="text-secondary font-label-sm flex items-center gap-2 hover:underline whitespace-nowrap">Browse all <span class="material-symbols-outlined text-sm">arrow_forward</span></a>
</div>
<div class="grid grid-cols-1 md:grid-cols-3 gap-gutter">
@foreach($prods->take(3) as $i => $p)
@php $stock = $p->stock ?? (($i % 3 === 1) ? 'low' : 'in'); @endphp
<div class="glass-card rounded-2xl overflow-hidden group flex flex-col">
<div class="relative h-56 overflow-hidden bg-surface-container grid place-items-center">
<span class="material-symbols-outlined text-6xl text-on-surface-variant/30" style="font-variation-settings:'FILL' 1;">{{ $catIcons[$i % count($catIcons)] }}</span>
<div class="absolute top-4 left-4 {{ $stock==='low' ? 'bg-tertiary/20 text-tertiary border-tertiary/30' : 'bg-success/20 text-success border-success/30' }} text-xs font-technical-data px-2 py-1 rounded border backdrop-blur-md">{{ $stock==='low' ? 'LOW STOCK' : 'IN STOCK' }}</div>
</div>
<div class="p-6 flex flex-col flex-1">
<div class="flex justify-between items-start mb-4 gap-3">
<div>
<h4 class="font-headline-lg text-lg mb-1">{{ $p->name }}</h4>
<p class="font-technical-data text-on-surface-variant text-xs">MPN: {{ $p->mpn ?? $p->sku ?? '—' }}</p>
</div>
<div class="text-right shrink-0">
<div class="text-secondary font-headline-lg text-xl">${{ number_format((float)($p->base_price ?? 0), 2) }}</div>
<div class="text-on-surface-variant text-[10px] font-technical-data">per unit</div>
</div>
</div>
<div class="space-y-3 mb-6">
<div class="flex justify-between text-xs font-technical-data"><span class="text-on-surface-variant">Spec</span><span>{{ $p->spec ?? 'See datasheet' }}</span></div>
<div class="flex justify-between text-xs font-technical-data"><span class="text-on-surface-variant">Availability</span><span>{{ $p->avail ?? 'Live inventory' }}</span></div>
</div>
<a href="{{ $p->slug ? '/products/'.$p->slug : '/products' }}" class="mt-auto w-full py-3 bg-surface-variant hover:bg-secondary hover:text-on-secondary transition-all rounded-lg font-label-sm flex items-center justify-center gap-2">
<span class="material-symbols-outlined text-sm">shopping_cart</span> View product
</a>
</div>
</div>
@endforeach
</div>
</section>

<!-- Newsletter -->
<section class="py-xl px-margin-mobile md:px-margin-desktop max-w-[1440px] mx-auto">
<div class="glass-card rounded-[2rem] p-10 md:p-20 relative overflow-hidden">
<div class="relative z-10 max-w-2xl">
<h2 class="font-display-lg text-3xl md:text-4xl mb-6">Design with <span class="text-secondary">Intelligence</span>.</h2>
<p class="text-on-surface-variant text-base md:text-lg mb-8 leading-relaxed">Get AI-curated hardware insights and regional supply alerts for your builds.</p>
<form action="/api/v1/newsletter/subscribe" method="post" class="flex flex-col sm:flex-row gap-4">
<input name="email" class="flex-1 bg-surface-container border border-subtle rounded-xl px-6 py-4 focus:ring-2 focus:ring-secondary focus:border-transparent" placeholder="Professional email" type="email">
<button type="submit" class="bg-secondary text-on-secondary px-8 py-4 rounded-xl font-label-sm hover:brightness-110 hover:scale-105 active:scale-95 transition-all">Subscribe</button>
</form>
</div>
</div>
</section>
</main>

<footer class="w-full py-xl px-margin-mobile md:px-margin-desktop grid grid-cols-2 md:grid-cols-4 gap-gutter max-w-[1440px] mx-auto bg-surface-container-lowest border-t border-subtle mt-xl">
<div class="col-span-2 md:col-span-1">
<div class="font-headline-lg text-2xl font-bold text-primary mb-6">NeoGiga</div>
<p class="text-on-surface-variant font-technical-data text-xs leading-relaxed">© {{ date('Y') }} NeoGiga. Precision engineering for global innovation.</p>
</div>
<div><h5 class="text-on-surface font-label-sm mb-6">Catalog</h5><ul class="space-y-4">
<li><a class="text-on-surface-variant font-technical-data text-sm hover:text-secondary transition-all" href="/products">Products</a></li>
<li><a class="text-on-surface-variant font-technical-data text-sm hover:text-secondary transition-all" href="/categories">Categories</a></li>
<li><a class="text-on-surface-variant font-technical-data text-sm hover:text-secondary transition-all" href="/rfq">Bulk RFQ</a></li>
</ul></div>
<div><h5 class="text-on-surface font-label-sm mb-6">Platform</h5><ul class="space-y-4">
<li><a class="text-on-surface-variant font-technical-data text-sm hover:text-secondary transition-all" href="/ai-commerce">AI Builder</a></li>
<li><a class="text-on-surface-variant font-technical-data text-sm hover:text-secondary transition-all" href="/learn">Learning Hub</a></li>
<li><a class="text-on-surface-variant font-technical-data text-sm hover:text-secondary transition-all" href="/sell-on-neogiga">Become a Seller</a></li>
</ul></div>
<div><h5 class="text-on-surface font-label-sm mb-6">Editions</h5><ul class="space-y-4">
<li><a class="text-on-surface-variant font-technical-data text-sm hover:text-secondary transition-all" href="https://neogiga.com">Global · neogiga.com</a></li>
<li><a class="text-on-surface-variant font-technical-data text-sm hover:text-secondary transition-all" href="https://neogiga.in">India · neogiga.in</a></li>
<li><a class="text-on-surface-variant font-technical-data text-sm hover:text-secondary transition-all" href="https://giganepal.com">Nepal · giganepal.com</a></li>
</ul></div>
</footer>

<script>
  document.addEventListener('keydown',function(e){
    if((e.metaKey||e.ctrlKey)&&e.key==='k'){e.preventDefault();document.querySelector('input[name=q]')?.focus();}
  });
</script>
</body>
</html>
