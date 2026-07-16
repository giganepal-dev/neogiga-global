@props([
    'status' => 'pending',   // approved|active|rejected|pending|draft|archived|...
    'label' => null,         // override the display text
])

@php
    $map = [
        'approved' => ['icon' => 'approve', 'tone' => 'ok',    'text' => 'Approved'],
        'active'   => ['icon' => 'approve', 'tone' => 'ok',    'text' => 'Active'],
        'paid'     => ['icon' => 'approve', 'tone' => 'ok',    'text' => 'Paid'],
        'rejected' => ['icon' => 'reject',  'tone' => 'bad',   'text' => 'Rejected'],
        'failed'   => ['icon' => 'reject',  'tone' => 'bad',   'text' => 'Failed'],
        'pending'  => ['icon' => 'clock',   'tone' => 'warn',  'text' => 'Pending'],
        'draft'    => ['icon' => 'edit',    'tone' => 'muted', 'text' => 'Draft'],
        'archived' => ['icon' => 'archive', 'tone' => 'muted', 'text' => 'Archived'],
    ];
    $s = $map[$status] ?? ['icon' => 'help', 'tone' => 'muted', 'text' => ucfirst(str_replace('_', ' ', $status))];
    $text = $label ?? $s['text'];
@endphp

<span class="ng-status ng-status--{{ $s['tone'] }}" {{ $attributes }}>
    <x-icon :name="$s['icon']" :size="14" />
    <span>{{ $text }}</span>
</span>
