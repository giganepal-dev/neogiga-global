{{--
    Geo-location marketplace suggestion.

    SEO-safe by design: this is a progressive-enhancement JS modal, NOT a
    server-side IP redirect. It is rendered ONLY when the shared marketplace
    context already decided a suggestion is warranted (active regional edition
    detected for the visitor, not a crawler, not an excluded path, not already
    chosen/seen). Two variants:

      • mode "modal"  — visitor is on the GLOBAL edition; full accessible dialog
        with a visible countdown that auto-redirects to the regional edition.
      • mode "notice" — visitor is already on a DIFFERENT regional edition; a
        small optional notice, never auto-redirects, never traps focus.

    Both reuse the existing, tested marketplace.preference POST flow (httpOnly
    cookie + cross-domain redirect) — no new endpoint.
--}}
@php
    $rec = $marketplaceContext['recommended'] ?? null;
    $mode = $marketplaceContext['recommendation_mode'] ?? null;
    $showGeo = ($marketplaceContext['show_recommendation'] ?? false) && is_array($rec) && $mode;
@endphp
@if($showGeo)
@php
    $geoCfg = (array) config('neogiga_global.geo_routing', []);
    $override = (array) ($rec['geo'] ?? []);
    $copy = (array) ($geoCfg['copy'] ?? []);

    $countdown = (int) ($override['countdown'] ?? $geoCfg['countdown_seconds'] ?? 5);
    $countdown = max(2, min(60, $countdown));
    $autoRedirect = $mode === 'modal'
        && filter_var($override['auto_redirect'] ?? ($geoCfg['auto_redirect'] ?? true), FILTER_VALIDATE_BOOLEAN);

    $current = $marketplaceContext['current'] ?? null;
    $currentName = $current->name ?? 'NeoGiga Global';
    $currentCode = strtolower((string) ($current->code ?? 'global'));

    $regionName = $rec['name'] ?? 'the regional edition';
    $countryName = $rec['country_name'] ?: $regionName;
    $cc = strtoupper((string) ($rec['country_code'] ?? ''));
    $flag = strlen($cc) === 2
        ? mb_chr(0x1F1E6 + ord($cc[0]) - 65) . mb_chr(0x1F1E6 + ord($cc[1]) - 65)
        : '🌐';

    $repl = ['{country}' => $countryName, '{region}' => $regionName, '{current}' => $currentName];
    $title = strtr((string) ($override['title'] ?? $copy['title'] ?? 'Choose your NeoGiga marketplace'), $repl);
    $body = strtr((string) ($override['message'] ?? $copy['body'] ?? ''), $repl);
    $goLabel = strtr((string) ($copy['go'] ?? 'Go to {region}'), $repl);
    $stayLabel = strtr((string) ($copy['stay'] ?? 'Stay on {current}'), $repl);
    $noticeText = strtr((string) ($copy['notice'] ?? 'Switch to {region}?'), $repl);
    $returnPath = request()->getRequestUri();
    $prefRoute = route('marketplace.preference');
@endphp

