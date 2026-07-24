@extends('frontend.layout')
@section('title', 'AI & Robotics News — NeoGiga')
@section('meta_description', 'Latest news, product launches, research updates, and press releases in AI and robotics.')
@section('content')
<div style="max-width:1200px;margin:0 auto;padding:24px">
    <div style="font-size:.85rem;color:#64748b;margin-bottom:16px"><a href="{{ url($localePrefix ?? '/en') }}/ai" style="color:#3b82f6;text-decoration:none">AI & Robotics</a> / News</div>
    <h1 style="font-size:1.8rem;font-weight:800;margin-bottom:24px">News & Releases</h1>

    @if($featured)
    <div style="background:#f8fafc;border:1px solid #e2e8f0;border-radius:16px;padding:32px;margin-bottom:32px">
        <span style="padding:3px 10px;background:#eff6ff;color:#2563eb;border-radius:6px;font-size:.75rem;text-transform:uppercase">{{ str_replace('_',' ',$featured->article_type) }}</span>
        <h2 style="font-size:1.4rem;font-weight:700;margin:12px 0 8px"><a href="{{ url($localePrefix ?? '/en') }}/ai/news/{{ $featured->slug }}" style="color:inherit;text-decoration:none">{{ $featured->title }}</a></h2>
        <p style="color:#64748b;margin-bottom:8px">{{ $featured->excerpt }}</p>
        <div style="font-size:.8rem;color:#94a3b8">{{ $featured->published_at?->format('M d, Y') }}</div>
    </div>
    @endif

    <div style="display:grid;grid-template-columns:repeat(auto-fill,minmax(280px,1fr));gap:16px">
        @forelse($articles as $article)
        <a href="{{ url($localePrefix ?? '/en') }}/ai/news/{{ $article->slug }}" style="display:block;background:#fff;border:1px solid #e2e8f0;border-radius:12px;padding:20px;text-decoration:none;color:inherit">
            <span style="padding:2px 8px;background:#eff6ff;color:#2563eb;border-radius:4px;font-size:.7rem;text-transform:uppercase">{{ str_replace('_',' ',$article->article_type) }}</span>
            <div style="font-weight:700;margin-top:8px">{{ $article->title }}</div>
            <div style="font-size:.85rem;color:#64748b;margin-top:4px">{{ Str::limit($article->excerpt, 100) }}</div>
            <div style="font-size:.75rem;color:#94a3b8;margin-top:8px">{{ $article->published_at?->format('M d, Y') }}</div>
        </a>
        @empty
        <div style="grid-column:1/-1;text-align:center;padding:48px;color:#64748b">No articles published yet.</div>
        @endforelse
    </div>
    <div style="margin-top:32px;text-align:center">{{ $articles->withQueryString()->links() }}</div>
</div>
@endsection
