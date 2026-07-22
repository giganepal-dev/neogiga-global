<div class="confirm-card" role="alert" aria-live="polite">
    <div class="confirm-icon">✅</div>
    <h1 class="confirm-title">{{ $title }}</h1>

    @if(!empty($reference))
        <div class="confirm-ref">{{ $reference }}</div>
    @endif

    <p class="confirm-message">{{ $message }}</p>

    @if(!empty($summary))
        <div class="confirm-summary">
            @foreach($summary as $label => $value)
                <div class="confirm-row"><span>{{ $label }}</span><strong>{{ $value }}</strong></div>
            @endforeach
        </div>
    @endif

    @if(!empty($emailStatus))
        <div class="confirm-email">
            @if($emailStatus === 'queued') 📨 Confirmation email queued
            @elseif($emailStatus === 'sent') ✅ Confirmation email sent
            @elseif($emailStatus === 'failed') ⚠️ Your submission was successful, but the confirmation email could not be sent
            @endif
        </div>
    @endif

    @if(!empty($nextStep))
        <p class="confirm-next">{{ $nextStep }}</p>
    @endif

    <div class="confirm-actions">
        @if(!empty($primaryAction))
            <a href="{{ $primaryAction['url'] }}" class="btn btn-primary">{{ $primaryAction['label'] }}</a>
        @endif
        @if(!empty($secondaryActions))
            @foreach($secondaryActions as $action)
                <a href="{{ $action['url'] }}" class="btn btn-ghost">{{ $action['label'] }}</a>
            @endforeach
        @endif
    </div>

    <div class="confirm-support">
        <p>Need help? <a href="/en/contact">Contact NeoGiga Support</a></p>
    </div>
</div>

<style nonce="{{ $csp_nonce ?? '' }}">
.confirm-card{max-width:640px;margin:40px auto;padding:40px 32px;background:var(--s1);border:1px solid var(--line);border-radius:16px;text-align:center}
.confirm-icon{font-size:3rem;margin-bottom:12px}
.confirm-title{font-size:1.5rem;margin:0 0 8px;color:var(--on)}
.confirm-ref{display:inline-block;padding:8px 18px;background:var(--bg2);border:1px solid var(--line);border-radius:8px;font-family:ui-monospace,monospace;font-size:1.1rem;font-weight:700;color:var(--cyan);margin-bottom:16px}
.confirm-message{color:var(--muted);margin:0 0 20px;line-height:1.6}
.confirm-summary{display:grid;gap:8px;max-width:400px;margin:16px auto;text-align:left}
.confirm-row{display:flex;justify-content:space-between;gap:16px;padding:8px 0;border-bottom:1px solid var(--line);font-size:.9rem}
.confirm-row span{color:var(--muted)}.confirm-row strong{color:var(--on)}
.confirm-email{padding:10px 16px;border-radius:8px;background:var(--bg2);margin:12px 0;font-size:.88rem;color:var(--muted)}
.confirm-next{color:var(--muted);font-size:.9rem;margin:12px 0}
.confirm-actions{display:flex;gap:10px;justify-content:center;flex-wrap:wrap;margin:20px 0}
.confirm-support{margin-top:24px;font-size:.82rem;color:var(--faint)}
.confirm-support a{color:var(--cyan)}
@media(max-width:480px){.confirm-card{padding:24px 16px;margin:20px 8px}}
</style>