<style nonce="{{ $csp_nonce ?? '' }}">
    .ng-geo-overlay{position:fixed;inset:0;z-index:1000;display:flex;align-items:center;justify-content:center;padding:20px;background:rgba(9,17,30,.55);backdrop-filter:blur(4px)}
    .ng-geo-overlay[hidden]{display:none}
    .ng-geo-dialog{width:min(460px,100%);background:var(--s1);color:var(--on);border:1px solid var(--line);border-radius:var(--r);box-shadow:0 30px 80px rgba(9,17,30,.35);padding:26px 24px 22px;position:relative;animation:ng-geo-in .22s ease-out}
    @media(prefers-reduced-motion:reduce){.ng-geo-dialog{animation:none}}
    @keyframes ng-geo-in{from{opacity:0;transform:translateY(10px) scale(.98)}to{opacity:1;transform:none}}
    .ng-geo-close{position:absolute;top:12px;right:12px;width:34px;height:34px;border-radius:9px;border:1px solid var(--line);background:transparent;color:var(--muted);font-size:1.1rem;line-height:1;display:grid;place-items:center}
    .ng-geo-close:hover{color:var(--on);border-color:var(--cyan)}
    .ng-geo-flag{font-size:2.4rem;line-height:1;margin-bottom:10px}
    .ng-geo-title{font-size:1.28rem;font-weight:800;letter-spacing:-.01em;margin:0 0 8px;color:var(--ink);padding-right:30px}
    .ng-geo-body{color:var(--muted);font-size:.94rem;margin:0 0 14px}
    .ng-geo-body strong{color:var(--on)}
    .ng-geo-count{display:flex;align-items:center;gap:8px;font-size:.85rem;color:var(--muted);background:var(--s2,#f4f6f9);border:1px solid var(--line);border-radius:10px;padding:9px 12px;margin-bottom:16px}
    .ng-geo-count[hidden]{display:none}
    .ng-geo-count b{color:var(--cyan);font-variant-numeric:tabular-nums;font-weight:800}
    .ng-geo-ring{width:16px;height:16px;border-radius:50%;border:2px solid var(--line);border-top-color:var(--cyan);animation:ng-geo-spin 1s linear infinite;flex:none}
    @media(prefers-reduced-motion:reduce){.ng-geo-ring{animation:none}}
    @keyframes ng-geo-spin{to{transform:rotate(360deg)}}
    .ng-geo-actions{display:flex;gap:10px;flex-wrap:wrap}
    .ng-geo-actions form{margin:0;flex:1 1 160px}
    .ng-geo-btn{width:100%;min-height:46px;border-radius:10px;padding:0 16px;font-weight:700;font-size:.92rem;border:1px solid transparent;display:inline-flex;align-items:center;justify-content:center;gap:6px}
    .ng-geo-primary{background:var(--cyan);color:#fff}
    .ng-geo-primary:hover{filter:brightness(1.08)}
    .ng-geo-ghost{background:transparent;color:var(--on);border-color:var(--line)}
    .ng-geo-ghost:hover{border-color:var(--cyan);color:var(--cyan)}
    /* soft cross-region notice */
    .ng-geo-notice{position:fixed;left:16px;right:16px;bottom:16px;z-index:900;max-width:420px;margin-inline:auto;background:var(--s1);color:var(--on);border:1px solid var(--line);border-radius:12px;box-shadow:0 18px 50px rgba(9,17,30,.25);padding:14px 16px;display:flex;align-items:center;gap:12px}
    .ng-geo-notice[hidden]{display:none}
    .ng-geo-notice .ng-geo-flag{font-size:1.6rem;margin:0}
    .ng-geo-notice p{margin:0;font-size:.86rem;color:var(--muted);flex:1}
    .ng-geo-notice-actions{display:flex;gap:6px;flex-wrap:wrap}
    .ng-geo-notice form{margin:0}
    .ng-geo-notice .ng-geo-btn{min-height:36px;font-size:.82rem;width:auto;padding:0 12px}
    .ng-geo-notice-dismiss{background:transparent;border:0;color:var(--faint);font-size:1.2rem;line-height:1;padding:2px 4px;align-self:flex-start}
    @media(min-width:560px){.ng-geo-notice{left:auto;right:20px;bottom:20px}}
</style>

@if($mode === 'modal')
<div class="ng-geo-overlay" id="ng-geo-overlay" role="presentation" hidden
     data-countdown="{{ $countdown }}" data-autoredirect="{{ $autoRedirect ? '1' : '0' }}" data-region="{{ $cc }}">
    <div class="ng-geo-dialog" role="dialog" aria-modal="true" aria-labelledby="ng-geo-title" aria-describedby="ng-geo-body">
        <button type="button" class="ng-geo-close" id="ng-geo-x" aria-label="Dismiss and stay on {{ $currentName }}">&times;</button>
        <div class="ng-geo-flag" aria-hidden="true">{{ $flag }}</div>
        <h2 class="ng-geo-title" id="ng-geo-title">{{ $title }}</h2>
        <p class="ng-geo-body" id="ng-geo-body">{{ $body }}</p>
        @if($autoRedirect)
        <div class="ng-geo-count" id="ng-geo-count" aria-live="polite">
            <span class="ng-geo-ring" aria-hidden="true"></span>
            <span>Redirecting to <strong>{{ $regionName }}</strong> in <b id="ng-geo-secs">{{ $countdown }}</b> seconds.</span>
        </div>
        @endif
        <div class="ng-geo-actions">
            <form method="post" action="{{ $prefRoute }}">
                @csrf
                <input type="hidden" name="marketplace" value="{{ $currentCode }}">
                <input type="hidden" name="return_path" value="{{ $returnPath }}">
                <input type="hidden" name="action" value="stay">
                <button type="submit" class="ng-geo-btn ng-geo-ghost" id="ng-geo-stay-btn">{{ $stayLabel }}</button>
            </form>
            <form method="post" action="{{ $prefRoute }}" id="ng-geo-go">
                @csrf
                <input type="hidden" name="marketplace" value="{{ $rec['code'] }}">
                <input type="hidden" name="return_path" value="{{ $returnPath }}">
                <input type="hidden" name="action" value="switch">
                <button type="submit" class="ng-geo-btn ng-geo-primary" id="ng-geo-go-btn">{{ $goLabel }} <span aria-hidden="true">&rarr;</span></button>
            </form>
        </div>
    </div>
</div>
@elseif($geoCfg['soft_notice'] ?? true)
<aside class="ng-geo-notice" id="ng-geo-notice" role="region" aria-label="Regional edition suggestion" hidden data-region="{{ $cc }}">
    <span class="ng-geo-flag" aria-hidden="true">{{ $flag }}</span>
    <p>{{ $noticeText }}</p>
    <div class="ng-geo-notice-actions">
        <form method="post" action="{{ $prefRoute }}">
            @csrf
            <input type="hidden" name="marketplace" value="{{ $rec['code'] }}">
            <input type="hidden" name="return_path" value="{{ $returnPath }}">
            <input type="hidden" name="action" value="switch">
            <button type="submit" class="ng-geo-btn ng-geo-primary">Switch to {{ $regionName }}</button>
        </form>
    </div>
    <button type="button" class="ng-geo-notice-dismiss" id="ng-geo-notice-x" aria-label="Dismiss, stay on {{ $currentName }}">&times;</button>
</aside>
@endif

<script nonce="{{ $csp_nonce ?? '' }}">
(function(){
    var dl=function(ev,extra){try{window.dataLayer=window.dataLayer||[];var o={event:ev};if(extra)for(var k in extra)o[k]=extra[k];window.dataLayer.push(o)}catch(e){}};
    var cookieDays={{ (int) max(30, (int) ($geoCfg['cookie_days'] ?? 180)) }};
    function markSeen(){try{document.cookie='{{ \App\Services\Marketplace\GlobalMarketplaceContextService::SEEN_COOKIE }}=1;path=/;max-age='+(cookieDays*86400)+';SameSite=Lax';sessionStorage.setItem('ng_geo_seen','1')}catch(e){}}
    // Loop prevention: if we arrived carrying the redirect marker, never re-show; strip it.
    try{
        var url=new URL(window.location.href);
        if(url.searchParams.has('marketplace_redirect')){
            url.searchParams.delete('marketplace_redirect');
            window.history.replaceState({},document.title,url.pathname+(url.search||'')+(url.hash||''));
            dl('marketplace_redirect_loop_prevented');
            return;
        }
    }catch(e){}
    try{if(sessionStorage.getItem('ng_geo_seen'))return}catch(e){}

    var overlay=document.getElementById('ng-geo-overlay');
    var notice=document.getElementById('ng-geo-notice');
    var region=(overlay||notice)?(overlay||notice).getAttribute('data-region'):'';

    /* -------- soft cross-region notice (no countdown, no focus trap) -------- */
    if(notice){
        notice.hidden=false;
        dl('marketplace_notice_shown',{ng_region:region});
        var nx=document.getElementById('ng-geo-notice-x');
        if(nx)nx.addEventListener('click',function(){notice.hidden=true;markSeen();dl('marketplace_notice_dismissed',{ng_region:region})});
        return;
    }

    if(!overlay)return;

    /* ----------------------------- full modal ----------------------------- */
    var dialog=overlay.querySelector('.ng-geo-dialog');
    var secsEl=document.getElementById('ng-geo-secs');
    var goForm=document.getElementById('ng-geo-go');
    var lastFocus=document.activeElement;
    var remaining=parseInt(overlay.getAttribute('data-countdown'),10)||5;
    var autoRedirect=overlay.getAttribute('data-autoredirect')==='1';
    var timer=null,paused=false,done=false;

    function focusables(){return dialog.querySelectorAll('button,[href],input:not([type=hidden]),[tabindex]:not([tabindex="-1"])')}
    function open(){
        overlay.hidden=false;
        try{sessionStorage.setItem('ng_geo_seen','1')}catch(e){}
        dl('marketplace_modal_shown',{ng_region:region,ng_auto_redirect:autoRedirect});
        var f=focusables();if(f.length)f[f.length-1].focus(); // land on primary "Go"
        if(autoRedirect)tick();
    }
    function tick(){
        clearInterval(timer);
        timer=setInterval(function(){
            if(paused||done)return;
            remaining--;
            if(secsEl)secsEl.textContent=remaining;
            if(remaining<=0){done=true;clearInterval(timer);dl('marketplace_auto_redirect_completed',{ng_region:region});if(goForm)goForm.submit()}
        },1000);
    }
    function stop(){done=true;clearInterval(timer)}
    function dismiss(){stop();overlay.hidden=true;markSeen();dl('marketplace_redirect_cancelled',{ng_region:region});if(lastFocus&&lastFocus.focus)lastFocus.focus()}

    // Pause the countdown whenever the user is reading/interacting with the modal.
    ['mouseenter','focusin','touchstart'].forEach(function(ev){dialog.addEventListener(ev,function(){paused=true},{passive:true})});
    ['mouseleave','focusout'].forEach(function(ev){dialog.addEventListener(ev,function(e){if(ev==='focusout'&&dialog.contains(e.relatedTarget))return;paused=false})});

    var xBtn=document.getElementById('ng-geo-x');
    if(xBtn)xBtn.addEventListener('click',dismiss);
    overlay.addEventListener('click',function(e){if(e.target===overlay)dismiss()});
    var stayBtn=document.getElementById('ng-geo-stay-btn');
    if(stayBtn)stayBtn.addEventListener('click',function(){stop();dl('marketplace_stay_selected',{ng_region:region})}); // form then submits
    var goBtn=document.getElementById('ng-geo-go-btn');
    if(goBtn)goBtn.addEventListener('click',function(){stop();dl('marketplace_go_selected',{ng_region:region})});

    overlay.addEventListener('keydown',function(e){
        if(e.key==='Escape'){e.preventDefault();dismiss();return}
        if(e.key!=='Tab')return;
        var f=focusables();if(!f.length)return;
        var first=f[0],last=f[f.length-1];
        if(e.shiftKey&&document.activeElement===first){e.preventDefault();last.focus()}
        else if(!e.shiftKey&&document.activeElement===last){e.preventDefault();first.focus()}
    });

    open();
})();
</script>
@endif
