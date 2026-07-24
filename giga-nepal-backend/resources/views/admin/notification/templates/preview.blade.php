@extends('admin.layout')
@section('title', 'Preview: ' . ucwords(str_replace('-', ' ', $template)))
@section('crumb', 'Notifications / Templates / ' . ucwords(str_replace('-', ' ', $template)) . ' / Preview')

@section('content')
<div class="page-head">
    <div>
        <h2>Preview: {{ ucwords(str_replace('-', ' ', $template)) }}</h2>
        <p style="color:var(--muted)">Sample data preview. Actual emails use real user/order data.</p>
    </div>
    <div class="page-actions" style="display:flex;gap:8px">
        <a href="/admin/notification/templates/{{ $template }}" class="btn btn-ghost">Back to Template</a>
        <a href="/admin/notification/templates/{{ $template }}/edit" class="btn btn-primary">Edit Template</a>
    </div>
</div>

<div style="display:grid;grid-template-columns:1fr 320px;gap:16px;align-items:start">
    <div class="card">
        <div class="card-h"><h2>Email Preview</h2></div>
        <div style="background:#ffffff;padding:0;border-radius:0 0 8px 8px">
            <div style="max-width:600px;margin:0 auto;border:1px solid #e0e0e0;overflow:hidden">
                <div style="background:linear-gradient(135deg,#0f5bd7,#0f62e6);padding:28px 24px;text-align:center">
                    <div style="font-size:28px;line-height:1">&#9889;</div>
                    <h1 style="color:#fff;font-size:20px;margin:12px 0 0;font-weight:700">NeoGiga</h1>
                </div>
                <div style="padding:24px">
                    <pre style="font-family:monospace;font-size:12px;line-height:1.6;white-space:pre-wrap;color:#333">{{ $content }}</pre>
                </div>
                <div style="padding:20px 24px;background:#f8fafc;border-top:1px solid #dfe6ef;color:#8a97a8;font-size:12px;text-align:center">
                    <p>NeoGiga — Global Engineering Marketplace</p>
                </div>
            </div>
        </div>
    </div>

    <div>
        <div class="card">
            <div class="card-h"><h2>Sample Data</h2></div>
            <div class="card-body">
                @if(count($sampleData) > 0)
                    <table style="width:100%;font-size:.82rem">
                        @foreach($sampleData as $key => $value)
                        <tr>
                            <td style="padding:6px 0;color:var(--muted);vertical-align:top">${{ $key }}</td>
                            <td style="padding:6px 0;font-weight:500;word-break:break-all">
                                @if(is_array($value))
                                    @foreach($value as $item)
                                        <div>{{ $item['name'] ?? '' }} x{{ $item['quantity'] ?? 1 }} — {{ $item['price'] ?? '0.00' }}</div>
                                    @endforeach
                                @else
                                    {{ $value }}
                                @endif
                            </td>
                        </tr>
                        @endforeach
                    </table>
                @else
                    <p style="color:var(--muted);font-size:.88rem">No sample data available for this template.</p>
                @endif
            </div>
        </div>
    </div>
</div>
@endsection
