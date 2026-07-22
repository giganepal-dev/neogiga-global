@extends('frontend.account.layout')
@section('title', $title.' — NeoGiga')
@section('account-content')
<header class="account-topbar"><div><h1>{{ $title }}</h1><p>Live account data scoped to your NeoGiga login.</p></div><a class="account-button secondary" href="{{ $backUrl }}">Back to list</a></header>
<section class="account-panel">
    <div class="account-detail-grid">
        @foreach((array)$record as $field=>$value)
            @continue(in_array($field, ['id','user_id','meta','metadata','billing_address','shipping_address'], true))
            <div><span>{{ ucwords(str_replace('_',' ',$field)) }}</span><strong>
                @if($value === null || $value === '') —
                @elseif(str_ends_with($field, '_at')) {{ \Carbon\Carbon::parse($value)->format('d M Y, H:i') }}
                @elseif(is_numeric($value) && str_contains($field, 'total')) {{ number_format((float)$value, 2) }}
                @else {{ $value }} @endif
            </strong></div>
        @endforeach
    </div>
</section>
@foreach($relations as $relation)
<section class="account-panel">
    <div class="account-panel-head"><div><h2>{{ $relation['title'] }}</h2></div></div>
    @if($relation['rows']->isEmpty())<div class="account-empty">No records yet.</div>@else
    <div class="account-table-wrap"><table class="account-table"><thead><tr>@foreach($relation['columns'] as $label)<th>{{ $label }}</th>@endforeach</tr></thead><tbody>
        @foreach($relation['rows'] as $row)<tr>@foreach($relation['columns'] as $field=>$label)@php($value=$row->{$field} ?? null)<td>
            @if(in_array($field,['status','priority'],true))<span class="account-badge {{ $value }}">{{ str_replace('_',' ',(string)$value) }}</span>
            @elseif($value && str_ends_with($field,'_at')){{ \Carbon\Carbon::parse($value)->format('d M Y, H:i') }}
            @elseif(is_numeric($value) && in_array($field,['unit_price','target_price','line_total','total_price','tax_amount','amount'],true)){{ number_format((float)$value,2) }}
            @else{{ $value !== null && $value !== '' ? $value : '—' }}@endif
        </td>@endforeach</tr>@endforeach
    </tbody></table></div>@endif
</section>
@endforeach
@endsection
