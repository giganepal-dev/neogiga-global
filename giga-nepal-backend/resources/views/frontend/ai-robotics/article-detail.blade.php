@extends('frontend.layout')
@section('title', $article->title . ' — NeoGiga AI & Robotics')
@section('meta_description', Str::limit($article->excerpt ?? strip_tags($article->body), 160))
@section('content')
<div style="max-width:800px;margin:0 auto;padding:24px">
    <div style="font-size:.85rem;color:#64748b;margin-bottom:16px"><a href="{{ url($localePrefix ?? '/en') }}/ai" style="color:#3b82f6;text-decoration:none">AI & Robotics</a> / <a href="{{ url($localePrefix ?? '/en') }}/ai/news" style="color:#3b82f6;text-decoration:none">News</a> / {{ Str::limit($article->title, 40) }}</div>
    <span style="padding:3px 10px;background:#eff6ff;color:#2563eb;border-radius:6px;font-size:.75rem;text-transform:uppercase">{{ str_replace('_',' ',$article->article_type) }}</span>
    <h1 style="font-size:1.8rem;font-weight:800;margin:12px 0 8px">{{ $article->title }}</h1>
    <div style="color:#94a3b8;font-size:.85rem;margin-bottom:24px">{{ $article->published_at?->format('F d, Y') }} @if($article->author) · {{ $article->author->name }}@endif</div>
    @if($article->excerpt)<p style="color:#475569;font-size:1.05rem;line-height:1.6;margin-bottom:24px;font-style:italic">{{ $article->excerpt }}</p>@endif
    <div style="color:#334155;line-height:1.8;font-size:.95rem">{!! $article->body !!}</div>
    @if($article->tags && count($article->tags))
    <div style="margin-top:32px;padding-top:16px;border-top:1px solid #e2e8f0;display:flex;gap:6px;flex-wrap:wrap">
        @foreach($article->tags as $tag)<span style="padding:4px 10px;background:#f1f5f9;color:#475569;border-radius:6px;font-size:.8rem">{{ $tag }}</span>@endforeach
    </div>
    @endif
</div>
@endsection
