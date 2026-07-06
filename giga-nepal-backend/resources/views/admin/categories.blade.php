@extends('admin.layout')
@section('title','Categories')
@section('crumb','Engineering taxonomy · '.number_format($total).' nodes')
@section('content')

<div class="card">
    <div class="card-h">
        <div><h2>Category tree</h2><div class="sub">{{ number_format($roots->count()) }} root branches · {{ number_format($total) }} total</div></div>
    </div>
    @if ($roots->isEmpty())
        <div class="empty">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6"><path d="M3 6h18M3 12h18M3 18h12" stroke-linecap="round"/></svg>
            <h3>No categories yet</h3>
            <p>Run the CategoryTaxonomySeeder to load the engineering taxonomy.</p>
        </div>
    @else
        <ul class="tree">
            @foreach ($roots as $root)
                <li>
                    <div class="row">
                        <span class="dot"></span>
                        <strong>{{ $root->name }}</strong>
                        @if($root->is_featured)<span class="badge b-info">Featured</span>@endif
                        @unless($root->is_active)<span class="badge b-muted">Hidden</span>@endunless
                        <span class="cnt mono">{{ $root->slug }}</span>
                    </div>
                    @if ($root->children->isNotEmpty())
                        <ul class="kids">
                            @foreach ($root->children as $child)
                                <li><div class="row">{{ $child->name }}<span class="cnt">{{ $child->children_count ? number_format($child->children_count).' sub' : '' }}</span></div></li>
                            @endforeach
                        </ul>
                    @endif
                </li>
            @endforeach
        </ul>
    @endif
</div>

@endsection
