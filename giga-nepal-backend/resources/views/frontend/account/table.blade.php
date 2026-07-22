@extends('frontend.account.layout')
@section('title', $title.' — NeoGiga')
@section('account-content')
<header class="account-topbar"><div><h1>{{ $title }}</h1><p>{{ $intro }}</p></div><a class="account-button" href="{{ $actionUrl }}">{{ $actionLabel }}</a></header>
@if($rows->isEmpty())
    <div class="account-panel account-empty">There are no records in this account yet.</div>
@else
    <div class="account-table-wrap"><table class="account-table"><thead><tr>@foreach($columns as $label)<th>{{ $label }}</th>@endforeach</tr></thead><tbody>
    @foreach($rows as $row)<tr>@foreach($columns as $field => $label)
        @php $value = $row->{$field} ?? null; @endphp
        <td class="clip">
            @if($detailBase && $loop->first)
                <a href="{{ $detailBase }}/{{ $row->id }}">{{ $value !== null && $value !== '' ? $value : 'View' }}</a>
            @elseif($field === 'status' || $field === 'payment_status' || $field === 'priority')
                <span class="account-badge {{ $value }}">{{ str_replace('_',' ',(string)($value ?: '—')) }}</span>
            @elseif(str_ends_with($field, '_at') && $value)
                {{ \Carbon\Carbon::parse($value)->format('d M Y, H:i') }}
            @elseif(is_numeric($value) && in_array($field, ['grand_total','amount','subtotal'], true))
                {{ number_format((float)$value, 2) }}
            @else {{ $value !== null && $value !== '' ? $value : '—' }} @endif
        </td>
    @endforeach</tr>@endforeach
    </tbody></table></div>
@endif
@endsection
