@props([
    'certifications' => collect(),
    'compact' => true,
])

@php($marks = collect($certifications))

<div {{ $attributes->class(['cert-marks', 'cert-marks-detail' => ! $compact]) }} aria-label="Product certification and compliance status">
    @forelse($marks as $mark)
        @if(! empty($mark['url']))
            <a class="cert-mark is-verified" href="{{ $mark['url'] }}" target="_blank" rel="nofollow noopener" title="{{ $mark['source'] ?? 'Verified certification record' }}">
                <x-icon name="quality" size="14" /> <span>{{ $mark['label'] }}</span>
            </a>
        @else
            <span class="cert-mark is-verified" title="{{ $mark['source'] ?? 'Verified certification record' }}">
                <x-icon name="quality" size="14" /> <span>{{ $mark['label'] }}</span>
            </span>
        @endif
    @empty
        <span class="cert-mark is-pending" title="No verified certification record is currently published. Confirm required compliance documents through RFQ.">
            <x-icon name="shield" size="14" /> <span>{{ $compact ? 'Compliance docs' : 'Documentation on request' }}</span>
        </span>
    @endforelse
</div>
