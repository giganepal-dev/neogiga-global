@extends('admin.layout')
@section('title', $m->name)
@section('crumb', 'Marketplace configuration · ' . $m->code)
@section('page_actions')
    <a class="btn" href="/admin/marketplaces">← All marketplaces</a>
@endsection
@section('content')
@php $tab = session('tab', 'general'); @endphp
<style>
    .mtabs{display:flex;flex-wrap:wrap;gap:6px;margin-bottom:16px}
    .mtabs button{border:1px solid var(--line);background:#fff;border-radius:8px;padding:8px 14px;font-weight:700;color:var(--slate);cursor:pointer}
    .mtabs button.on{background:var(--navy);color:#fff;border-color:var(--navy)}
    .mtab{display:none}.mtab.on{display:block}
    .frm{display:grid;gap:14px;max-width:760px}
    .frm label{display:block;font-weight:700;font-size:.82rem;margin-bottom:4px;color:var(--slate)}
    .frm input[type=text],.frm input[type=url],.frm input[type=datetime-local],.frm select,.frm textarea{width:100%;border:1px solid var(--line);border-radius:8px;padding:9px 10px;font:inherit}
    .frm textarea{min-height:80px}
    .frm .row{display:grid;grid-template-columns:1fr 1fr;gap:14px}
    .frm .chk{display:flex;align-items:center;gap:8px;font-weight:600}
    .counter{font-size:.72rem;color:var(--muted)}
    .checklist li{margin:3px 0}.pass{color:#166534}.fail{color:#991b1b}
    .inline-actions{display:flex;flex-wrap:wrap;gap:8px;margin-top:8px}
    .inline-actions form{margin:0}
</style>

<div class="mtabs" role="tablist">
    @foreach(['general'=>'General','domain'=>'Domain & Routing','status'=>'Status & Access','seo'=>'SEO','branding'=>'Branding','advanced'=>'Advanced'] as $k=>$label)
        <button type="button" class="mtab-btn {{ $tab===$k?'on':'' }}" data-tab="{{ $k }}">{{ $label }}</button>
    @endforeach
</div>

{{-- status summary badges --}}
<div class="card" style="margin-bottom:16px"><div class="card-b" style="display:flex;flex-wrap:wrap;gap:8px">
    @if($m->is_active)<span class="badge b-ok">Active</span>@else<span class="badge b-muted">Inactive</span>@endif
    @if($m->is_visible)<span class="badge b-ok">Visible</span>@else<span class="badge b-warn">Hidden</span>@endif
    @if($m->maintenance_mode)<span class="badge b-warn">Maintenance</span>@endif
    <span class="badge {{ $m->domain_verified_at?'b-ok':'b-warn' }}">Domain {{ $m->domain_verified_at?'verified':'pending' }}</span>
    <span class="badge {{ $m->indexable?'b-ok':'b-muted' }}">{{ $m->indexable?'Indexable':'Noindex' }}</span>
    <span class="badge {{ (!empty($m->seo_title)&&!empty($m->seo_description))?'b-ok':'b-warn' }}">SEO {{ (!empty($m->seo_title)&&!empty($m->seo_description))?'complete':'incomplete' }}</span>
</div></div>

{{-- GENERAL --}}
<div class="mtab {{ $tab==='general'?'on':'' }}" data-tab="general">
    <div class="card"><div class="card-b">
        <form class="frm" method="post" action="/admin/marketplaces/{{ $m->id }}/config"><input type="hidden" name="tab" value="general">@csrf
            <div><label>Marketplace name</label><input type="text" name="name" value="{{ old('name',$m->name) }}" required></div>
            <div class="row">
                <div><label>Code</label><input type="text" value="{{ $m->code }}" disabled></div>
                <div><label>Country</label><select name="country_id"><option value="">—</option>@foreach($countries as $c)<option value="{{ $c->id }}" @selected($m->country_id==$c->id)>{{ $c->name }}</option>@endforeach</select></div>
            </div>
            <div class="row">
                <div><label>Currency</label><select name="currency_id"><option value="">—</option>@foreach($currencies as $cur)<option value="{{ $cur->id }}" @selected($m->currency_id==$cur->id)>{{ $cur->code }}</option>@endforeach</select></div>
                <div><label>Timezone</label><input type="text" name="timezone" value="{{ old('timezone',$m->timezone) }}"></div>
            </div>
            <div class="row">
                <div><label>Locale</label><input type="text" name="locale" value="{{ old('locale',$m->locale) }}"></div>
                <div><label>Default language</label><input type="text" name="default_language" value="{{ old('default_language',$m->default_language) }}"></div>
            </div>
            <div><label>Short description</label><textarea name="short_description">{{ old('short_description',$m->short_description) }}</textarea></div>
            <div><button class="btn btn-primary" type="submit">Save general</button></div>
        </form>
    </div></div>
</div>

{{-- DOMAIN --}}
<div class="mtab {{ $tab==='domain'?'on':'' }}" data-tab="domain">
    <div class="card"><div class="card-b">
        <form class="frm" method="post" action="/admin/marketplaces/{{ $m->id }}/config"><input type="hidden" name="tab" value="domain">@csrf
            <div class="row">
                <div><label>Domain mode</label><select name="domain_mode">@foreach(['custom_domain','subdomain','path'] as $mode)<option value="{{ $mode }}" @selected($m->domain_mode===$mode)>{{ $mode }}</option>@endforeach</select></div>
                <div><label>Suggested country domain</label><input type="text" value="{{ $suggestedDomain ?? '—' }}" disabled></div>
            </div>
            <div class="row">
                <div><label>Primary domain @if($m->is_domain_locked)<span class="badge b-warn">locked</span>@endif</label><input type="text" name="domain" value="{{ old('domain',$m->domain) }}" @disabled($m->is_domain_locked && (auth()->user()->role->name ?? null)!=='super_admin')></div>
                <div><label>Generated domain</label><input type="text" value="{{ $m->generated_domain ?? '—' }}" disabled></div>
            </div>
            <div class="row">
                <div><label>Canonical domain</label><input type="text" name="canonical_domain" value="{{ old('canonical_domain',$m->canonical_domain) }}"></div>
                <div><label>Domain prefix</label><input type="text" name="domain_prefix" value="{{ old('domain_prefix',$m->domain_prefix) }}"></div>
            </div>
            <div><label>WWW redirect</label><select name="www_redirect_mode">@foreach(['none','www_to_non_www','non_www_to_www'] as $w)<option value="{{ $w }}" @selected($m->www_redirect_mode===$w)>{{ $w }}</option>@endforeach</select></div>
            <label class="chk"><input type="checkbox" name="force_https" value="1" @checked($m->force_https)> Force HTTPS</label>
            <label class="chk"><input type="checkbox" name="redirect_to_canonical" value="1" @checked($m->redirect_to_canonical)> Redirect to canonical</label>
            @if((auth()->user()->role->name ?? null)==='super_admin')
                <label class="chk"><input type="checkbox" name="is_domain_locked" value="1" @checked($m->is_domain_locked)> Lock domain (Super Admin)</label>
            @endif
            <div><button class="btn btn-primary" type="submit">Save domain</button></div>
        </form>
        <div class="inline-actions">
            <form method="post" action="/admin/marketplaces/{{ $m->id }}/generate-domain">@csrf<button class="btn" type="submit">Suggest domain</button></form>
            <form method="post" action="/admin/marketplaces/{{ $m->id }}/generate-domain">@csrf<input type="hidden" name="confirm" value="1"><button class="btn" type="submit">Generate & save</button></form>
            <form method="post" action="/admin/marketplaces/{{ $m->id }}/verify-domain">@csrf<button class="btn" type="submit">Verify domain (DNS)</button></form>
        </div>
    </div></div>
</div>

{{-- STATUS --}}
<div class="mtab {{ $tab==='status'?'on':'' }}" data-tab="status">
    <div class="card"><div class="card-b">
        <h3>Pre-launch checklist</h3>
        <ul class="checklist">
            @foreach($validation['checklist'] as $c)
                <li class="{{ $c['passed']?'pass':'fail' }}">{{ $c['passed']?'✓':'✕' }} {{ $c['label'] }} @if(!$c['passed'] && $c['critical'])<span class="badge b-warn">critical</span>@endif</li>
            @endforeach
        </ul>
        <div class="inline-actions">
            <form method="post" action="/admin/marketplaces/{{ $m->id }}/enable">@csrf<button class="btn btn-primary" type="submit" @disabled(!$validation['can_activate'] && (auth()->user()->role->name ?? null)!=='super_admin')>Enable</button></form>
            @if((auth()->user()->role->name ?? null)==='super_admin' && !$validation['can_activate'])
                <form method="post" action="/admin/marketplaces/{{ $m->id }}/enable">@csrf<input type="hidden" name="force" value="1"><button class="btn" type="submit" onclick="return confirm('Force-enable despite failed validation?')">Force enable</button></form>
            @endif
            <form method="post" action="/admin/marketplaces/{{ $m->id }}/disable" style="display:flex;gap:6px">@csrf<input type="text" name="reason" placeholder="Reason to disable" required><button class="btn" type="submit">Disable</button></form>
        </div>
        <hr style="margin:16px 0;border:none;border-top:1px solid var(--line)">
        <form class="frm" method="post" action="/admin/marketplaces/{{ $m->id }}/config"><input type="hidden" name="tab" value="status">@csrf
            <label class="chk"><input type="checkbox" name="allow_customer_registration" value="1" @checked($m->allow_customer_registration)> Allow customer registration</label>
            <label class="chk"><input type="checkbox" name="allow_vendor_registration" value="1" @checked($m->allow_vendor_registration)> Allow vendor registration</label>
            <label class="chk"><input type="checkbox" name="checkout_enabled" value="1" @checked($m->checkout_enabled)> Allow checkout</label>
            <label class="chk"><input type="checkbox" name="maintenance_mode" value="1" @checked($m->maintenance_mode)> Maintenance mode</label>
            <div><label>Launch date/time</label><input type="datetime-local" name="launch_at" value="{{ old('launch_at', optional($m->launch_at)->format('Y-m-d\TH:i')) }}"></div>
            <div><label>Maintenance message</label><textarea name="maintenance_message">{{ old('maintenance_message',$m->maintenance_message) }}</textarea></div>
            <div><button class="btn btn-primary" type="submit">Save access</button></div>
        </form>
    </div></div>
</div>

{{-- SEO --}}
<div class="mtab {{ $tab==='seo'?'on':'' }}" data-tab="seo">
    <div class="card"><div class="card-b">
        <div class="inline-actions" style="margin-bottom:12px">
            <form method="post" action="/admin/marketplaces/{{ $m->id }}/generate-seo">@csrf<button class="btn" type="submit">Generate SEO</button></form>
            <form method="post" action="/admin/marketplaces/{{ $m->id }}/generate-seo">@csrf<input type="hidden" name="only_empty" value="1"><button class="btn" type="submit">Fill empty only</button></form>
        </div>
        <form class="frm" method="post" action="/admin/marketplaces/{{ $m->id }}/config"><input type="hidden" name="tab" value="seo">@csrf
            <div><label>SEO title <span class="counter" id="tc"></span></label><input type="text" name="seo_title" id="seo_title" value="{{ old('seo_title',$m->seo_title) }}"></div>
            <div><label>Meta description <span class="counter" id="dc"></span></label><textarea name="seo_description" id="seo_description">{{ old('seo_description',$m->seo_description) }}</textarea></div>
            <div><label>Keywords</label><input type="text" name="seo_keywords" value="{{ old('seo_keywords',$m->seo_keywords) }}"></div>
            <div><label>H1 heading</label><input type="text" name="seo_h1" value="{{ old('seo_h1',$m->seo_h1) }}"></div>
            <div><label>Canonical URL</label><input type="url" name="seo_canonical_url" value="{{ old('seo_canonical_url',$m->seo_canonical_url) }}"></div>
            <div><label>Robots</label><input type="text" name="seo_robots" value="{{ old('seo_robots',$m->seo_robots) }}"></div>
            <div class="row">
                <div><label>OG title</label><input type="text" name="seo_og_title" value="{{ old('seo_og_title',$m->seo_og_title) }}"></div>
                <div><label>OG image</label><input type="text" name="seo_og_image" value="{{ old('seo_og_image',$m->seo_og_image) }}"></div>
            </div>
            <div><label>OG description</label><textarea name="seo_og_description">{{ old('seo_og_description',$m->seo_og_description) }}</textarea></div>
            <div class="row">
                <div><label>Twitter title</label><input type="text" name="seo_twitter_title" value="{{ old('seo_twitter_title',$m->seo_twitter_title) }}"></div>
                <div><label>Twitter image</label><input type="text" name="seo_twitter_image" value="{{ old('seo_twitter_image',$m->seo_twitter_image) }}"></div>
            </div>
            <div><label>Twitter description</label><textarea name="seo_twitter_description">{{ old('seo_twitter_description',$m->seo_twitter_description) }}</textarea></div>
            <label class="chk"><input type="checkbox" name="indexable" value="1" @checked($m->indexable)> Indexable (index,follow)</label>
            <label class="chk"><input type="checkbox" name="sitemap_enabled" value="1" @checked($m->sitemap_enabled)> Include in sitemap</label>
            <label class="chk"><input type="checkbox" name="hreflang_enabled" value="1" @checked($m->hreflang_enabled)> Emit hreflang</label>
            <div><button class="btn btn-primary" type="submit">Save SEO</button></div>
        </form>
    </div></div>
</div>

{{-- BRANDING --}}
<div class="mtab {{ $tab==='branding'?'on':'' }}" data-tab="branding">
    <div class="card"><div class="card-b">
        <form class="frm" method="post" action="/admin/marketplaces/{{ $m->id }}/config"><input type="hidden" name="tab" value="branding">@csrf
            <div class="row">
                <div><label>Logo URL</label><input type="text" name="logo" value="{{ old('logo',$m->logo) }}"></div>
                <div><label>Favicon URL</label><input type="text" name="favicon" value="{{ old('favicon',$m->favicon) }}"></div>
            </div>
            <div><label>Banner image URL</label><input type="text" name="banner_image" value="{{ old('banner_image',$m->banner_image) }}"></div>
            <div><label>Homepage heading</label><input type="text" name="homepage_heading" value="{{ old('homepage_heading',$m->homepage_heading) }}"></div>
            <div><label>Homepage subheading</label><input type="text" name="homepage_subheading" value="{{ old('homepage_subheading',$m->homepage_subheading) }}"></div>
            <div><label>Marketplace description</label><textarea name="marketplace_description">{{ old('marketplace_description',$m->marketplace_description) }}</textarea></div>
            <div><button class="btn btn-primary" type="submit">Save branding</button></div>
        </form>
    </div></div>
</div>

{{-- ADVANCED --}}
<div class="mtab {{ $tab==='advanced'?'on':'' }}" data-tab="advanced">
    <div class="card"><div class="card-b">
        <form class="frm" method="post" action="/admin/marketplaces/{{ $m->id }}/config"><input type="hidden" name="tab" value="advanced">@csrf
            @if((auth()->user()->role->name ?? null)==='super_admin')
                <div><label>Header scripts (Super Admin)</label><textarea name="seo_header_scripts">{{ old('seo_header_scripts',$m->seo_header_scripts) }}</textarea></div>
                <div><label>Footer scripts (Super Admin)</label><textarea name="seo_footer_scripts">{{ old('seo_footer_scripts',$m->seo_footer_scripts) }}</textarea></div>
            @else
                <p class="sub">Custom scripts require Super Admin.</p>
            @endif
            <div><label>Settings (JSON)</label><textarea name="settings">{{ old('settings', json_encode($m->settings ?? [], JSON_PRETTY_PRINT)) }}</textarea></div>
            <div><button class="btn btn-primary" type="submit">Save advanced</button></div>
        </form>
        <div class="inline-actions"><form method="post" action="/admin/marketplaces/{{ $m->id }}/clear-cache">@csrf<button class="btn" type="submit">Clear cache</button></form></div>
        <hr style="margin:16px 0;border:none;border-top:1px solid var(--line)">
        <h3>Audit history</h3>
        <div class="scroll-x"><table class="tbl"><thead><tr><th>When</th><th>Action</th><th>User</th></tr></thead><tbody>
            @forelse($audit as $a)<tr><td class="mono">{{ $a->created_at }}</td><td>{{ $a->action }}</td><td>{{ $a->user_id ?? 'system' }}</td></tr>
            @empty<tr><td colspan="3"><span class="sub">No audit entries yet.</span></td></tr>@endforelse
        </tbody></table></div>
    </div></div>
</div>

<script>
(function(){
    var btns=document.querySelectorAll('.mtab-btn'), tabs=document.querySelectorAll('.mtab');
    function show(name){
        btns.forEach(function(b){b.classList.toggle('on',b.dataset.tab===name);});
        tabs.forEach(function(t){t.classList.toggle('on',t.dataset.tab===name);});
    }
    btns.forEach(function(b){b.addEventListener('click',function(){show(b.dataset.tab);});});
    var t=document.getElementById('seo_title'),tc=document.getElementById('tc');
    var d=document.getElementById('seo_description'),dc=document.getElementById('dc');
    function cnt(){if(t&&tc)tc.textContent=t.value.length+'/60';if(d&&dc)dc.textContent=d.value.length+'/160';}
    if(t)t.addEventListener('input',cnt);if(d)d.addEventListener('input',cnt);cnt();
})();
</script>
@endsection
