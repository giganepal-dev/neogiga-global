@extends('frontend.layout')
@section('title', 'AI & Robotics Store — NeoGiga')
@section('meta_description', 'Shop AI development boards, sensors, robot components, and accessories.')
@section('content')
<div style="max-width:1200px;margin:0 auto;padding:24px">
    <div style="font-size:.85rem;color:#64748b;margin-bottom:16px"><a href="{{ url($localePrefix ?? '/en') }}/ai" style="color:#3b82f6;text-decoration:none">AI & Robotics</a> / Store</div>
    <h1 style="font-size:1.8rem;font-weight:800;margin-bottom:24px">AI & Robotics Store</h1>

    <form method="GET" style="display:flex;gap:12px;margin-bottom:24px">
        <input type="text" name="q" value="{{ request('q') }}" placeholder="Search AI hardware, sensors, components..." style="flex:1;padding:10px 12px;border:1px solid #d1d5db;border-radius:6px">
        <button type="submit" style="padding:10px 24px;background:#3b82f6;color:#fff;border:none;border-radius:6px;font-weight:600">Search</button>
    </form>

    <div style="display:grid;grid-template-columns:repeat(auto-fill,minmax(220px,1fr));gap:16px">
        @forelse($products as $product)
        <a href="{{ url($localePrefix ?? '/en') }}/products/{{ $product->slug }}" style="display:block;background:#fff;border:1px solid #e2e8f0;border-radius:12px;padding:16px;text-decoration:none;color:inherit">
            @if($product->images && $product->images->first())
                <img src="{{ $product->images->first()->image_path }}" alt="{{ $product->name }}" style="width:100%;height:140px;object-fit:contain;margin-bottom:8px;border-radius:8px;background:#f8fafc">
            @else
                <div style="width:100%;height:140px;background:#f1f5f9;border-radius:8px;display:flex;align-items:center;justify-content:center;font-size:2rem;margin-bottom:8px">📦</div>
            @endif
            <div style="font-weight:600;font-size:.9rem;margin-bottom:2px">{{ Str::limit($product->name, 50) }}</div>
            <div style="font-size:.8rem;color:#64748b">{{ $product->brand?->name ?? '' }} {{ $product->mpn ? '· '.$product->mpn : '' }}</div>
        </a>
        @empty
        <div style="grid-column:1/-1;text-align:center;padding:48px;color:#64748b">No products found.</div>
        @endforelse
    </div>
    <div style="margin-top:32px;text-align:center">{{ $products->withQueryString()->links() }}</div>
</div>
@endsection
