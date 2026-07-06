@extends('admin.layout')

@section('title','SEO')
@section('crumb','Admin Console / SEO')

@section('content')
<div class="grid kpis">
    <div class="kpi"><div class="t">SEO pages</div><div class="v tnum">{{ number_format($pages->total()) }}</div><div class="s">Managed metadata</div></div>
    <div class="kpi"><div class="t">Redirects</div><div class="v tnum">{{ number_format($redirects->count()) }}</div><div class="s">Recent rules</div></div>
    <div class="kpi"><div class="t">Product meta</div><div class="v tnum">{{ number_format($productMetaCount) }}</div><div class="s">Product SEO rows</div></div>
</div>

<div class="note">Sitemap: <span class="mono">{{ $sitemapUrl }}</span>. Public pages should show concise source labels while admin pages keep full source URLs and confidence metadata.</div>

<section class="card">
    <div class="card-h"><h2>SEO Pages</h2><span class="sub">API: /api/v1/admin/console/seo/pages</span></div>
    <div class="scroll-x">
        <table class="tbl">
            <thead><tr><th>Path</th><th>Title</th><th>Robots</th><th>Source</th><th>Confidence</th><th>Updated</th></tr></thead>
            <tbody>
            @forelse($pages as $page)
                <tr>
                    <td class="mono">{{ $page->url_path }}</td>
                    <td>{{ $page->title }}</td>
                    <td><span class="badge {{ $page->is_indexable ? 'b-ok':'b-warn' }}">{{ $page->robots }}</span></td>
                    <td>{{ $page->source_name ?: 'manual' }}</td>
                    <td>{{ $page->confidence_level }}</td>
                    <td>{{ $page->updated_at }}</td>
                </tr>
            @empty
                <tr><td colspan="6"><div class="empty"><h3>No SEO pages configured</h3><p>Add page metadata through the protected SEO API.</p></div></td></tr>
            @endforelse
            </tbody>
        </table>
    </div>
</section>

<section class="card" style="margin-top:14px">
    <div class="card-h"><h2>Redirects</h2><span class="sub">API: /api/v1/admin/console/seo/redirects</span></div>
    <div class="scroll-x">
        <table class="tbl">
            <thead><tr><th>From</th><th>To</th><th>Status</th><th>Active</th></tr></thead>
            <tbody>
            @forelse($redirects as $redirect)
                <tr>
                    <td class="mono">{{ $redirect->from_path }}</td>
                    <td class="mono">{{ $redirect->to_url }}</td>
                    <td>{{ $redirect->status_code }}</td>
                    <td><span class="badge {{ $redirect->is_active ? 'b-ok':'b-muted' }}">{{ $redirect->is_active ? 'yes':'no' }}</span></td>
                </tr>
            @empty
                <tr><td colspan="4"><div class="empty"><h3>No redirects</h3><p>Redirect rules will appear here after they are added.</p></div></td></tr>
            @endforelse
            </tbody>
        </table>
    </div>
</section>

<div style="margin-top:14px">{{ $pages->links() }}</div>
@endsection

