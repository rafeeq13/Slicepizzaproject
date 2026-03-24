/**
 * Slice+ Ultra Premium Payment — v3
 * Light inputs on dark bg (Stripe-style) — works with Clover cross-origin iframes
 */
(function(){

var css = document.createElement('style');
css.textContent = `
/* ── Wrapper ── */
.pay-section { position:relative; margin-top:8px; }

/* ── Cards header ── */
.pay-cards-header {
    display:flex; align-items:center; justify-content:space-between;
    margin-bottom:24px; flex-wrap:wrap; gap:10px;
}
.pay-cards-label {
    font-size:.7rem; font-weight:700; color:#555;
    text-transform:uppercase; letter-spacing:2px;
    display:flex; align-items:center; gap:8px;
}
.pay-cards-label svg { width:14px; height:14px; stroke:#22c55e; fill:none; stroke-width:2.5; }
.pay-cards-icons { display:flex; gap:6px; align-items:center; }
.pay-card-icon {
    background:#fff;
    border-radius:6px; padding:4px 8px;
    display:flex; align-items:center; justify-content:center;
    height:28px; box-shadow:0 2px 8px rgba(0,0,0,.3);
}
.pay-card-icon svg { height:16px; width:auto; }

/* ── Main glass container ── */
.pay-iframe-wrap {
    background:rgba(18,18,18,.95);
    border:1px solid rgba(255,255,255,.06);
    border-radius:20px;
    padding:28px;
    margin-bottom:0;
    box-shadow:
        0 2px 4px rgba(0,0,0,.5),
        0 12px 40px rgba(0,0,0,.4),
        inset 0 1px 0 rgba(255,255,255,.04);
    position:relative; overflow:hidden;
}
.pay-iframe-wrap::before {
    content:''; position:absolute;
    top:0; left:20%; right:20%; height:1px;
    background:linear-gradient(90deg,transparent,rgba(255,255,255,.08),transparent);
}

/* ── Field ── */
.pay-field { margin-bottom:20px; position:relative; }
.pay-field:last-child { margin-bottom:0; }
.pay-field-full { width:100%; }

.pay-field-label {
    display:flex; align-items:center; gap:5px;
    font-size:.68rem; font-weight:700;
    color:#555; margin-bottom:8px;
    letter-spacing:1.2px; text-transform:uppercase;
}
.pay-field-label svg { width:11px; height:11px; stroke:#444; fill:none; stroke-width:2.5; }

/* ── Input box — light style to match Clover iframe ── */
.pay-field-box {
    background:#f8f8f8;
    border:2px solid transparent;
    border-radius:12px;
    padding:0 16px;
    min-height:52px;
    display:flex; align-items:center;
    transition:all .2s ease;
    position:relative;
    box-shadow:0 2px 8px rgba(0,0,0,.2);
}
.pay-field-box:focus-within {
    border-color:rgba(223,43,43,.5);
    box-shadow:0 0 0 3px rgba(223,43,43,.1), 0 2px 8px rgba(0,0,0,.2);
    background:#fff;
}
.pay-field-full .pay-field-box { min-height:54px; }

/* Clover iframes inside box */
.pay-field-box > div,
.pay-field-box iframe {
    width:100%!important;
    min-height:48px!important;
    border:none!important;
    background:transparent!important;
}

/* ── Row layout ── */
.pay-fields-row {
    display:grid;
    grid-template-columns:1fr 1fr 1fr;
    gap:12px;
}

/* ── Security strip ── */
.pay-security {
    display:flex; align-items:center; justify-content:center;
    gap:0; margin-top:20px; border-radius:12px; overflow:hidden;
    border:1px solid rgba(34,197,94,.1);
    background:rgba(34,197,94,.03);
}
.pay-security-item {
    flex:1; display:flex; align-items:center; justify-content:center;
    gap:5px; font-size:.65rem; color:#22c55e; font-weight:700;
    letter-spacing:.8px; padding:11px 6px;
    border-right:1px solid rgba(34,197,94,.08);
    text-transform:uppercase;
}
.pay-security-item:last-child { border-right:none; }
.pay-security-item svg { width:11px; height:11px; stroke:#22c55e; fill:none; stroke-width:2.5; flex-shrink:0; }

/* ── Trust text ── */
.pay-trust {
    text-align:center; margin-top:14px;
    font-size:.67rem; color:#444; line-height:1.6;
    display:flex; align-items:center; justify-content:center; gap:5px;
}
.pay-trust svg { width:10px; height:10px; stroke:#444; fill:none; stroke-width:2; flex-shrink:0; }

@media(max-width:480px){
    .pay-fields-row { grid-template-columns:1fr 1fr; gap:10px; }
    .pay-iframe-wrap { padding:20px; }
    .pay-security { flex-wrap:wrap; }
    .pay-security-item { min-width:50%; }
}
`;
document.head.appendChild(css);

// Card icons
var cardIcons = {
    visa: '<svg viewBox="0 0 48 32" width="36" height="22"><rect width="48" height="32" rx="4" fill="#1A1F71"/><path d="M19.5 21h-3l1.9-11.5h3L19.5 21zm12.7-11.2c-.6-.2-1.5-.5-2.7-.5-3 0-5.1 1.5-5.1 3.7 0 1.6 1.5 2.5 2.6 3 1.1.6 1.5 1 1.5 1.5 0 .8-1 1.2-1.9 1.2-1.2 0-1.9-.2-2.9-.6l-.4-.2-.4 2.5c.7.3 2.1.6 3.5.6 3.2 0 5.2-1.5 5.2-3.8 0-1.3-.8-2.2-2.5-3-1-.5-1.7-.9-1.7-1.4 0-.5.5-1 1.7-1 1 0 1.7.2 2.3.4l.3.1.5-2.5zM37 9.5h-2.3c-.7 0-1.3.2-1.6 1L29 21h3.2l.6-1.8h3.9l.4 1.8H40L37 9.5zm-3.5 8l1.2-3.4.4-1.1.2 1 .7 3.5h-2.5zM15.5 9.5L12.6 17l-.3-1.5c-.5-1.8-2.2-3.7-4-4.7l2.7 10.2h3.2l4.8-11.5h-3.5z" fill="#fff"/><path d="M10 9.5H5l-.1.3c3.8.9 6.3 3.2 7.3 5.9l-1-5.2c-.2-.7-.7-1-1.2-1z" fill="#F9A51A"/></svg>',
    mastercard: '<svg viewBox="0 0 48 32" width="36" height="22"><rect width="48" height="32" rx="4" fill="#252525"/><circle cx="18" cy="16" r="8" fill="#EB001B"/><circle cx="30" cy="16" r="8" fill="#F79E1B"/><path d="M24 10a8 8 0 000 12 8 8 0 000-12z" fill="#FF5F00"/></svg>',
    amex: '<svg viewBox="0 0 48 32" width="36" height="22"><rect width="48" height="32" rx="4" fill="#006FCF"/><text x="24" y="19" text-anchor="middle" fill="white" font-size="9" font-weight="bold" font-family="Arial">AMEX</text></svg>',
    discover: '<svg viewBox="0 0 48 32" width="36" height="22"><rect width="48" height="32" rx="4" fill="#fff" stroke="#ddd"/><circle cx="28" cy="16" r="7" fill="#F76F1B"/><text x="14" y="19" fill="#333" font-size="7" font-weight="bold" font-family="Arial">DISC</text></svg>'
};

var observer = new MutationObserver(function(){ enhancePaymentSection(); });
observer.observe(document.body, { childList:true, subtree:true });
var checkInterval = setInterval(function(){ enhancePaymentSection(); }, 500);
var enhanced = false;

function makeField(labelHtml, cloverEl) {
    var f   = document.createElement('div');
    f.className = 'pay-field';
    var lbl = document.createElement('div');
    lbl.className = 'pay-field-label';
    lbl.innerHTML = labelHtml;
    var box = document.createElement('div');
    box.className = 'pay-field-box';
    if (cloverEl) box.appendChild(cloverEl);
    f.appendChild(lbl);
    f.appendChild(box);
    return f;
}

function enhancePaymentSection() {
    var cardNum  = document.getElementById('card-number');
    var cardDate = document.getElementById('card-date');
    var cardCvv  = document.getElementById('card-cvv');
    var cardZip  = document.getElementById('card-zip');

    if (!cardNum || enhanced) return;
    if (cardNum.closest('.pay-iframe-wrap')) return;

    enhanced = true;
    clearInterval(checkInterval);

    var psContainer = cardNum.closest('.ps');
    if (!psContainer) psContainer = cardNum.parentElement && cardNum.parentElement.parentElement;
    if (!psContainer) return;

    // Detach all fields first
    psContainer.appendChild(cardNum);
    if (cardDate) psContainer.appendChild(cardDate);
    if (cardCvv)  psContainer.appendChild(cardCvv);
    if (cardZip)  psContainer.appendChild(cardZip);

    // ── Build UI ──
    var wrapper = document.createElement('div');
    wrapper.className = 'pay-section';

    // Header
    var header = document.createElement('div');
    header.className = 'pay-cards-header';
    header.innerHTML =
        '<span class="pay-cards-label">' +
            '<svg viewBox="0 0 24 24"><rect x="1" y="4" width="22" height="16" rx="2"/><line x1="1" y1="10" x2="23" y2="10"/></svg>' +
            'Secure Payment' +
        '</span>' +
        '<div class="pay-cards-icons">' +
            '<span class="pay-card-icon">' + cardIcons.visa + '</span>' +
            '<span class="pay-card-icon">' + cardIcons.mastercard + '</span>' +
            '<span class="pay-card-icon">' + cardIcons.amex + '</span>' +
            '<span class="pay-card-icon">' + cardIcons.discover + '</span>' +
        '</div>';

    // Glass container
    var fieldsWrap = document.createElement('div');
    fieldsWrap.className = 'pay-iframe-wrap';

    // Card number
    var numField = makeField(
        '<svg viewBox="0 0 24 24"><rect x="1" y="4" width="22" height="16" rx="2"/><line x1="1" y1="10" x2="23" y2="10"/></svg> Card Number',
        cardNum
    );
    numField.classList.add('pay-field-full');

    // Row
    var row = document.createElement('div');
    row.className = 'pay-fields-row';

    row.appendChild(makeField(
        '<svg viewBox="0 0 24 24"><rect x="3" y="4" width="18" height="18" rx="2"/><line x1="16" y1="2" x2="16" y2="6"/><line x1="8" y1="2" x2="8" y2="6"/><line x1="3" y1="10" x2="21" y2="10"/></svg> Expiry',
        cardDate
    ));
    row.appendChild(makeField(
        '<svg viewBox="0 0 24 24"><rect x="3" y="11" width="18" height="11" rx="2"/><path d="M7 11V7a5 5 0 0110 0v4"/></svg> CVV',
        cardCvv
    ));

    if (cardZip) {
        row.appendChild(makeField(
            '<svg viewBox="0 0 24 24"><path d="M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 0118 0z"/><circle cx="12" cy="10" r="3"/></svg> Postal',
            cardZip
        ));
    } else {
        row.style.gridTemplateColumns = '1fr 1fr';
    }

    fieldsWrap.appendChild(numField);
    fieldsWrap.appendChild(row);

    // Security strip
    var security = document.createElement('div');
    security.className = 'pay-security';
    security.innerHTML =
        '<span class="pay-security-item"><svg viewBox="0 0 24 24"><path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/></svg>SSL Secured</span>' +
        '<span class="pay-security-item"><svg viewBox="0 0 24 24"><rect x="3" y="11" width="18" height="11" rx="2"/><path d="M7 11V7a5 5 0 0110 0v4"/></svg>Encrypted</span>' +
        '<span class="pay-security-item"><svg viewBox="0 0 24 24"><path d="M22 11.08V12a10 10 0 11-5.93-9.14"/><polyline points="22 4 12 14.01 9 11.01"/></svg>PCI Compliant</span>';

    // Trust
    var trust = document.createElement('div');
    trust.className = 'pay-trust';
    trust.innerHTML = '<svg viewBox="0 0 24 24"><rect x="3" y="11" width="18" height="11" rx="2"/><path d="M7 11V7a5 5 0 0110 0v4"/></svg>Your payment is processed securely through Clover. We never store your card details.';

    // Assemble
    wrapper.appendChild(header);
    wrapper.appendChild(fieldsWrap);
    wrapper.appendChild(security);
    wrapper.appendChild(trust);

    psContainer.innerHTML = '';
    psContainer.appendChild(wrapper);
}

})();