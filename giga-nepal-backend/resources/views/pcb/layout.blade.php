<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>@yield('title', 'NeoGiga PCB')</title>
    <meta name="description" content="@yield('description', 'Secure PCB fabrication project workspaces, Gerber review, engineering quotes and production tracking from NeoGiga.')">
    <meta name="robots" content="@yield('robots', 'index,follow')">
    <link rel="canonical" href="{{ url()->current() }}">
    <link rel="icon" href="data:image/svg+xml,<svg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 32 32'><rect width='32' height='32' rx='6' fill='%23081527'/><path d='M8 9h16v14H8zM12 5v4m8-4v4m-8 14v4m8-4v4M4 13h4m-4 6h4m16-6h4m-4 6h4' stroke='%2319d3f5' stroke-width='2' fill='none'/></svg>">
    <style>
        :root{--navy:#071426;--navy2:#0d2741;--cyan:#16c6e7;--gold:#f2b632;--ink:#102033;--muted:#64748b;--soft:#f4f7fa;--line:#dbe4ec;--white:#fff;--green:#15803d;--red:#b91c1c;--amber:#a16207;--radius:8px;--max:1240px}
        *{box-sizing:border-box}html{scroll-behavior:smooth}body{margin:0;background:var(--soft);color:var(--ink);font-family:Inter,ui-sans-serif,system-ui,-apple-system,"Segoe UI",sans-serif;line-height:1.5;letter-spacing:0}a{color:inherit;text-decoration:none}img{display:block;max-width:100%}button,input,select,textarea{font:inherit;letter-spacing:0}button{cursor:pointer}.wrap{width:min(var(--max),calc(100% - 36px));margin-inline:auto}.skip{position:absolute;left:-999px;top:8px;background:#fff;padding:8px 12px;z-index:100}.skip:focus{left:8px}
        .topline{height:5px;background:linear-gradient(90deg,var(--cyan) 0 72%,var(--gold) 72%)}.site-head{background:var(--navy);color:#fff;border-bottom:1px solid rgba(255,255,255,.12);position:sticky;top:0;z-index:40}.head-row{min-height:72px;display:flex;align-items:center;gap:24px}.brand{display:flex;align-items:center;gap:11px;font-weight:850;white-space:nowrap}.brand-mark{width:38px;height:38px;border:1px solid rgba(22,198,231,.6);border-radius:7px;display:grid;place-items:center;color:var(--cyan);font-weight:900}.brand small{display:block;color:var(--gold);font-size:.62rem;text-transform:uppercase;letter-spacing:.13em}.main-nav{display:flex;align-items:center;gap:20px;margin-left:auto;color:#cbd8e7;font-size:.9rem;font-weight:700}.main-nav a:hover,.main-nav a[aria-current="page"]{color:#fff}.account{display:flex;align-items:center;gap:8px;margin-left:12px}.account-name{font-size:.82rem;color:#bdcadd;max-width:150px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap}
        .btn{display:inline-flex;align-items:center;justify-content:center;gap:7px;min-height:40px;padding:0 15px;border:1px solid transparent;border-radius:7px;font-weight:800;font-size:.88rem;text-align:center}.btn-primary{background:var(--cyan);color:#041622}.btn-gold{background:var(--gold);color:#1d1605}.btn-dark{background:var(--navy);color:#fff}.btn-light{background:#fff;color:var(--ink);border-color:var(--line)}.btn-danger{background:#fff;color:var(--red);border-color:#fecaca}.btn:hover{filter:brightness(1.04)}.btn:focus-visible,a:focus-visible,input:focus-visible,select:focus-visible,textarea:focus-visible,summary:focus-visible{outline:3px solid rgba(22,198,231,.38);outline-offset:2px}
        .page{padding:28px 0 64px}.page-head{display:flex;align-items:flex-start;justify-content:space-between;gap:20px;margin-bottom:20px}.eyebrow{color:#0e7490;font-size:.73rem;font-weight:900;text-transform:uppercase;letter-spacing:.1em}.page-title{font-size:clamp(1.8rem,4vw,2.75rem);line-height:1.05;margin:5px 0 6px}.lead{color:var(--muted);margin:0;max-width:70ch}.actions{display:flex;gap:8px;flex-wrap:wrap}.crumbs{display:flex;gap:7px;flex-wrap:wrap;color:var(--muted);font-size:.82rem;margin-bottom:18px}.crumbs a{color:#0e7490}
        .notice,.errors{border:1px solid #bae6fd;background:#ecfeff;color:#155e75;padding:12px 14px;border-radius:7px;margin-bottom:16px}.errors{border-color:#fecaca;background:#fff1f2;color:#991b1b}.errors ul{margin:0;padding-left:20px}.grid{display:grid;gap:16px}.kpis{grid-template-columns:repeat(3,minmax(0,1fr));margin-bottom:20px}.kpi{background:#fff;border:1px solid var(--line);border-radius:var(--radius);padding:16px}.kpi span{display:block;color:var(--muted);font-size:.75rem;font-weight:800;text-transform:uppercase}.kpi strong{display:block;font-size:1.9rem;margin-top:5px}.projects{grid-template-columns:repeat(auto-fill,minmax(280px,1fr))}.card{background:#fff;border:1px solid var(--line);border-radius:var(--radius);box-shadow:0 8px 24px rgba(15,31,48,.05)}.project-card{padding:18px;display:grid;gap:13px}.project-card:hover{border-color:#8addec}.project-card h2{font-size:1.05rem;margin:0}.project-meta{display:grid;grid-template-columns:repeat(2,1fr);gap:10px;color:var(--muted);font-size:.82rem}.project-meta b{display:block;color:var(--ink);font-size:.9rem;margin-top:2px}.card-head{display:flex;align-items:flex-start;justify-content:space-between;gap:14px;padding:16px 18px;border-bottom:1px solid var(--line)}.card-head h2{margin:0;font-size:1.05rem}.card-body{padding:18px}.split{grid-template-columns:minmax(0,1.45fr) minmax(300px,.75fr);align-items:start}.stack{display:grid;gap:16px}
        .badge{display:inline-flex;align-items:center;min-height:25px;padding:2px 9px;border-radius:999px;font-size:.71rem;font-weight:850;text-transform:capitalize;white-space:nowrap}.badge-draft,.badge-cancelled{background:#e8edf2;color:#475569}.badge-review,.badge-quote_pending,.badge-requirements_pending,.badge-files_ready{background:#fff3cd;color:#7c4a03}.badge-quoted,.badge-awaiting_approval{background:#cffafe;color:#155e75}.badge-ordered,.badge-manufacturing,.badge-design_in_progress{background:#dbeafe;color:#1d4ed8}.badge-inspection,.badge-design_review{background:#ede9fe;color:#6d28d9}.badge-shipped,.badge-completed,.badge-approved{background:#dcfce7;color:#166534}.badge-on_hold,.badge-rejected{background:#fee2e2;color:#991b1b}
        .form-grid{display:grid;grid-template-columns:repeat(2,minmax(0,1fr));gap:14px}.field{display:grid;gap:6px}.field.full{grid-column:1/-1}.field label{font-size:.76rem;font-weight:850;color:#475569;text-transform:uppercase}.field small{color:var(--muted)}.control{width:100%;min-height:42px;border:1px solid #cbd7e2;border-radius:7px;background:#fff;color:var(--ink);padding:9px 11px}.control:focus{border-color:#40cde5;outline:3px solid rgba(22,198,231,.15)}textarea.control{min-height:100px;resize:vertical}.check-grid{display:grid;grid-template-columns:repeat(2,minmax(0,1fr));gap:9px}.check{display:flex;align-items:center;gap:8px;border:1px solid var(--line);padding:9px 10px;border-radius:7px;background:#f8fafc}.form-actions{display:flex;gap:8px;flex-wrap:wrap;margin-top:16px}.divider{border:0;border-top:1px solid var(--line);margin:18px 0}details.advanced{border:1px solid var(--line);border-radius:7px;background:#fafcfe}details.advanced summary{padding:12px 14px;font-weight:850;cursor:pointer}details.advanced>div{padding:0 14px 14px}
        .table-wrap{overflow-x:auto}.table{width:100%;border-collapse:collapse;font-size:.86rem}.table th,.table td{text-align:left;padding:10px 12px;border-bottom:1px solid #e9eef3;vertical-align:top}.table th{font-size:.71rem;text-transform:uppercase;color:var(--muted);background:#f8fafc}.table tr:last-child td{border-bottom:0}.muted{color:var(--muted)}.file-name{font-weight:800;word-break:break-word}.file-security{display:flex;gap:5px;flex-wrap:wrap;margin-top:5px}.price{font-variant-numeric:tabular-nums;font-weight:900}.empty{padding:38px 20px;text-align:center;color:var(--muted)}.empty strong{display:block;color:var(--ink);margin-bottom:4px}.timeline{display:grid;gap:0}.timeline-item{display:grid;grid-template-columns:12px 1fr;gap:10px;padding-bottom:14px}.timeline-dot{width:8px;height:8px;border-radius:50%;background:var(--cyan);margin-top:7px}.timeline-item p{margin:0}.timeline-item time{font-size:.75rem;color:var(--muted)}
        .site-foot{background:#071426;color:#9fb0c2;padding:32px 0}.foot-row{display:flex;align-items:center;justify-content:space-between;gap:18px;flex-wrap:wrap;font-size:.82rem}.foot-links{display:flex;gap:16px;flex-wrap:wrap}.foot-links a:hover{color:#fff}
        @media(max-width:850px){.main-nav{display:none}.account{margin-left:auto}.split{grid-template-columns:1fr}.kpis{grid-template-columns:1fr}.head-row{gap:10px}.page-head{display:block}.page-head .actions{margin-top:14px}.form-grid{grid-template-columns:1fr}.field.full{grid-column:auto}}
        @media(max-width:600px){.wrap{width:min(var(--max),calc(100% - 22px))}.account-name{display:none}.account .btn-light{display:none}.head-row{min-height:62px}.brand small{display:none}.projects{grid-template-columns:1fr}.project-meta,.check-grid{grid-template-columns:1fr}.btn{width:100%}.actions,.form-actions{display:grid}.topline{height:4px}}
        @media(prefers-reduced-motion:reduce){*{scroll-behavior:auto!important}}
    </style>
    @stack('styles')
</head>
<body>
<a class="skip" href="#main">Skip to content</a>
<div class="topline" aria-hidden="true"></div>
<header class="site-head">
    <div class="wrap head-row">
        <a class="brand" href="/en" aria-label="NeoGiga PCB home">
            <span class="brand-mark">PCB</span>
            <span>NeoGiga<small>PCB Engineering</small></span>
        </a>
        <nav class="main-nav" aria-label="PCB portal navigation">
            @auth
                <a href="/en/projects" @if(request()->is('en/projects*')) aria-current="page" @endif>Projects</a>
                <a href="https://neogiga.com/en/products">Components</a>
                <a href="https://neogiga.com/en/rfq">Bulk RFQ</a>
            @else
                <a href="#capabilities">Capabilities</a>
                <a href="#workflow">Workflow</a>
                <a href="https://neogiga.com/en/products">Components</a>
            @endauth
        </nav>
        <div class="account">
            @auth
                <span class="account-name">{{ auth()->user()->name }}</span>
                <form method="post" action="/en/logout">@csrf<button class="btn btn-light" type="submit">Sign out</button></form>
            @else
                <a class="btn btn-light" href="/en/login">Sign in</a>
                <a class="btn btn-gold" href="/en/register">Start project</a>
            @endauth
        </div>
    </div>
</header>
<main id="main">
    @yield('content')
</main>
<footer class="site-foot">
    <div class="wrap foot-row">
        <span>NeoGiga PCB · Secure engineering workflow · Manual commercial review</span>
        <nav class="foot-links" aria-label="Footer">
            <a href="https://neogiga.com/en">NeoGiga Global</a>
            <a href="https://neogiga.com/en/products">Component catalog</a>
            <a href="mailto:pcb@neogiga.com">PCB support</a>
        </nav>
    </div>
</footer>
</body>
</html>
