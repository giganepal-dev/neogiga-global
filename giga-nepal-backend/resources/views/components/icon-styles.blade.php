{{-- Shared styles for the icon component system. Include once per layout <head>.
     Theme-neutral: colours come from currentColor + a few CSS vars with fallbacks,
     so the same components render correctly on the dark storefront and admin. --}}
<style nonce="{{ $csp_nonce ?? '' }}">
    .ng-icon{display:inline-block;flex:none}
    .ng-sr{position:absolute!important;width:1px;height:1px;padding:0;margin:-1px;overflow:hidden;clip:rect(0,0,0,0);white-space:nowrap;border:0}

    /* Header action: icon (+ optional label) + count badge */
    .ng-haction{position:relative;display:inline-flex;align-items:center;gap:8px;
        min-height:40px;padding:0 10px;border-radius:10px;color:inherit;text-decoration:none;
        font-weight:600;font-size:.84rem;line-height:1;border:1px solid transparent;transition:.15s}
    .ng-haction:hover{background:rgba(127,127,127,.12);border-color:rgba(127,127,127,.22)}
    .ng-haction:focus-visible{outline:2px solid var(--ng-focus,#28d8fb);outline-offset:2px}
    .ng-haction.is-active{color:var(--ng-accent,#28d8fb)}
    .ng-haction__ico{position:relative;display:inline-flex}
    .ng-haction__badge{position:absolute;top:-8px;right:-10px;min-width:17px;height:17px;padding:0 4px;
        border-radius:999px;background:var(--ng-accent,#f9bd2c);color:#101417;font-size:.66rem;font-weight:800;
        display:inline-flex;align-items:center;justify-content:center;line-height:1}

    /* Icon-only button */
    .ng-iconbtn{display:inline-flex;align-items:center;justify-content:center;width:38px;height:38px;
        border-radius:10px;border:1px solid rgba(127,127,127,.28);background:transparent;color:inherit;
        cursor:pointer;transition:.15s}
    .ng-iconbtn:hover{background:rgba(127,127,127,.12);border-color:rgba(127,127,127,.45)}
    .ng-iconbtn:focus-visible{outline:2px solid var(--ng-focus,#28d8fb);outline-offset:2px}
    .ng-iconbtn:active{transform:translateY(1px)}
    .ng-iconbtn--solid{background:var(--ng-accent,#28d8fb);color:#003640;border-color:transparent}
    .ng-iconbtn--subtle{border-color:transparent;background:rgba(127,127,127,.1)}

    /* Labeled button */
    .ng-lbtn{display:inline-flex;align-items:center;gap:8px;min-height:40px;padding:0 16px;border-radius:10px;
        font-weight:700;font-size:.86rem;cursor:pointer;text-decoration:none;border:1px solid transparent;transition:.15s}
    .ng-lbtn:focus-visible{outline:2px solid var(--ng-focus,#28d8fb);outline-offset:2px}
    .ng-lbtn:active{transform:translateY(1px)}
    .ng-lbtn--primary{background:var(--ng-accent,#28d8fb);color:#003640}
    .ng-lbtn--primary:hover{filter:brightness(1.08)}
    .ng-lbtn--secondary{background:rgba(127,127,127,.12);color:inherit;border-color:rgba(127,127,127,.28)}
    .ng-lbtn--secondary:hover{border-color:rgba(127,127,127,.5)}
    .ng-lbtn--ghost{background:transparent;color:inherit;border-color:rgba(127,127,127,.28)}
    .ng-lbtn--ghost:hover{background:rgba(127,127,127,.1)}
    .ng-lbtn--danger{background:#e5484d;color:#fff}
    .ng-lbtn--danger:hover{filter:brightness(1.06)}

    /* Sidebar nav item */
    .ng-navitem{display:flex;align-items:center;gap:12px;padding:9px 12px;border-radius:10px;color:inherit;
        text-decoration:none;font-weight:600;font-size:.9rem;opacity:.86;transition:.15s}
    .ng-navitem:hover{background:rgba(127,127,127,.12);opacity:1}
    .ng-navitem:focus-visible{outline:2px solid var(--ng-focus,#28d8fb);outline-offset:2px}
    .ng-navitem.is-active{background:var(--ng-accent-soft,rgba(40,216,251,.14));color:var(--ng-accent,#28d8fb);opacity:1}
    
    /* Navigation groups */
    .nav-group{margin-bottom:16px}
    .nav-group-header{padding:8px 12px 6px;margin:0 -12px;border-bottom:1px solid rgba(255,255,255,.06)}
    .nav-group-label{font-size:.68rem;text-transform:uppercase;letter-spacing:.08em;color:#64748B;font-weight:700}
    .nav-group-items{display:grid;gap:4px;padding-top:6px}

    /* Table row action */
    .ng-tact{display:inline-flex;align-items:center;justify-content:center;width:30px;height:30px;border-radius:8px;
        border:1px solid rgba(127,127,127,.22);background:transparent;color:inherit;cursor:pointer;transition:.15s}
    .ng-tact:hover{background:rgba(127,127,127,.12)}
    .ng-tact:focus-visible{outline:2px solid var(--ng-focus,#28d8fb);outline-offset:2px}
    .ng-tact--primary:hover{color:#2563eb}
    .ng-tact--success:hover{color:#16a34a}
    .ng-tact--danger:hover{color:#e5484d;border-color:rgba(229,72,77,.5)}

    /* Status pill */
    .ng-status{display:inline-flex;align-items:center;gap:5px;padding:3px 9px;border-radius:999px;font-size:.74rem;
        font-weight:700;border:1px solid transparent}
    .ng-status--ok{background:rgba(22,163,74,.14);color:#16a34a;border-color:rgba(22,163,74,.3)}
    .ng-status--bad{background:rgba(229,72,77,.14);color:#e5484d;border-color:rgba(229,72,77,.3)}
    .ng-status--warn{background:rgba(217,119,6,.14);color:#d97706;border-color:rgba(217,119,6,.3)}
    .ng-status--muted{background:rgba(127,127,127,.14);color:#8b8f98;border-color:rgba(127,127,127,.3)}

    /* Empty state */
    .ng-empty{display:flex;flex-direction:column;align-items:center;text-align:center;gap:6px;padding:40px 20px;opacity:.85}
    .ng-empty__ico{color:var(--ng-accent,#28d8fb);opacity:.8}
    .ng-empty__title{font-weight:700;margin:6px 0 0}
    .ng-empty__msg{font-size:.86rem;opacity:.7;margin:0}

    @media (prefers-reduced-motion: reduce){.ng-haction,.ng-iconbtn,.ng-lbtn,.ng-navitem,.ng-tact{transition:none}}
</style>
