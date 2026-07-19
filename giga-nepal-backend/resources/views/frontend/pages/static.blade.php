@extends('frontend.layout')
@section('title', $page['title'].' - NeoGiga')
@section('description', \Illuminate\Support\Str::limit($page['intro'], 155))

@section('content')
<section class="section" style="padding-top:18px">
    <div class="wrap" style="max-width:880px">
        <nav class="crumbs" aria-label="Breadcrumb"><a href="/en">Home</a><span><x-icon name="chevron-right" size="12"/></span><strong>{{ $page['title'] }}</strong></nav>
        <div class="panel" style="padding:34px">
            <p class="eyebrow">NeoGiga</p>
            <h1 class="page-title" style="font-size:clamp(1.9rem,4vw,2.8rem)">{{ $page['title'] }}</h1>
            <p class="lead">{{ $page['intro'] }}</p>
            @foreach($page['sections'] as $section)
                <h2 style="font-size:1.15rem;margin:26px 0 8px">{{ $section['h'] }}</h2>
                @foreach($section['p'] as $paragraph)
                    <p class="sub" style="margin:0 0 10px">{{ $paragraph }}</p>
                @endforeach
            @endforeach
        </div>
    </div>
</section>
@endsection
